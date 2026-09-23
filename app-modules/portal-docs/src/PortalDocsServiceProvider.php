<?php

declare(strict_types=1);

namespace He4rt\PortalDocs;

use Dedoc\Scramble\Scramble;
use He4rt\PortalDocs\Docs\DocsSite;
use He4rt\PortalDocs\OpenApi\Scramble\DocumentsApiConventions;
use He4rt\PortalDocs\OpenApi\ScrambleSpecLoader;
use He4rt\PortalDocs\OpenApi\SpecLoader;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class PortalDocsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/portal-docs.php', 'portal-docs');

        // Scramble reads this flag in its own boot(), which runs before this module's:
        // calling it from boot() here would arrive after the routes are already exposed,
        // and Scramble's GET docs/api would silently replace the portal's.
        Scramble::ignoreDefaultRoutes();

        $this->app->bind(SpecLoader::class, fn (): ScrambleSpecLoader => new ScrambleSpecLoader(
            config()->string('portal-docs.openapi.api', Scramble::DEFAULT_API),
        ));

        $this->app->singleton(DocsSite::class, fn (Application $app): DocsSite => new DocsSite(
            contentPath: config()->string('portal-docs.content_path'),
            specLoader: $app->make(SpecLoader::class),
            prefix: config()->string('portal-docs.prefix', 'docs'),
            openApiEnabled: config()->boolean('portal-docs.openapi.enabled', default: true),
        ));
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'portal-docs');

        // The portal is client-rendered on purpose: Mintlify highlights code in a worker
        // and Mermaid needs a DOM. Scoped to this prefix so the host keeps its options.
        Inertia::withoutSsr(config()->string('portal-docs.prefix', 'docs').'/*');

        // assertInertia resolves page components against this list; without the push it
        // reports "Inertia page component [Docs/Show] does not exist".
        config()->set('inertia.pages.paths', [
            ...config()->array('inertia.pages.paths', []),
            __DIR__.'/../resources/js/pages',
        ]);

        // `regex:` rules already become `pattern` through Scramble's own RegexRule transformer.
        Scramble::configure()->withDocumentTransformers(DocumentsApiConventions::class);
    }
}
