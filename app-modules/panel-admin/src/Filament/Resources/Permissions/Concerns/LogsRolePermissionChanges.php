<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Permissions\Concerns;

use App\Support\Activitylog\AuditActivity;
use He4rt\Identity\Permissions\Role;

/**
 * Logs role permission syncs, which Spatie never sees because they mutate the
 * role_has_permissions pivot rather than a Role column.
 *
 * Create pages skip the snapshot (there is no "before"), so the diff runs from
 * an empty set. The entry writes attribute_changes under the Role log channel
 * so it lands in the package "Changes" tab beside the Role's own edits.
 */
trait LogsRolePermissionChanges
{
    /** @var list<string> */
    protected array $permissionsBeforeSave = [];

    protected function snapshotPermissions(): void
    {
        $this->permissionsBeforeSave = $this->currentPermissionIds();
    }

    protected function logPermissionChange(): void
    {
        $after = $this->currentPermissionIds();

        if ($this->permissionsBeforeSave === $after) {
            return;
        }

        AuditActivity::changed(
            $this->role(),
            'permissions_synced',
            attributes: ['permissions' => $after],
            old: ['permissions' => $this->permissionsBeforeSave],
            description: 'Role permissions updated',
            log: Role::class,
        );
    }

    /**
     * @return list<string>
     */
    protected function currentPermissionIds(): array
    {
        $ids = $this->role()->permissions()
            ->pluck('id')
            ->map(static fn (mixed $id): string => (string) $id)
            ->all();

        sort($ids);

        return $ids;
    }

    protected function role(): Role
    {
        /** @var Role $role */
        $role = $this->record;

        return $role;
    }
}
