<?php

declare(strict_types=1);

use He4rt\PortalDocs\Docs\DocsSite;
use He4rt\PortalDocs\Docs\NavigationCache;
use He4rt\PortalDocs\OpenApi\SpecLoader;
use He4rt\PortalDocs\Tests\Support\FixtureSite;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\artisan;
use function Pest\Laravel\get;

/** Counts how often the spec is actually generated. */
function countingLoader(): object
{
    return new class implements SpecLoader
    {
        public int $calls = 0;

        public function load(): array
        {
            $this->calls++;

            return FixtureSite::spec();
        }
    };
}

function siteWithLoader(SpecLoader $loader, bool $cacheEnabled = true): DocsSite
{
    config()->set('portal-docs.content_path', FixtureSite::contentPath());
    config()->set('portal-docs.cache.enabled', $cacheEnabled);

    $site = new DocsSite(
        contentPath: FixtureSite::contentPath(),
        specLoader: $loader,
        prefix: 'docs',
        cache: new NavigationCache(enabled: $cacheEnabled, ttl: 86_400, version: null),
    );

    app()->instance(DocsSite::class, $site);

    return $site;
}

beforeEach(function (): void {
    Cache::flush();
    FixtureSite::use();
});

it('generates the spec once and serves the rest from cache', function (): void {
    $loader = countingLoader();
    siteWithLoader($loader);

    get('/docs/api/post/post-posts')->assertOk();
    get('/docs/api/post/get-posts')->assertOk();
    get('/docs/api/post/delete-posts-post')->assertOk();

    expect($loader->calls)->toBe(1);
});

it('never touches the spec loader once portal-docs:cache has run', function (): void {
    siteWithLoader(countingLoader());
    artisan('portal-docs:cache')->assertSuccessful();

    // A fresh request builds its own DocsSite; the warm cache has to carry it.
    $loader = countingLoader();
    siteWithLoader($loader);

    get('/docs/api/post/post-posts')->assertOk();

    expect($loader->calls)->toBe(0);
});

it('reflects an edited page without a cache clear', function (): void {
    $site = siteWithLoader(countingLoader());

    expect($site->find('get-started')->title)->toBe('Get Started');

    $path = FixtureSite::contentPath().'/get-started.mdx';
    $original = (string) file_get_contents($path);

    try {
        file_put_contents($path, str_replace('title: Get Started', 'title: Começar agora', $original));
        touch($path, Date::now()->getTimestamp() + 5);

        expect(siteWithLoader(countingLoader())->find('get-started')->title)->toBe('Começar agora');
    } finally {
        file_put_contents($path, $original);
    }
});

it('skips the cache entirely when it is disabled', function (): void {
    // Each request builds its own DocsSite, so in-memory memoisation does not carry over:
    // with the cache off, every one of them regenerates the spec from scratch.
    $first = countingLoader();
    siteWithLoader($first, cacheEnabled: false)->pages();

    $second = countingLoader();
    siteWithLoader($second, cacheEnabled: false)->pages();

    expect($first->calls)->toBe(1)
        ->and($second->calls)->toBe(1);
});

it('clears what it warmed', function (): void {
    siteWithLoader(countingLoader());
    artisan('portal-docs:cache')->assertSuccessful();

    $loader = countingLoader();
    siteWithLoader($loader);
    artisan('portal-docs:cache', ['--clear' => true])->assertSuccessful();

    $counted = countingLoader();
    siteWithLoader($counted);
    get('/docs/api/post/post-posts')->assertOk();

    expect($counted->calls)->toBe(1);
});
