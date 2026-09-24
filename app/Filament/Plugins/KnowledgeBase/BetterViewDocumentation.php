<?php

declare(strict_types=1);

namespace App\Filament\Plugins\KnowledgeBase;

use Guava\FilamentKnowledgeBase\Filament\Pages\ViewDocumentation;
use Illuminate\Contracts\Support\Htmlable;

class BetterViewDocumentation extends ViewDocumentation
{
    protected static string $resource = BetterKnowledgeDocumentationResource::class;

    /** Empty drops the panel's own h1: the markdown already opens with the title, and the
     * breadcrumb still names the page. */
    public function getHeading(): string|Htmlable
    {
        return '';
    }
}
