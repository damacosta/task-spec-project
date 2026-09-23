<?php

declare(strict_types=1);

use He4rt\PortalDocs\OpenApi\RequestExamples;
use Illuminate\Support\Facades\Http;

it('renders a GET without a body as a curl command with no --data', function (): void {
    $examples = RequestExamples::build(
        method: 'get',
        url: 'https://fixture.test/api/posts',
        headers: [['name' => 'X-Sandbox-Scenario', 'example' => 'success']],
        security: [['key' => 'bearerAuth', 'name' => null, 'type' => 'http', 'scheme' => 'bearer']],
        body: null,
        contentType: null,
    );

    expect($examples['curl'])->toContain('curl --request GET')
        ->and($examples['curl'])->toContain("--header 'Authorization: Bearer <token>'")
        ->and($examples['curl'])->toContain("--header 'X-Sandbox-Scenario: success'")
        ->and($examples['curl'])->not->toContain('--data')
        ->and($examples['curl'])->not->toEndWith('\\');
});

it('sends the declared body as pretty JSON in every language', function (): void {
    $examples = RequestExamples::build(
        method: 'post',
        url: 'https://fixture.test/api/posts',
        headers: [],
        security: [],
        body: ['title' => 'Shipping the docs portal', 'status' => 'draft'],
        contentType: 'application/json',
    );

    expect($examples['curl'])->toContain('--data')
        ->and($examples['curl'])->toContain('"title": "Shipping the docs portal"')
        ->and($examples['javascript'])->toContain("method: 'POST'")
        ->and($examples['javascript'])->toContain('JSON.stringify(')
        ->and($examples['php'])->toContain(Http::class)
        ->and($examples['php'])->toContain("'title' => 'Shipping the docs portal'");
});

it('turns an apiKey scheme into its header', function (): void {
    $examples = RequestExamples::build(
        method: 'get',
        url: 'https://fixture.test/api/users',
        headers: [],
        security: [['key' => 'apiKeyAuth', 'name' => 'x-api-key', 'type' => 'apiKey', 'in' => 'header', 'scheme' => null]],
        body: null,
        contentType: null,
    );

    expect($examples['curl'])->toContain("--header 'x-api-key: <api-key>'");
});

it('always asks for a JSON response', function (): void {
    $examples = RequestExamples::build('get', 'https://fixture.test/api/posts', [], [], body: null, contentType: null);

    expect($examples['curl'])->toContain("--header 'Accept: application/json'");
});
