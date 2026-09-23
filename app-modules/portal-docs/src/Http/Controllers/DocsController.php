<?php

declare(strict_types=1);

namespace He4rt\PortalDocs\Http\Controllers;

use He4rt\PortalDocs\Docs\DocsPage;
use He4rt\PortalDocs\Docs\DocsSite;
use He4rt\PortalDocs\Docs\PageKind;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class DocsController extends Controller
{
    public function __construct(private readonly DocsSite $site) {}

    public function index(): RedirectResponse
    {
        $slug = $this->site->firstSlug();

        abort_if($slug === null, 404);

        return to_route('portal-docs.page', $slug);
    }

    /**
     * Entry point of the API tab. Falls back to the first page of any kind when the spec
     * has no operations yet, so the tab is never a dead link.
     */
    public function api(): RedirectResponse
    {
        $slug = $this->site->firstEndpointSlug() ?? $this->site->firstSlug();

        abort_if($slug === null, 404);

        return to_route('portal-docs.page', $slug);
    }

    public function show(string $slug): InertiaResponse
    {
        $page = $this->site->find($slug);

        abort_unless($page instanceof DocsPage, 404);

        $props = [
            ...$this->sharedProps(),
            'page' => $page->toArray($this->site->prefix()),
            'previous' => $this->site->previous($slug)?->toArray($this->site->prefix()),
            'next' => $this->site->next($slug)?->toArray($this->site->prefix()),
            'markdownUrl' => url($page->href($this->site->prefix()).'.md'),
        ];

        if ($page->kind === PageKind::Endpoint) {
            $endpoint = $this->site->openApi()?->endpoint($slug);

            abort_if($endpoint === null, 404);

            return Inertia::render('Docs/Endpoint', [...$props, 'endpoint' => $endpoint]);
        }

        return Inertia::render('Docs/Show', $props);
    }

    public function markdown(string $slug): Response
    {
        $markdown = $this->site->markdown($slug);

        abort_if($markdown === null, 404);

        return response($this->documentationIndexNote().$markdown)
            ->header('Content-Type', 'text/markdown; charset=utf-8');
    }

    public function llms(): Response
    {
        return response($this->site->llmsIndex(url('/')))
            ->header('Content-Type', 'text/plain; charset=utf-8');
    }

    public function llmsFull(): Response
    {
        return response($this->site->llmsFull(url('/')))
            ->header('Content-Type', 'text/plain; charset=utf-8');
    }

    /**
     * Sent once per Inertia session: together they weigh around 10 kB, and resending them
     * on every client-side navigation is pure waste.
     *
     * @return array<string, mixed>
     */
    private function sharedProps(): array
    {
        return [
            'config' => Inertia::once(fn (): array => $this->site->config()->toArray()),
            'navigation' => Inertia::once(fn (): array => $this->site->navigationArray()),
        ];
    }

    private function documentationIndexNote(): string
    {
        return '> Documentation Index: '.url('/'.mb_trim($this->site->prefix(), '/').'/llms.txt')."\n\n";
    }
}
