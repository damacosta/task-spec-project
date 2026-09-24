<?php

declare(strict_types=1);

namespace He4rt\PortalDocs\Docs;

/**
 * Everything docs.json declares outside the navigation tree.
 *
 * Mintlify's schema carries more than this (fonts, versions, languages, footer);
 * the unknown keys are dropped rather than passed through, so the front-end never
 * reads a field the layout does not honour.
 */
final readonly class DocsConfig
{
    /**
     * @param  array{primary: string, light: string, dark: string}  $colors
     * @param  list<array{anchor: string, href: string, icon: string|null}>  $anchors
     * @param  list<string>  $contextual
     */
    public function __construct(
        public string $name,
        public ?string $description,
        public array $colors,
        public string $appearance,
        public bool $strictAppearance,
        public array $anchors,
        public array $contextual,
    ) {}

    /**
     * @param  array<string, mixed>  $raw
     * @param  list<string>  $defaultContextual
     */
    public static function fromArray(array $raw, array $defaultContextual): self
    {
        $colors = is_array($raw['colors'] ?? null) ? $raw['colors'] : [];
        $primary = is_string($colors['primary'] ?? null) ? $colors['primary'] : '#16a34a';

        $appearance = is_array($raw['appearance'] ?? null) ? $raw['appearance'] : [];
        $default = is_string($appearance['default'] ?? null) ? $appearance['default'] : 'system';

        $contextual = is_array($raw['contextual'] ?? null) ? $raw['contextual'] : [];
        $options = array_values(array_filter(
            is_array($contextual['options'] ?? null) ? $contextual['options'] : $defaultContextual,
            is_string(...),
        ));

        return new self(
            name: is_string($raw['name'] ?? null) ? $raw['name'] : 'Documentation',
            description: is_string($raw['description'] ?? null) ? $raw['description'] : null,
            colors: [
                'primary' => $primary,
                'light' => is_string($colors['light'] ?? null) ? $colors['light'] : $primary,
                'dark' => is_string($colors['dark'] ?? null) ? $colors['dark'] : $primary,
            ],
            appearance: in_array($default, ['system', 'light', 'dark'], strict: true) ? $default : 'system',
            strictAppearance: (bool) ($appearance['strict'] ?? false),
            anchors: self::anchors($raw),
            contextual: $options === [] ? $defaultContextual : $options,
        );
    }

    /**
     * @return array{name: string, description: string|null, colors: array{primary: string, light: string, dark: string}, appearance: string, strictAppearance: bool, anchors: list<array{anchor: string, href: string, icon: string|null}>, contextual: list<string>}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'colors' => $this->colors,
            'appearance' => $this->appearance,
            'strictAppearance' => $this->strictAppearance,
            'anchors' => $this->anchors,
            'contextual' => $this->contextual,
        ];
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return list<array{anchor: string, href: string, icon: string|null}>
     */
    private static function anchors(array $raw): array
    {
        $navigation = is_array($raw['navigation'] ?? null) ? $raw['navigation'] : [];
        $global = is_array($navigation['global'] ?? null) ? $navigation['global'] : [];
        $anchors = is_array($global['anchors'] ?? null) ? $global['anchors'] : [];

        $resolved = [];

        foreach ($anchors as $anchor) {
            if (!is_array($anchor) || !is_string($anchor['anchor'] ?? null) || !is_string($anchor['href'] ?? null)) {
                continue;
            }

            $resolved[] = [
                'anchor' => $anchor['anchor'],
                'href' => $anchor['href'],
                'icon' => is_string($anchor['icon'] ?? null) ? $anchor['icon'] : null,
            ];
        }

        return $resolved;
    }
}
