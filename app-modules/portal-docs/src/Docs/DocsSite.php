<?php

declare(strict_types=1);

namespace He4rt\PortalDocs\Docs;

use He4rt\PortalDocs\OpenApi\OpenApiDocument;
use He4rt\PortalDocs\OpenApi\SpecLoader;
use He4rt\PortalDocs\Support\Value;

/**
 * The navigation tree the whole portal reads from.
 *
 * A page exists only if docs.json lists it: an .mdx file sitting in content/ without an
 * entry is not reachable, does not appear in llms.txt, and answers 404.
 */
final class DocsSite
{
    /** Slugs are relative paths, so anything outside this shape could escape content/. */
    private const string SLUG_PATTERN = '/^[a-z0-9_-]+(\/[a-z0-9_-]+)*$/';

    private ?DocsConfig $config = null;

    /** @var list<array{tab: string, groups: list<array{group: string, pages: list<DocsPage>}>}>|null */
    private ?array $navigation = null;

    private ?OpenApiDocument $openApi = null;

    public function __construct(
        private readonly string $contentPath,
        private readonly SpecLoader $specLoader,
        private readonly string $prefix = 'docs',
        private readonly bool $openApiEnabled = true,
    ) {}

    public static function isSafeSlug(string $slug): bool
    {
        return preg_match(self::SLUG_PATTERN, $slug) === 1;
    }

    public function config(): DocsConfig
    {
        return $this->config ??= DocsConfig::fromArray(
            $this->manifest(),
            Value::strings(config()->array('portal-docs.contextual', ['copy', 'view', 'chatgpt', 'claude'])),
        );
    }

    public function prefix(): string
    {
        return $this->prefix;
    }

    /**
     * @return list<array{tab: string, groups: list<array{group: string, pages: list<DocsPage>}>}>
     */
    public function navigation(): array
    {
        return $this->navigation ??= $this->buildNavigation();
    }

    /**
     * Shape sent to Inertia: DocsPage flattened, ready for the sidebar.
     *
     * @return list<array{tab: string, groups: list<array{group: string, pages: list<array<string, mixed>>}>}>
     */
    public function navigationArray(): array
    {
        return array_map(fn (array $tab): array => [
            'tab' => $tab['tab'],
            'groups' => array_map(fn (array $group): array => [
                'group' => $group['group'],
                'pages' => array_map(fn (DocsPage $page): array => $page->toArray($this->prefix), $group['pages']),
            ], $tab['groups']),
        ], $this->navigation());
    }

    /**
     * @return list<DocsPage>
     */
    public function pages(): array
    {
        $pages = [];

        foreach ($this->navigation() as $tab) {
            foreach ($tab['groups'] as $group) {
                foreach ($group['pages'] as $page) {
                    $pages[] = $page;
                }
            }
        }

        return $pages;
    }

    public function find(string $slug): ?DocsPage
    {
        foreach ($this->pages() as $page) {
            if ($page->slug === $slug) {
                return $page;
            }
        }

        return null;
    }

    public function previous(string $slug): ?DocsPage
    {
        $pages = $this->pages();
        $index = $this->indexOf($slug, $pages);

        return $index !== null && $index > 0 ? $pages[$index - 1] : null;
    }

    public function next(string $slug): ?DocsPage
    {
        $pages = $this->pages();
        $index = $this->indexOf($slug, $pages);

        return $index !== null && isset($pages[$index + 1]) ? $pages[$index + 1] : null;
    }

    public function firstSlug(): ?string
    {
        return $this->pages()[0]->slug ?? null;
    }

    public function firstEndpointSlug(): ?string
    {
        foreach ($this->pages() as $page) {
            if ($page->kind === PageKind::Endpoint) {
                return $page->slug;
            }
        }

        return null;
    }

    public function openApi(): ?OpenApiDocument
    {
        if (!$this->openApiEnabled) {
            return null;
        }

        return $this->openApi ??= new OpenApiDocument($this->specLoader->load());
    }

    /**
     * Raw markdown of one page: the .mdx body without frontmatter, or the generated
     * endpoint document.
     */
    public function markdown(string $slug): ?string
    {
        $page = $this->find($slug);

        if (!$page instanceof DocsPage) {
            return null;
        }

        if ($page->kind === PageKind::Endpoint) {
            return $this->openApi()?->markdown($slug);
        }

        $contents = $this->read($slug);

        if ($contents === null) {
            return null;
        }

        $body = FrontMatter::strip($contents);
        $heading = '# '.$page->title;
        $description = $page->description === null ? '' : "\n".$page->description."\n";

        return $heading."\n".$description."\n".$body."\n";
    }

