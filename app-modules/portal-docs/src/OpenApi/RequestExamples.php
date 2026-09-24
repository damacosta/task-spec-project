<?php

declare(strict_types=1);

namespace He4rt\PortalDocs\OpenApi;

/**
 * Builds the three static snippets of the right-hand panel.
 *
 * They are illustrations, not a playground: nothing here is executed by the portal.
 */
final readonly class RequestExamples
{
    /**
     * @param  list<array<string, mixed>>  $headers  header parameters, each with name and example
     * @param  list<array<string, mixed>>  $security
     * @return array{curl: string, php: string, javascript: string}
     */
    public static function build(
        string $method,
        string $url,
        array $headers,
        array $security,
        mixed $body,
        ?string $contentType,
    ): array {
        $method = mb_strtoupper($method);
        $lines = self::headerLines($headers, $security, $contentType);
        $json = $body === null ? null : (json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: null);

        return [
            'curl' => self::curl($method, $url, $lines, $json),
            'php' => self::php($method, $url, $lines, $body),
            'javascript' => self::javascript($method, $url, $lines, $json),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $headers
     * @param  list<array<string, mixed>>  $security
     * @return array<string, string>
     */
    private static function headerLines(array $headers, array $security, ?string $contentType): array
    {
        $lines = [];

        foreach ($security as $scheme) {
            $type = $scheme['type'] ?? null;

            if ($type === 'http' && ($scheme['scheme'] ?? null) === 'bearer') {
                $lines['Authorization'] = 'Bearer <token>';
            }

            if ($type === 'apiKey' && ($scheme['in'] ?? null) === 'header' && is_string($scheme['name'] ?? null)) {
                $lines[$scheme['name']] = '<api-key>';
            }
        }

        foreach ($headers as $header) {
            if (!is_string($header['name'] ?? null)) {
                continue;
            }

            $example = $header['example'] ?? null;
            $lines[$header['name']] = is_scalar($example) ? (string) $example : '<value>';
        }

        if ($contentType !== null) {
            $lines['Content-Type'] = $contentType;
        }

        $lines['Accept'] = 'application/json';

        return $lines;
    }

    /**
     * @param  array<string, string>  $headers
     */
    private static function curl(string $method, string $url, array $headers, ?string $json): string
    {
        $parts = ["curl --request {$method} \\", "  --url {$url} \\"];

        foreach ($headers as $name => $value) {
            $parts[] = "  --header '{$name}: {$value}' \\";
        }

        if ($json === null) {
            $last = array_pop($parts);

            return implode("\n", [...$parts, mb_rtrim((string) $last, ' \\')]);
        }

        $parts[] = "  --data '".$json."'";

        return implode("\n", $parts);
    }

    /**
     * @param  array<string, string>  $headers
     */
    private static function php(string $method, string $url, array $headers, mixed $body): string
    {
        $headerLines = [];

        foreach ($headers as $name => $value) {
            $headerLines[] = "        '{$name}' => '{$value}',";
        }

        $call = mb_strtolower($method);

        $lines = [
            'use Illuminate\Support\Facades\Http;',
            '',
            '$response = Http::withHeaders([',
            ...$headerLines,
            '    ])',
        ];

        if ($body === null) {
            $lines[] = "    ->{$call}('{$url}');";
        } else {
            $lines[] = "    ->{$call}('{$url}', ".self::phpArray($body, 2).');';
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, string>  $headers
     */
    private static function javascript(string $method, string $url, array $headers, ?string $json): string
    {
        $headerLines = [];

        foreach ($headers as $name => $value) {
            $headerLines[] = "    '{$name}': '{$value}',";
        }

        $lines = [
            "const response = await fetch('{$url}', {",
            "  method: '{$method}',",
            '  headers: {',
            ...$headerLines,
            '  },',
        ];

        if ($json !== null) {
            $lines[] = '  body: JSON.stringify('.$json.'),';
        }

        $lines[] = '});';
        $lines[] = '';
        $lines[] = 'const data = await response.json();';

        return implode("\n", $lines);
    }

    private static function phpArray(mixed $value, int $indent): string
    {
        $pad = str_repeat('    ', $indent);
        $inner = str_repeat('    ', $indent + 1);

        if (!is_array($value)) {
            return self::phpScalar($value);
        }

        if ($value === []) {
            return '[]';
        }

        $lines = ['['];
        $isList = array_is_list($value);

        foreach ($value as $key => $item) {
            $rendered = self::phpArray($item, $indent + 1);
            $lines[] = $isList
                ? $inner.$rendered.','
                : $inner."'".$key."' => ".$rendered.',';
        }

        $lines[] = $pad.']';

        return implode("\n", $lines);
    }

    private static function phpScalar(mixed $value): string
    {
        return match (true) {
            $value === null => 'null',
            is_bool($value) => $value ? 'true' : 'false',
            is_int($value), is_float($value) => (string) $value,
            default => "'".str_replace("'", "\\'", (string) $value)."'",
        };
    }
}
