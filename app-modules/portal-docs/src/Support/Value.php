<?php

declare(strict_types=1);

namespace He4rt\PortalDocs\Support;

/**
 * Narrows values decoded from JSON and YAML.
 *
 * docs.json and the OpenAPI document are user input as far as the type system is
 * concerned: every branch below is a shape the portal must survive, not a formality.
 */
final class Value
{
    /**
     * @return array<string, mixed>
     */
    public static function map(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $map = [];

        foreach ($value as $key => $item) {
            $map[(string) $key] = $item;
        }

        return $map;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function maps(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_map(self::map(...), array_filter($value, is_array(...))));
    }

    /**
     * @return list<mixed>
     */
    public static function list(mixed $value): array
    {
        return is_array($value) ? array_values($value) : [];
    }

    /**
     * @return list<string>
     */
    public static function strings(mixed $value): array
    {
        return is_array($value)
            ? array_values(array_filter($value, is_string(...)))
            : [];
    }

    public static function string(mixed $value, ?string $default = null): ?string
    {
        return is_string($value) ? $value : $default;
    }
}
