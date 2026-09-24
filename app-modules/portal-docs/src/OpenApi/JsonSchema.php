<?php

declare(strict_types=1);

namespace He4rt\PortalDocs\OpenApi;

use He4rt\PortalDocs\Support\Value;

/**
 * Turns an OpenAPI schema into the flat field list the endpoint page renders.
 *
 * Depth is capped at self::MAX_DEPTH: a self-referencing $ref (a comment with replies,
 * a team with teams) would otherwise recurse forever.
 */
final readonly class JsonSchema
{
    private const int MAX_DEPTH = 6;

    private const array COUNTING_CONSTRAINTS = ['minLength', 'maxLength', 'minItems', 'maxItems'];

    /**
     * @param  array<string, mixed>  $document  the whole spec, needed to follow $ref
     */
    public function __construct(private array $document) {}

    /**
     * @param  array<string, mixed>  $schema
     * @return list<array<string, mixed>>
     */
    public function fields(array $schema, int $depth = 0): array
    {
        $schema = $this->resolve($schema, $depth);

        if (($schema['type'] ?? null) === 'array' && is_array($schema['items'] ?? null)) {
            $schema = $this->resolve($schema['items'], $depth);
        }

        $properties = is_array($schema['properties'] ?? null) ? $schema['properties'] : [];
        $required = is_array($schema['required'] ?? null) ? $schema['required'] : [];

        $fields = [];

        foreach ($properties as $name => $property) {
            if (!is_string($name) || !is_array($property)) {
                continue;
            }

            $fields[] = $this->field($name, $property, in_array($name, $required, strict: true), $depth);
        }

        $additional = $schema['additionalProperties'] ?? null;

        if (is_array($additional) && $additional !== []) {
            $fields[] = $this->field('{key}', $additional, required: false, depth: $depth);
        }

        return $fields;
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    public function field(string $name, array $schema, bool $required, int $depth = 0): array
    {
        $schema = $this->resolve($schema, $depth);

        $enum = is_array($schema['enum'] ?? null) ? array_values($schema['enum']) : null;
        $enumDescriptions = is_array($schema['x-enumDescriptions'] ?? null) ? $schema['x-enumDescriptions'] : null;

        return [
            'name' => $name,
            'type' => $this->typeLabel($schema, $depth),
            'required' => $required,
            'deprecated' => (bool) ($schema['deprecated'] ?? false),
            'description' => is_string($schema['description'] ?? null) ? $schema['description'] : null,
            'default' => $schema['default'] ?? null,
            'example' => $this->example($schema, $depth),
            'nullable' => $this->isNullable($schema),
            'enum' => $enum,
            'enumDescriptions' => $enumDescriptions,
            'constraints' => $this->constraints($schema),
            'children' => $this->children($schema, $depth),
        ];
    }

    /**
     * Follows $ref and merges allOf so callers only ever see a plain schema.
     *
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    public function resolve(array $schema, int $depth = 0): array
    {
        if ($depth > self::MAX_DEPTH) {
            return $schema;
        }

        if (is_string($schema['$ref'] ?? null)) {
            $target = $this->dereference($schema['$ref']);
            $rest = $schema;
            unset($rest['$ref']);

            return array_merge($this->resolve($target, $depth + 1), $rest);
        }

        if (is_array($schema['allOf'] ?? null)) {
            $merged = $schema;
            unset($merged['allOf']);
            $properties = is_array($merged['properties'] ?? null) ? $merged['properties'] : [];
            $required = is_array($merged['required'] ?? null) ? $merged['required'] : [];

            foreach ($schema['allOf'] as $part) {
                if (!is_array($part)) {
                    continue;
                }

                $resolved = $this->resolve($part, $depth + 1);
                $properties = array_merge($properties, is_array($resolved['properties'] ?? null) ? $resolved['properties'] : []);
                $required = array_merge($required, is_array($resolved['required'] ?? null) ? $resolved['required'] : []);
                unset($resolved['properties'], $resolved['required']);
                $merged = array_merge($resolved, $merged);
            }

            if ($properties !== []) {
                $merged['properties'] = $properties;
            }

            if ($required !== []) {
                $merged['required'] = array_values(array_unique($required));
            }

            return $merged;
        }

        return $schema;
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    public function typeLabel(array $schema, int $depth = 0): string
    {
        $schema = $this->resolve($schema, $depth);
        $type = $schema['type'] ?? null;

        if (is_array($type)) {
            $types = array_values(array_filter($type, is_string(...)));
            $nullable = in_array('null', $types, strict: true);
            $types = array_values(array_filter($types, static fn (string $value): bool => $value !== 'null'));
            $label = $types === [] ? 'any' : implode(' | ', $types);

            return $nullable ? $label.' | null' : $label;
        }

        if (is_array($schema['enum'] ?? null)) {
            $base = is_string($type) ? $type : 'string';

            return 'enum<'.$base.'>';
        }

        if ($type === 'array') {
            $items = is_array($schema['items'] ?? null) ? $this->resolve($schema['items'], $depth + 1) : [];
            $itemType = $items === [] ? 'any' : $this->typeLabel($items, $depth + 1);

            return $itemType.'[]';
        }

        if (!is_string($type)) {
            return is_array($schema['properties'] ?? null) ? 'object' : 'any';
        }

        $format = $schema['format'] ?? null;

        return is_string($format) && $format !== '' ? $type.'<'.$format.'>' : $type;
    }

    /**
     * Declared examples always win. The type fallback only fires when the spec says nothing,
     * so a documented endpoint never renders a placeholder like "<string>".
     *
     * @param  array<string, mixed>  $schema
     */
    public function example(array $schema, int $depth = 0): mixed
    {
        $schema = $this->resolve($schema, $depth);

        if (array_key_exists('example', $schema)) {
            return $schema['example'];
        }

        if (is_array($schema['examples'] ?? null) && $schema['examples'] !== []) {
            $first = array_values($schema['examples'])[0];

            return is_array($first) && array_key_exists('value', $first) ? $first['value'] : $first;
        }

        if (array_key_exists('default', $schema)) {
            return $schema['default'];
        }

        if (is_array($schema['enum'] ?? null) && $schema['enum'] !== []) {
            return array_values($schema['enum'])[0];
        }

        return $this->fallbackExample($schema, $depth);
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    private function constraints(array $schema): array
    {
        $constraints = [];

        foreach (['minimum', 'maximum', 'minLength', 'maxLength', 'minItems', 'maxItems', 'pattern', 'format'] as $key) {
            if (array_key_exists($key, $schema) && $schema[$key] !== null) {
                $constraints[$key] = in_array($key, self::COUNTING_CONSTRAINTS, strict: true)
                    ? $this->asCount($schema[$key])
                    : $schema[$key];
            }
        }

        return $constraints;
    }

    /**
     * Scramble emits `max:255` as the float 255.0, which would render as "255.0".
     * These four constraints count characters or items, so they are always integers.
     */
    private function asCount(mixed $value): mixed
    {
        return is_float($value) && $value === floor($value) ? (int) $value : $value;
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return list<array<string, mixed>>|null
     */
    private function children(array $schema, int $depth): ?array
    {
        if ($depth >= self::MAX_DEPTH) {
            return null;
        }

        $target = $schema;

        if (($schema['type'] ?? null) === 'array' && is_array($schema['items'] ?? null)) {
            $target = $this->resolve($schema['items'], $depth + 1);
        }

        $hasProperties = is_array($target['properties'] ?? null) && $target['properties'] !== [];
        $hasAdditional = is_array($target['additionalProperties'] ?? null) && $target['additionalProperties'] !== [];

        if (!$hasProperties && !$hasAdditional) {
            return null;
        }

        $children = $this->fields($target, $depth + 1);

        return $children === [] ? null : $children;
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    private function isNullable(array $schema): bool
    {
        $type = $schema['type'] ?? null;

        if (is_array($type)) {
            return in_array('null', $type, strict: true);
        }

        return (bool) ($schema['nullable'] ?? false);
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    private function fallbackExample(array $schema, int $depth): mixed
    {
        $type = $schema['type'] ?? null;
        $type = is_array($type)
            ? (array_values(array_filter($type, static fn ($value): bool => $value !== 'null'))[0] ?? 'string')
            : $type;

        if ($type === 'array') {
            $items = is_array($schema['items'] ?? null) ? $this->resolve($schema['items'], $depth + 1) : [];

            return $items === [] ? [] : [$this->example($items, $depth + 1)];
        }

        if ($type === 'object' || is_array($schema['properties'] ?? null)) {
            $object = [];

            foreach ($this->fields($schema, $depth + 1) as $field) {
                $object[$field['name']] = $field['example'];
            }

            return $object;
        }

        return match ($type) {
            'integer' => 123,
            'number' => 123.45,
            'boolean' => true,
            default => $this->stringExample($schema),
        };
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    private function stringExample(array $schema): string
    {
        return match ($schema['format'] ?? null) {
            'date-time' => '2026-09-22T10:00:00Z',
            'date' => '2026-09-22',
            'email' => 'user@example.com',
            'uuid' => '9f8c2b1a-4d3e-4f5a-8b6c-7d8e9f0a1b2c',
            'uri', 'url' => 'https://example.com',
            default => '<string>',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function dereference(string $ref): array
    {
        if (!str_starts_with($ref, '#/')) {
            return [];
        }

        $node = $this->document;

        foreach (explode('/', mb_substr($ref, 2)) as $segment) {
            $segment = str_replace(['~1', '~0'], ['/', '~'], $segment);

            if (!is_array($node) || !array_key_exists($segment, $node)) {
                return [];
            }

            $node = $node[$segment];
        }

        return Value::map($node);
    }
}
