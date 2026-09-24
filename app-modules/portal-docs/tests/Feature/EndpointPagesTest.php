<?php

declare(strict_types=1);

use He4rt\PortalDocs\OpenApi\OpenApiDocument;

function fixtureDocument(): OpenApiDocument
{
    $spec = json_decode((string) file_get_contents(__DIR__.'/../Fixtures/openapi.json'), associative: true);

    return new OpenApiDocument(is_array($spec) ? $spec : []);
}

it('separates path segments before slugging an endpoint', function (): void {
    expect(OpenApiDocument::slugFor('Post', 'delete', '/posts/{post}'))->toBe('api/post/delete-posts-post')
        ->and(OpenApiDocument::slugFor('Post', 'put', '/posts/{post}'))->toBe('api/post/put-posts-post');
});

it('derives a title from the operationId when no summary exists', function (): void {
    $endpoint = fixtureDocument()->endpoint('api/post/get-posts');

    expect($endpoint['title'])->toBe('List posts');
});

it('prefers the declared summary over the derived title', function (): void {
    expect(fixtureDocument()->endpoint('api/post/post-posts')['title'])->toBe('Create a post');
});

it('builds the full request url from the spec server', function (): void {
    $endpoint = fixtureDocument()->endpoint('api/post/post-posts');

    expect($endpoint['server'])->toBe('https://fixture.test/api')
        ->and($endpoint['url'])->toBe('https://fixture.test/api/posts')
        ->and($endpoint['method'])->toBe('POST');
});

it('groups parameters by where they travel', function (): void {
    $endpoint = fixtureDocument()->endpoint('api/post/get-posts');

    expect(array_column($endpoint['parameters']['query'], 'name'))->toBe(['page'])
        ->and(array_column($endpoint['parameters']['header'], 'name'))->toBe(['X-Sandbox-Scenario'])
        ->and($endpoint['parameters']['path'])->toBe([]);
});

it('keeps the parameter description and example from the spec', function (): void {
    $header = fixtureDocument()->endpoint('api/post/get-posts')['parameters']['header'][0];

    expect($header['description'])->toBe('Forces a sandbox outcome.')
        ->and($header['example'])->toBe('success')
        ->and($header['enum'])->toBe(['success', 'failure'])
        ->and($header['enumDescriptions'])->toBe([
            'success' => 'The call succeeds.',
            'failure' => 'The call fails with 422.',
        ]);
});

it('resolves the request body with its constraints and nested fields', function (): void {
    $body = fixtureDocument()->endpoint('api/post/post-posts')['body'];

    expect($body['contentType'])->toBe('application/json')
        ->and($body['required'])->toBeTrue();

    $fields = collect($body['fields'])->keyBy('name');

    expect($fields['title']['required'])->toBeTrue()
        ->and($fields['title']['constraints'])->toBe(['maxLength' => 255])
        ->and($fields['title']['example'])->toBe('Shipping the docs portal')
        ->and($fields['status']['type'])->toBe('enum<string>')
        ->and($fields['status']['default'])->toBe('draft')
        ->and($fields['metadata']['children'][0]['name'])->toBe('{key}')
        ->and(array_column($fields['author']['children'], 'name'))->toBe(['name', 'email']);
});

it('never renders a placeholder where the spec declares an example', function (): void {
    $endpoint = fixtureDocument()->endpoint('api/post/post-posts');
    $example = $endpoint['body']['example'];

    expect($example['title'])->toBe('Shipping the docs portal')
        ->and($example['status'])->toBe('draft')
        ->and($example['author']['name'])->toBe('Ada Lovelace')
        ->and($example['author']['email'])->toBe('user@example.com')
        ->and($endpoint['requestExamples']['curl'])->toContain('"title": "Shipping the docs portal"');
});

it('marks a field the spec left without an example', function (): void {
    $example = fixtureDocument()->endpoint('api/post/post-posts')['body']['example'];

    expect($example['metadata']['{key}'])->toBe('<string>');
});

it('lists every response with its status and fields', function (): void {
    $responses = collect(fixtureDocument()->endpoint('api/post/post-posts')['responses'])->keyBy('status');

    expect($responses->keys()->all())->toBe([201, 422])
        ->and($responses['201']['description'])->toBe('The created post.')
        ->and(array_column($responses['201']['fields'], 'name'))->toBe(['id', 'title', 'status', 'published_at', 'author'])
        ->and($responses['422']['description'])->toBe('The payload failed validation.');
});

it('handles a response with no body', function (): void {
    $responses = fixtureDocument()->endpoint('api/post/delete-posts-post')['responses'];

    expect($responses[0]['status'])->toBe('204')
        ->and($responses[0]['fields'])->toBe([])
        ->and($responses[0]['contentType'])->toBeNull();
});

it('applies the global security requirement and lets an operation override it', function (): void {
    $document = fixtureDocument();

    expect($document->endpoint('api/post/post-posts')['security'][0]['key'])->toBe('bearerAuth')
        ->and($document->endpoint('api/post/post-posts')['security'][0]['description'])->toBe('A personal access token.')
        ->and($document->endpoint('api/user/get-users')['security'][0]['key'])->toBe('apiKeyAuth')
        ->and($document->endpoint('api/user/get-users')['security'][0]['name'])->toBe('x-api-key');
});

it('puts the authorization header in the curl example', function (): void {
    $curl = fixtureDocument()->endpoint('api/post/post-posts')['requestExamples']['curl'];

    expect($curl)->toContain("--header 'Authorization: Bearer <token>'")
        ->and($curl)->toContain("--header 'Content-Type: application/json'")
        ->and($curl)->toContain('--data');
});

it('renders the endpoint as markdown with one heading per section', function (): void {
    $markdown = fixtureDocument()->markdown('api/post/post-posts');

    expect($markdown)->toContain('# Create a post')
        ->and($markdown)->toContain('> POST https://fixture.test/api/posts')
        ->and($markdown)->toContain('## Authorizations')
        ->and($markdown)->toContain('## Body (application/json)')
        ->and($markdown)->toContain('## Response 201')
        ->and($markdown)->toContain('- `title` (string, required): Headline of the post.');
});

it('returns null for an endpoint the spec does not declare', function (): void {
    expect(fixtureDocument()->endpoint('api/post/get-nothing'))->toBeNull()
        ->and(fixtureDocument()->markdown('api/post/get-nothing'))->toBeNull()
        ->and(fixtureDocument()->has('api/post/get-nothing'))->toBeFalse();
});
