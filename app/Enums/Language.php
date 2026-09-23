<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum Language: string implements HasColor, HasDescription, HasLabel
{
    case English = 'en';
    case BrazilianPortuguese = 'pt_BR';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $language): array => [$language->value => $language->getLabel()])
            ->all();
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::English => 'English',
            self::BrazilianPortuguese => 'Português (Brasil)',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::English => 'info',
            self::BrazilianPortuguese => 'success',
        };
    }

    public function getDescription(): string
    {
        return __(sprintf('language.%s.description', $this->value));
    }
}
