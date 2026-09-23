<?php

declare(strict_types=1);

use He4rt\PortalDocs\OpenApi\JsonSchema;

function fixtureSchema(): JsonSchema
{
    $spec = json_decode((string) file_get_contents(__DIR__.'/../Fixtures/openapi.json'), associative: true);

    return new JsonSchema(is_array($spec) ? $spec : []);
}

it('follows a $ref to its target schema', function (): void {
    $resolved = fixtureSchema()->resolve(['$ref' => '#/components/schemas/PostStatus']);

    expect($resolved['enum'])->toBe(['draft', 'published'])
        ->and($resolved['default'])->toBe('draft');
});

it('returns an empty schema for a $ref that points nowhere', function (): void {
    expect(fixtureSchema()->resolve(['$ref' => '#/components/schemas/Missing']))->toBeEmpty();
});

it('merges properties and required across allOf', function (): void {
    $merged = fixtureSchema()->resolve([
        'allOf' => [
            ['$ref' => '#/components/schemas/Author'],
            ['type' => 'object', 'required' => ['email']],
        ],
    ]);

    expect(array_keys($merged['properties']))->toBe(['name', 'email'])
        ->and($merged['required'])->toBe(['email']);
});

it('labels a nullable union as "type | null"', function (): void {
    $label = fixtureSchema()->typeLabel(['type' => ['string', 'null'], 'format' => 'date-time']);

    expect($label)->toBe('string | null');
});

it('labels an enum by its base type', function (): void {
    expect(fixtureSchema()->typeLabel(['$ref' => '#/components/schemas/PostStatus']))->toBe('enum<string>');
});

it('labels an array of objects as object[]', function (): void {
    $label = fixtureSchema()->typeLabel([
        'type' => 'array',
        'items' => ['$ref' => '#/components/schemas/Author'],
    ]);

    expect($label)->toBe('object[]');
});

it('turns additionalProperties into a {key} field', function (): void {
    $fields = fixtureSchema()->fields([
        'type' => 'object',
        'additionalProperties' => ['type' => 'string', 'description' => 'Free-form value.'],
    ]);

    expect($fields)->toHaveCount(1)
        ->and($fields[0]['name'])->toBe('{key}')
        ->and($fields[0]['description'])->toBe('Free-form value.');
});

it('prefers a declared example over every fallback', function (): void {
    $schema = fixtureSchema();

    expect($schema->example(['type' => 'string', 'example' => 'declared']))->toBe('declared')
        ->and($schema->example(['type' => 'string', 'examples' => ['from-list']]))->toBe('from-list')
        ->and($schema->example(['type' => 'string', 'default' => 'from-default']))->toBe('from-default')
        ->and($schema->example(['$ref' => '#/components/schemas/PostStatus']))->toBe('draft');
});

it('falls back by format only when the spec declares no example', function (): void {
    $schema = fixtureSchema();

    expect($schema->example(['type' => 'string', 'format' => 'uuid']))->toBe('9f8c2b1a-4d3e-4f5a-8b6c-7d8e9f0a1b2c')
        ->and($schema->example(['type' => 'string', 'format' => 'email']))->toBe('user@example.com')
        ->and($schema->example(['type' => 'string', 'format' => 'date-time']))->toBe('2026-09-22T10:00:00Z')
        ->and($schema->example(['type' => 'integer']))->toBe(123)
        ->and($schema->example(['type' => 'boolean']))->toBeTrue()
        ->and($schema->example(['type' => 'string']))->toBe('<string>');
});

it('nests object children under the parent field', function (): void {
    $fields = fixtureSchema()->fields(['$ref' => '#/components/schemas/Post']);
    $author = collect($fields)->firstWhere('name', 'author');

    expect($author['children'])->toHaveCount(2)
        ->and(array_column($author['children'], 'name'))->toBe(['name', 'email']);
});

it('collects the constraints the field list renders', function (): void {
    $field = fixtureSchema()->field('title', [
        'type' => 'string',
        'maxLength' => 255,
        'pattern' => '^[a-z]+$',
    ], required: true);

    expect($field['constraints'])->toBe(['maxLength' => 255, 'pattern' => '^[a-z]+$'])
        ->and($field['required'])->toBeTrue();
});

it('carries enum descriptions through to the field', function (): void {
    $field = fixtureSchema()->field('status', ['$ref' => '#/components/schemas/PostStatus'], required: false);

    expect($field['enum'])->toBe(['draft', 'published'])
        ->and($field['enumDescriptions'])->toBe([
            'draft' => 'Only the author sees it.',
            'published' => 'Visible to everyone.',
        ]);
});

it('stops recursion at the depth limit instead of looping forever', function (): void {
    $schema = new JsonSchema([
        'components' => [
            'schemas' => [
                'Node' => [
                    'type' => 'object',
                    'properties' => [
                        'child' => ['$ref' => '#/components/schemas/Node'],
                    ],
                ],
            ],
        ],
    ]);

    $fields = $schema->fields(['$ref' => '#/components/schemas/Node']);
    $depth = 0;

    while ($fields !== [] && $fields[0]['children'] !== null) {
        $fields = $fields[0]['children'];
        $depth++;
    }

    expect($depth)->toBeLessThanOrEqual(6);
});
