<?php

declare(strict_types=1);

use He4rt\PortalDocs\Docs\DocsSite;
use He4rt\PortalDocs\Tests\Support\FixtureSite;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\get;

beforeEach(fn () => FixtureSite::use());

it('registers every documented route under the configured prefix', function (): void {
    $names = collect(Route::getRoutes())
        ->map(fn ($route): ?string => $route->getName())
        ->filter(fn (?string $name): bool => $name !== null && str_starts_with($name, 'portal-docs.'))
        ->values();

    expect($names->all())->toEqualCanonicalizing([
        'portal-docs.index',
        'portal-docs.llms',
        'portal-docs.llms-full',
        'portal-docs.api',
        'portal-docs.markdown',
        'portal-docs.page',
    ]);
});

it('keeps docs/api for the portal instead of the Scramble ui', function (): void {
    $route = Route::getRoutes()->getByName('portal-docs.api');

    expect($route)->not->toBeNull()
        ->and($route->uri())->toBe('docs/api')
        ->and(Route::getRoutes()->getByName('scramble.docs.ui'))->toBeNull();
});

it('redirects the root to the first page', function (): void {
    get('/docs')->assertRedirect('/docs/get-started');
});

it('redirects the api tab to the first endpoint', function (): void {
    get('/docs/api')->assertRedirect('/docs/api/post/get-posts');
});

it('renders an mdx page through the module root view', function (): void {
    $response = get('/docs/flows/create');

    $response->assertOk()->assertSeeHtml('data-portal-docs')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Docs/Show')
            ->where('page.title', 'Create a post')
            ->where('page.sidebarTitle', 'Create')
            ->where('page.mode', 'wide')
            ->where('previous.slug', 'get-started')
            ->where('next.slug', 'reference/errors')
            ->where('config.name', 'Fixture Docs')
            ->has('navigation', 2)
        );
});

it('renders an endpoint page with its resolved props', function (): void {
    get('/docs/api/post/post-posts')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Docs/Endpoint')
            ->where('endpoint.method', 'POST')
            ->where('endpoint.title', 'Create a post')
            ->has('endpoint.body.fields', 4)
            ->has('endpoint.requestExamples.curl')
        );
});

it('answers 404 for a slug outside the navigation', function (): void {
    get('/docs/secret')->assertNotFound();
    get('/docs/flows/missing')->assertNotFound();
});

it('answers 200 to an inertia visit carrying the right version', function (): void {
    $response = get('/docs/get-started', [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => FixtureSite::inertiaVersion(),
    ]);

    $response->assertOk()
        ->assertHeader('X-Inertia', 'true')
        ->assertJsonPath('component', 'Docs/Show');
});

it('asks the client to reload when the inertia version is stale', function (): void {
    get('/docs/get-started', [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => 'stale-version',
    ])->assertConflict();
});

it('sends navigation and config only on the first visit', function (): void {
    $first = get('/docs/get-started', [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => FixtureSite::inertiaVersion(),
    ]);

    expect($first->json('props'))->toHaveKeys(['config', 'navigation']);

    $second = get('/docs/flows/create', [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => FixtureSite::inertiaVersion(),
        'X-Inertia-Except-Once-Props' => 'config,navigation',
    ]);

    expect($second->json('props'))->not->toHaveKey('navigation')
        ->and($second->json('props'))->not->toHaveKey('config');
});

it('serves the portal from a configurable prefix', function (): void {
    FixtureSite::use('manual');
    Route::getRoutes()->refreshNameLookups();

    expect(config()->string('portal-docs.prefix'))->toBe('manual')
        ->and(resolve(DocsSite::class)->find('get-started')->href('manual'))->toBe('/manual/get-started');
});
