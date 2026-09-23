<?php

declare(strict_types=1);

use He4rt\PortalDocs\Docs\DocsPage;
use He4rt\PortalDocs\Docs\DocsSite;
use He4rt\PortalDocs\Docs\PageKind;
use He4rt\PortalDocs\OpenApi\ArraySpecLoader;

function fixtureSite(bool $openApi = true): DocsSite
{
    $spec = json_decode((string) file_get_contents(__DIR__.'/../Fixtures/openapi.json'), associative: true);

    return new DocsSite(
        contentPath: __DIR__.'/../Fixtures/site',
        specLoader: new ArraySpecLoader(is_array($spec) ? $spec : []),
        prefix: 'docs',
        openApiEnabled: $openApi,
    );
}

it('reads the site config from docs.json', function (): void {
    $config = fixtureSite()->config();

    expect($config->name)->toBe('Fixture Docs')
        ->and($config->description)->toBe('A fixture site used by the portal-docs test suite.')
        ->and($config->colors['primary'])->toBe('#B45309')
        ->and($config->appearance)->toBe('system')
        ->and($config->contextual)->toBe(['copy', 'view', 'claude'])
        ->and($config->anchors)->toBe([
            ['anchor' => 'Support', 'href' => 'mailto:support@example.com', 'icon' => 'life-buoy'],
        ]);
});

it('keeps title and sidebarTitle as separate fields', function (): void {
    $page = fixtureSite()->find('flows/create');

    expect($page)->not->toBeNull()
        ->and($page->title)->toBe('Create a post')
        ->and($page->sidebarTitle)->toBe('Create')
        ->and($page->description)->toBe('How a post gets created.')
        ->and($page->mode)->toBe('wide');
});

it('falls back to the title when no sidebarTitle is declared', function (): void {
    $page = fixtureSite()->find('get-started');

    expect($page->title)->toBe('Get Started')
        ->and($page->sidebarTitle)->toBe('Get Started')
        ->and($page->icon)->toBe('rocket');
});

it('drops unsafe slugs and missing files from the navigation', function (): void {
    $slugs = array_map(fn (DocsPage $page): string => $page->slug, fixtureSite()->pages());

    expect($slugs)->not->toContain('../secret')
        ->and($slugs)->not->toContain('flows/missing');
});

it('ignores a page that exists on disk but is not listed in docs.json', function (): void {
    $site = fixtureSite();

    expect($site->find('secret'))->toBeNull()
        ->and($site->llmsIndex('https://example.test'))->not->toContain('Secret');
});

it('appends one endpoint group per tag after the declared mdx groups', function (): void {
    $tabs = fixtureSite()->navigation();
    $apiTab = collect($tabs)->firstWhere('tab', 'API reference');
    $groups = array_map(fn (array $group): string => $group['group'], $apiTab['groups']);

    expect($groups)->toBe(['API documentation', 'Post', 'User']);
});

it('leaves an openapi tab with only its mdx groups when the spec is disabled', function (): void {
    $tabs = fixtureSite(openApi: false)->navigation();
    $apiTab = collect($tabs)->firstWhere('tab', 'API reference');
    $groups = array_map(fn (array $group): string => $group['group'], $apiTab['groups']);

    expect($groups)->toBe(['API documentation']);
});

it('marks endpoint pages with their method and deprecation', function (): void {
    $page = fixtureSite()->find('api/post/delete-posts-post');

    expect($page)->not->toBeNull()
        ->and($page->kind)->toBe(PageKind::Endpoint)
        ->and($page->method)->toBe('DELETE')
        ->and($page->deprecated)->toBeTrue()
        ->and($page->title)->toBe('Delete a post');
});

it('walks previous and next across groups and tabs', function (): void {
    $site = fixtureSite();
    $slugs = array_map(fn (DocsPage $page): string => $page->slug, $site->pages());

    expect($slugs[0])->toBe('get-started')
        ->and($site->previous('get-started'))->toBeNull()
        ->and($site->next('flows/create')->slug)->toBe('reference/errors')
        ->and($site->previous('reference/errors')->slug)->toBe('flows/create');
});

it('answers the first slug of each tab', function (): void {
    $site = fixtureSite();

    expect($site->firstSlug())->toBe('get-started')
        ->and($site->firstEndpointSlug())->toBe('api/post/get-posts');
});

it('builds hrefs from the configured prefix', function (): void {
    $site = new DocsSite(
        contentPath: __DIR__.'/../Fixtures/site',
        specLoader: new ArraySpecLoader([]),
        prefix: 'manual',
        openApiEnabled: false,
    );

    expect($site->find('get-started')->href('manual'))->toBe('/manual/get-started');
});
