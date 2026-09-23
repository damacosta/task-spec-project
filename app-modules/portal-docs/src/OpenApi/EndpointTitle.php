<?php

declare(strict_types=1);

namespace He4rt\PortalDocs\OpenApi;

use Illuminate\Support\Str;

/**
 * Names an operation the way a reader expects: the summary if the controller has a PHPDoc,
 * otherwise the Laravel resource verb read off the operationId, otherwise the raw path.
 */
final readonly class EndpointTitle
{
    private const array VERBS = [
        'index' => 'List :resource',
        'show' => 'Show :resource',
        'store' => 'Create :resource',
        'update' => 'Update :resource',
        'destroy' => 'Delete :resource',
    ];

    public static function make(?string $summary, ?string $operationId, string $method, string $path, string $tag): string
    {
        if (is_string($summary) && mb_trim($summary) !== '') {
            return mb_trim($summary);
        }

        $fromOperationId = self::fromOperationId($operationId, $tag);

        if ($fromOperationId !== null) {
            return $fromOperationId;
        }

        return mb_strtoupper($method).' '.$path;
    }

    private static function fromOperationId(?string $operationId, string $tag): ?string
    {
        if (!is_string($operationId) || $operationId === '') {
            return null;
        }

        $action = Str::afterLast($operationId, '.');
        $template = self::VERBS[$action] ?? null;

        if ($template === null) {
            return null;
        }

        $resource = Str::headline($tag);
        $resource = $action === 'index' ? Str::plural($resource) : $resource;

        return str_replace(':resource', mb_strtolower($resource), $template);
    }
}