    /**
     * Index of the whole portal, in the llms.txt convention.
     */
    public function llmsIndex(string $baseUrl): string
    {
        $config = $this->config();
        $lines = ['# '.$config->name, ''];

        if ($config->description !== null) {
            $lines[] = '> '.$config->description;
            $lines[] = '';
        }

        foreach ($this->navigation() as $tab) {
            foreach ($tab['groups'] as $group) {
                $lines[] = '## '.$group['group'];
                $lines[] = '';

                foreach ($group['pages'] as $page) {
                    $url = mb_rtrim($baseUrl, '/').$page->href($this->prefix).'.md';
                    $line = '- ['.$page->title.']('.$url.')';

                    if ($page->description !== null && $page->description !== '') {
                        $line .= ': '.$page->description;
                    }

                    $lines[] = $line;
                }

                $lines[] = '';
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Every page concatenated, for a model that wants the whole portal in one request.
     */
    public function llmsFull(string $baseUrl): string
    {
        $config = $this->config();
        $blocks = ['# '.$config->name];

        if ($config->description !== null) {
            $blocks[] = '> '.$config->description;
        }

        foreach ($this->pages() as $page) {
            $markdown = $this->markdown($page->slug);

            if ($markdown === null) {
                continue;
            }

            $blocks[] = 'Source: '.mb_rtrim($baseUrl, '/').$page->href($this->prefix)."\n\n".mb_trim($markdown);
        }

        return implode("\n\n---\n\n", $blocks)."\n";
    }

    /**
     * Timestamps of everything the navigation is derived from; the cache key hangs off this.
     */
    public function fingerprint(): string
    {
        $paths = [$this->contentPath.'/docs.json', ...$this->mdxFiles()];
        $stamps = array_map(static fn (string $path): string => $path.':'.(@filemtime($path) ?: 0), $paths);

        return md5($this->contentPath.'|'.implode('|', $stamps));
    }

    public function read(string $slug): ?string
    {
        $path = $this->contentPath.'/'.$slug.'.mdx';

        if (!self::isSafeSlug($slug) || !is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);

        return $contents === false ? null : $contents;
    }

    /**
     * @param  list<DocsPage>  $pages
     */
    private function indexOf(string $slug, array $pages): ?int
    {
        foreach ($pages as $index => $page) {
            if ($page->slug === $slug) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function mdxFiles(): array
    {
        $files = glob($this->contentPath.'/{,*/,*/*/}*.mdx', GLOB_BRACE);

        return $files === false ? [] : $files;
    }

    /**
     * @return array<string, mixed>
     */
    private function manifest(): array
    {
        $path = $this->contentPath.'/docs.json';

        if (!is_file($path)) {
            return [];
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return [];
        }

        return Value::map(json_decode($contents, associative: true));
    }

    /**
     * @return list<array{tab: string, groups: list<array{group: string, pages: list<DocsPage>}>}>
     */
    private function buildNavigation(): array
    {
        $manifest = $this->manifest();
        $navigation = is_array($manifest['navigation'] ?? null) ? $manifest['navigation'] : [];
        $tabs = is_array($navigation['tabs'] ?? null) ? $navigation['tabs'] : [];

        $resolved = [];

        foreach ($tabs as $tab) {
            if (!is_array($tab) || !is_string($tab['tab'] ?? null)) {
                continue;
            }

            $groups = $this->mdxGroups(Value::map($tab), $tab['tab']);

            if (($tab['openapi'] ?? false) === true) {
                $groups = [...$groups, ...$this->endpointGroups($tab['tab'])];
            }

            if ($groups === []) {
                continue;
            }

            $resolved[] = ['tab' => $tab['tab'], 'groups' => $groups];
        }

        return $resolved;
    }

    /**
     * @param  array<string, mixed>  $tab
     * @return list<array{group: string, pages: list<DocsPage>}>
     */
    private function mdxGroups(array $tab, string $tabName): array
    {
        $groups = is_array($tab['groups'] ?? null) ? $tab['groups'] : [];
        $resolved = [];

        foreach ($groups as $group) {
            if (!is_array($group) || !is_string($group['group'] ?? null)) {
                continue;
            }

            $slugs = is_array($group['pages'] ?? null) ? $group['pages'] : [];
            $pages = [];

            foreach ($slugs as $slug) {
                if (!is_string($slug)) {
                    continue;
                }

                $page = $this->mdxPage($slug, $group['group'], $tabName);

                if ($page instanceof DocsPage) {
                    $pages[] = $page;
                }
            }

            if ($pages !== []) {
                $resolved[] = ['group' => $group['group'], 'pages' => $pages];
            }
        }

        return $resolved;
    }

    private function mdxPage(string $slug, string $group, string $tab): ?DocsPage
    {
        $contents = $this->read($slug);

        if ($contents === null) {
            return null;
        }

        $attributes = FrontMatter::attributes($contents);
        $title = is_string($attributes['title'] ?? null) ? $attributes['title'] : $slug;
        $sidebarTitle = is_string($attributes['sidebarTitle'] ?? null) ? $attributes['sidebarTitle'] : $title;

        return new DocsPage(
            slug: $slug,
            title: $title,
            sidebarTitle: $sidebarTitle,
            description: is_string($attributes['description'] ?? null) ? $attributes['description'] : null,
            group: $group,
            tab: $tab,
            kind: PageKind::Mdx,
            file: $slug.'.mdx',
            icon: is_string($attributes['icon'] ?? null) ? $attributes['icon'] : null,
            mode: is_string($attributes['mode'] ?? null) ? $attributes['mode'] : null,
        );
    }

    /**
     * @return list<array{group: string, pages: list<DocsPage>}>
     */
    private function endpointGroups(string $tab): array
    {
        $document = $this->openApi();

        if (!$document instanceof OpenApiDocument) {
            return [];
        }

        $resolved = [];

        foreach ($document->groups() as $group) {
            $pages = array_map(fn (array $page): DocsPage => new DocsPage(
                slug: $page['slug'],
                title: $page['title'],
                sidebarTitle: $page['title'],
                description: null,
                group: $group['group'],
                tab: $tab,
                kind: PageKind::Endpoint,
                method: mb_strtoupper($page['method']),
                deprecated: $page['deprecated'],
            ), $group['pages']);

            if ($pages !== []) {
                $resolved[] = ['group' => $group['group'], 'pages' => $pages];
            }
        }

        return $resolved;
    }
}
