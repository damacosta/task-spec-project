<?php

declare(strict_types=1);

use He4rt\PortalDocs\OpenApi\OpenApiDocument;
use He4rt\PortalDocs\OpenApi\SpecLoader;

/*
 * These run Scramble for real against the host API. They are the only tests that do,
 * and they are what proves the annotations in app/Http/Api still produce the document
 * the portal renders.
 */

function hostDocument(): OpenApiDocument
{
    return new OpenApiDocument(resolve(SpecLoader::class)->load());
}

it('documents every team endpoint under one tag', function (): void {
    $groups = hostDocument()->groups();

    expect($groups)->toHaveCount(1)
        ->and($groups[0]['group'])->toBe('Team')
        ->and(array_column($groups[0]['pages'], 'title'))->toBe([
            'List teams',
            'Create a team',
            'Show a team',
            'Update a team',
            'Delete a team',
        ]);
});

it('applies the bearer scheme documented by the transformer', function (): void {
    $security = hostDocument()->endpoint('api/team/post-teams')['security'];

    expect($security[0]['key'])->toBe('bearerAuth')
        ->and($security[0]['scheme'])->toBe('bearer')
        ->and($security[0]['description'])->toBe('A personal access token sent as `Authorization: Bearer <token>`.');
});

it('turns the form request rules into documented body fields', function (): void {
    $fields = collect(hostDocument()->endpoint('api/team/post-teams')['body']['fields'])->keyBy('name');

    expect($fields['name']['required'])->toBeTrue()
        ->and($fields['name']['constraints']['maxLength'])->toBe(255)
        ->and($fields['name']['example'])->toBe('He4rt Developers')
        ->and($fields['slug']['constraints']['pattern'])->toBe('^[a-z0-9]+(-[a-z0-9]+)*$')
        ->and($fields['contact_email']['type'])->toBe('string<email>')
        ->and($fields['status']['required'])->toBeFalse()
        ->and($fields['status']['default'])->toBe('active');
});

it('carries the enum case descriptions from the TeamStatus phpdoc', function (): void {
    $status = collect(hostDocument()->endpoint('api/team/post-teams')['body']['fields'])->firstWhere('name', 'status');

    expect($status['enum'])->toBe(['active', 'suspended', 'archived'])
        ->and($status['enumDescriptions']['suspended'])
        ->toBe('Access is blocked while an incident is reviewed; the team can go back to active.');
});

it('describes the shared error responses instead of the generator defaults', function (): void {
    $responses = collect(hostDocument()->endpoint('api/team/post-teams')['responses'])->keyBy('status');

    expect($responses[422]['description'])->toBe('The payload failed validation. `errors` holds one list of messages per field.')
        ->and($responses[401]['description'])->toBe('The request carries no valid credentials.');
});

it('documents the delete endpoint as a 204 with no body', function (): void {
    $responses = collect(hostDocument()->endpoint('api/team/delete-teams-team')['responses'])->keyBy('status');

    expect($responses[204]['fields'])->toBe([])
        ->and($responses[404]['description'])->toBe('No record matches the identifier in the path.');
});

it('documents the query parameters declared by attributes', function (): void {
    $query = collect(hostDocument()->endpoint('api/team/get-teams')['parameters']['query'])->keyBy('name');

    expect($query->keys()->all())->toBe(['page', 'per_page', 'status', 'search'])
        ->and($query['page']['default'])->toBe(1)
        ->and($query['page']['example'])->toBe(2)
        ->and($query['status']['enum'])->toBe(['active', 'suspended', 'archived']);
});
