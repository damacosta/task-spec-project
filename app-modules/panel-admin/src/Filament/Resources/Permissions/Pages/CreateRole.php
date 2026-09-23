<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Permissions\Pages;

use Filament\Resources\Pages\CreateRecord;
use He4rt\PanelAdmin\Filament\Resources\Permissions\Concerns\LogsRolePermissionChanges;
use He4rt\PanelAdmin\Filament\Resources\Permissions\RoleResource;

class CreateRole extends CreateRecord
{
    use LogsRolePermissionChanges;

    protected static string $resource = RoleResource::class;

    /**
     * @return array<never>
     */
    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function afterCreate(): void
    {
        $this->logPermissionChange();
    }
}
