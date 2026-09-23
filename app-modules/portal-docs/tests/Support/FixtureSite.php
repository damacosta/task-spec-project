<?php

declare(strict_types=1);

namespace He4rt\PortalDocs\Tests\Support;

use He4rt\PortalDocs\Docs\DocsSite;
use He4rt\PortalDocs\Http\Middleware\HandlePortalDocsInertiaRequests;
use He4rt\PortalDocs\OpenApi\ArraySpecLoader;
use He4rt\PortalDocs\OpenApi\SpecLoader;

/**
 * Points the portal at the test fixture instead of the shipped content, so the suite
 * never breaks when someone edits a real .mdx page.
 */
final class FixtureSite
{
    public static function use(string $prefix = 'docs'): DocsSite
    {
        config()->set('portal-docs.content_path', self::contentPath());
        config()->set('portal-docs.prefix', $prefix);

        app()->bind(SpecLoader::class, fn (): ArraySpecLoader => new ArraySpecLoader(self::spec()));
        app()->forgetInstance(DocsSite::class);

        $site = new DocsSite(
            contentPath: self::contentPath(),
            specLoader: resolve(SpecLoader::class),
            prefix: $prefix,
        );

        app()->instance(DocsSite::class, $site);

        return $site;
    }

    public static function contentPath(): string
    {
        return __DIR__.'/../Fixtures/site';
    }

    /**
     * @return array<string, mixed>
     */
    public static function spec(): array
    {
        $decoded = json_decode((string) file_get_contents(__DIR__.'/../Fixtures/openapi.json'), associative: true);

        return is_array($decoded) ? $decoded : [];
    }

    /** Null until `vite build` writes a manifest; the header then has to be an empty string. */
    public static function inertiaVersion(): string
    {
        return (string) resolve(HandlePortalDocsInertiaRequests::class)->version(request());
    }
}
