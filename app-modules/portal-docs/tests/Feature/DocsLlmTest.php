<?php

declare(strict_types=1);

use He4rt\PortalDocs\Tests\Support\FixtureSite;

use function Pest\Laravel\get;

beforeEach(fn () => FixtureSite::use());

it('serves an index grouped the way the sidebar is', function (): void {
    $response = get('/docs/llms.txt');

    $response->assertOk()->assertHeader('Content-Type', 'text/plain; charset=utf-8');

    $body = $response->getContent();

    expect($body)->toStartWith('# Fixture Docs')
        ->and($body)->toContain('> A fixture site used by the portal-docs test suite.')
        ->and($body)->toContain('## Get Started')
        ->and($body)->toContain('## Flows')
        ->and($body)->toContain(']('.url('/docs/get-started.md').'): The first page of the fixture site.')
        ->and($body)->toContain('## Post');
});

it('lists endpoint pages in the index too', function (): void {
    expect(get('/docs/llms.txt')->getContent())
        ->toContain('- [Create a post]('.url('/docs/api/post/post-posts.md').')');
});

it('leaves an unlisted page out of the index', function (): void {
    expect(get('/docs/llms.txt')->getContent())->not->toContain('Secret');
});

it('concatenates every page in llms-full', function (): void {
    $body = get('/docs/llms-full.txt')->assertOk()->getContent();

    expect($body)->toContain('Source: '.url('/docs/get-started'))
        ->and($body)->toContain('Source: '.url('/docs/api/post/post-posts'))
        ->and($body)->toContain('# Create a post')
        ->and($body)->toContain('> POST https://fixture.test/api/posts');
});

it('serves one page as raw markdown behind the index blockquote', function (): void {
    $response = get('/docs/flows/create.md');

    $response->assertOk()->assertHeader('Content-Type', 'text/markdown; charset=utf-8');

    $body = $response->getContent();

    expect($body)->toStartWith('> Documentation Index: '.url('/docs/llms.txt'))
        ->and($body)->toContain('# Create a post')
        ->and($body)->toContain('How a post gets created.')
        ->and($body)->toContain('<Note>Posts start as drafts.</Note>')
        ->and($body)->not->toContain('sidebarTitle:');
});

it('serves an endpoint as markdown', function (): void {
    $body = get('/docs/api/post/post-posts.md')->assertOk()->getContent();

    expect($body)->toContain('# Create a post')
        ->and($body)->toContain('> POST https://fixture.test/api/posts')
        ->and($body)->toContain('## Authorizations')
        ->and($body)->toContain('## Body (application/json)')
        ->and($body)->toContain('## Response 201');
});

it('answers 404 for markdown of a page outside the navigation', function (): void {
    get('/docs/secret.md')->assertNotFound();
});
