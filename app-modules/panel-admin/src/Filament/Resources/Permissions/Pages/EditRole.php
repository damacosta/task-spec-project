<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Permissions\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use He4rt\PanelAdmin\Filament\Resources\Permissions\Concerns\LogsRolePermissionChanges;
use He4rt\PanelAdmin\Filament\Resources\Permissions\RoleResource;

class EditRole extends EditRecord
{
    use LogsRolePermissionChanges;

    protected static string $resource = RoleResource::class;

    /**
     * @return DeleteAction[]
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $this->snapshotPermissions();
    }

    protected function afterSave(): void
    {
        $this->logPermissionChange();
    }
}
