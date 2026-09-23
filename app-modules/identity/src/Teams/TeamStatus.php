<?php

declare(strict_types=1);

namespace He4rt\Identity\Teams;

use BackedEnum;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum TeamStatus: string implements HasColor, HasIcon, HasLabel
{
    /** The team operates normally and its members can sign in. */
    case Active = 'active';

    /** Access is blocked while an incident is reviewed; the team can go back to active. */
    case Suspended = 'suspended';

    /** The team is closed for good and no longer appears in the default listing. */
    case Archived = 'archived';

    public function getLabel(): string
    {
        return __('teams::team_status.'.$this->value.'.label');
    }

    public function getColor(): array
    {
        return match ($this) {
            self::Active => Color::Green,
            self::Suspended => Color::Amber,
            self::Archived => Color::Red,
        };
    }

    public function getIcon(): BackedEnum
    {
        return match ($this) {
            self::Active => Heroicon::CheckCircle,
            self::Suspended => Heroicon::ExclamationCircle,
            self::Archived => Heroicon::XCircle,
        };
    }
}
