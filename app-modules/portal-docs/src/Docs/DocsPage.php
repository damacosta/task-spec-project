<?php

declare(strict_types=1);

namespace He4rt\PortalDocs\Docs;

/**
 * One navigable entry of the portal: an .mdx page or a generated endpoint page.
 */
final readonly class DocsPage
{
    public function __construct(
        public string $slug,
        public string $title,
        public string $sidebarTitle,
        public ?string $description,
        public string $group,
        public string $tab,
        public PageKind $kind,
        public ?string $file = null,
        public ?string $method = null,
        public bool $deprecated = false,
        public ?string $icon = null,
        public ?string $mode = null,
    ) {}

    public function href(string $prefix): string
    {
        return '/'.mb_trim($prefix, '/').'/'.$this->slug;
    }

    /**
     * @return array{slug: string, title: string, sidebarTitle: string, description: string|null, group: string, tab: string, kind: string, method: string|null, deprecated: bool, icon: string|null, mode: string|null, href: string}
     */
    public function toArray(string $prefix): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'sidebarTitle' => $this->sidebarTitle,
            'description' => $this->description,
            'group' => $this->group,
            'tab' => $this->tab,
            'kind' => $this->kind->value,
            'method' => $this->method,
            'deprecated' => $this->deprecated,
            'icon' => $this->icon,
            'mode' => $this->mode,
            'href' => $this->href($prefix),
        ];
    }
}
