<?php

declare(strict_types=1);

namespace App\Filament\Plugins\KnowledgeBase;

use App\Enums\Language;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;
use Guava\FilamentKnowledgeBase\Contracts\Documentable;
use Guava\FilamentKnowledgeBase\Enums\NodeType;
use Guava\FilamentKnowledgeBase\KnowledgeBaseRegistry;
use Guava\FilamentKnowledgeBase\Plugins\KnowledgeBasePlugin;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class BetterKnowledgeBasePlugin extends KnowledgeBasePlugin
{
    public function register(Panel $panel): void
    {
        $this->docsPath ??= base_path('docs'.DIRECTORY_SEPARATOR.$panel->getId());

        $panel->resources([
            BetterKnowledgeDocumentationResource::class,
        ]);

        resolve(KnowledgeBaseRegistry::class)->docsPath($panel->getId(), $this->getDocsPath());

    }

    public function boot(Panel $panel): void
    {
        Filament::serving(function () use ($panel): void {
            $language = Language::tryFrom((string) Auth::user()?->locale);

            if ($language instanceof Language) {
                App::setLocale($language->value);
            }

            $isDocsRoute = request()->routeIs('filament.*.resources.docs.*');

            if (!$isDocsRoute) {
                $this->registerDocumentationNavItem($panel);

                return;
            }

            $this->overrideNavigationWithDocs($panel);
        });
    }

    /**
     * Adds a "Documentation" nav item to the regular admin sidebar.
     */
    protected function registerDocumentationNavItem(Panel $panel): void
    {
        $firstDoc = BetterFlatfileNode::query()
            ->where('panel_id', $panel->getId())
            ->where('active', operator: true)
            ->type(NodeType::Documentation)
            ->get()
            ->sort(fn (Documentable $d1, Documentable $d2) => $d1->getOrder() <=> $d2->getOrder())
            ->first();

        if (!$firstDoc) {
            return;
        }

        $url = BetterViewDocumentation::getUrl(
            parameters: ['record' => $firstDoc],
            panel: $panel->getId(),
        );

        $panel->navigationItems([
            NavigationItem::make(__('filament-knowledge-base::translations.knowledge-base'))
                ->icon(Heroicon::OutlinedBookOpen)
                ->url($url)
                ->sort(999),
        ]);
    }

    /**
     * Replaces the entire sidebar with documentation navigation and adds a "Go Back" item to the user menu.
     */
    protected function overrideNavigationWithDocs(Panel $panel): void
    {
        $allNodes = BetterFlatfileNode::query()
            ->where('panel_id', $panel->getId())
            ->get();

        $groups = $allNodes
            ->filter(fn (BetterFlatfileNode $node) => $node->getType() === NodeType::Group)
            ->sort(fn (BetterFlatfileNode $a, BetterFlatfileNode $b) => $a->getOrder() <=> $b->getOrder());

        $docNodes = $allNodes
            ->filter(fn (BetterFlatfileNode $node) => $node->isActive() && in_array($node->getType(), [NodeType::Documentation, NodeType::Link], strict: true))
            ->sort(fn (Documentable $a, Documentable $b) => $a->getOrder() <=> $b->getOrder());

        $panelId = $panel->getId();
        $makeUrl = fn (BetterFlatfileNode $node): string => BetterViewDocumentation::getUrl(
            parameters: ['record' => $node],
            panel: $panelId,
        );

        $ungroupedItems = $docNodes
            ->filter(fn (BetterFlatfileNode $node) => $node->parent()?->getType() !== NodeType::Group)
            ->map(static function (Documentable $node) use ($makeUrl): NavigationItem {
                $url = $makeUrl($node);

                return NavigationItem::make($node->getTitle())
                    ->sort($node->getOrder())
                    ->icon($node->getIcon())
                    ->url($url)
                    ->isActiveWhen(fn () => url()->current() === $url);
            })
            ->values()
            ->all();

        $navigationGroups = $groups->map(static function (BetterFlatfileNode $group) use ($docNodes, $makeUrl): NavigationGroup {
            $hasGroupIcon = filled($group->getIcon());

            $groupItems = $docNodes
                ->filter(fn (BetterFlatfileNode $node) => $node->parent()?->id === $group->id)
                ->map(static function (Documentable $node) use ($hasGroupIcon, $makeUrl): NavigationItem {
                    $url = $makeUrl($node);
                    $item = NavigationItem::make($node->getTitle())
                        ->sort($node->getOrder())
                        ->url($url)
                        ->isActiveWhen(fn () => url()->current() === $url);

                    if (!$hasGroupIcon) {
                        $item->icon($node->getIcon());
                    }

                    return $item;
                })
                ->values()
                ->all();

            return NavigationGroup::make($group->getTitle())
                ->icon($group->getIcon())
                ->items($groupItems);
        })->values()->all();

        $panel->userMenuItems([
            'back-to-panel' => Action::make('back-to-panel')
                ->label(__('knowledge_base.back_to_panel', ['panel' => mb_ucfirst($panel->getId())]))
                ->url(Dashboard::getUrl(panel: $panel->getId()))
                ->icon(Heroicon::OutlinedArrowLeft)
                ->sort(1),
        ]);

        $panel->navigation(fn (NavigationBuilder $builder): NavigationBuilder => $builder
            ->items([
                ...$ungroupedItems,
            ])
            ->groups($navigationGroups));
    }
}
