<?php

declare(strict_types=1);

namespace He4rt\PortalDocs\Console;

use He4rt\PortalDocs\Docs\DocsPage;
use He4rt\PortalDocs\Docs\DocsSite;
use He4rt\PortalDocs\Docs\PageKind;
use Illuminate\Console\Command;

/**
 * Warms what a page request would otherwise build: the navigation tree and the resolved
 * OpenAPI document. Run it on deploy, after `scramble:cache`, so no visitor pays for the
 * static analysis of the controllers.
 */
final class CacheDocsCommand extends Command
{
    protected $signature = 'portal-docs:cache {--clear : Drop the cached navigation and spec instead of warming them}';

    protected $description = 'Warm the portal navigation and the resolved OpenAPI document';

    public function handle(DocsSite $site): int
    {
        $site->forgetCache();

        if ($this->option('clear')) {
            $this->components->info('Portal cache cleared.');

            return self::SUCCESS;
        }

        $pages = count($site->pages());
        $endpoints = count(array_filter(
            $site->pages(),
            fn (DocsPage $page): bool => $page->kind === PageKind::Endpoint,
        ));

        $this->components->info(sprintf(
            'Portal cache warmed: %d pages, %d of them endpoints.',
            $pages,
            $endpoints,
        ));

        return self::SUCCESS;
    }
}
