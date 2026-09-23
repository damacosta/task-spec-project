<?php

declare(strict_types=1);

namespace He4rt\Permissions\Tests\Feature\Filament;

use Filament\Facades\Filament;
use He4rt\Identity\Permissions\Permission;
use He4rt\Identity\Permissions\Role;
use He4rt\Identity\Permissions\Roles;
use He4rt\Identity\Users\User;
use He4rt\PanelAdmin\Filament\Resources\Permissions\Pages\CreateRole;
use He4rt\PanelAdmin\Filament\Resources\Permissions\Pages\EditRole;
use He4rt\PanelAdmin\Filament\Resources\Permissions\Pages\ListRoles;
use He4rt\PanelAdmin\Filament\Resources\Permissions\RoleResource;
use Spatie\Activitylog\Models\Activity;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    actingAs(User::factory()->create());

    // Give the user SuperAdmin role to bypass all policies
    auth()->user()->assignRole(Roles::SuperAdmin->value);
});

it('can list roles', function (): void {
    $roles = Role::factory()->count(5)->create();

    livewire(ListRoles::class)
        ->loadTable()
        ->assertCanSeeTableRecords($roles)
        ->assertCountTableRecords(5 + count(Roles::cases())); // 5 + default roles
});

it('can render create role page', function (): void {
    get(RoleResource::getUrl('create'))
        ->assertSuccessful();
});

it('can create role', function (): void {
    $newData = [
        'name' => 'Test Role',
        'guard_name' => 'web',
    ];

    livewire(CreateRole::class)
        ->fillForm($newData)
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(Role::class, [
        'name' => 'Test Role',
        'guard_name' => 'web',
    ]);
});

it('can render edit role page', function (): void {
    $role = Role::factory()->create();

    get(RoleResource::getUrl('edit', ['record' => $role]))
        ->assertSuccessful();
});

it('can update role', function (): void {
    $role = Role::factory()->create();
    $newData = [
        'name' => 'Updated Role Name',
    ];

    livewire(EditRole::class, [
        'record' => $role->getRouteKey(),
    ])
        ->fillForm($newData)
        ->call('save')
        ->assertHasNoFormErrors();

    expect($role->refresh())
        ->name->toBe('Updated Role Name');
});

it('can sync permissions to role', function (): void {
    $role = Role::factory()->create();
    $permissions = Permission::query()->limit(5)->pluck('id')->toArray();

    livewire(EditRole::class, [
        'record' => $role->getRouteKey(),
    ])
        ->fillForm([
            'permissions' => $permissions,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($role->refresh()->permissions->pluck('id')->toArray())
        ->toEqualCanonicalizing($permissions);
});

it('logs an activity when a role is created with permissions', function (): void {
    $permissions = Permission::query()->limit(3)->pluck('id')->toArray();

    livewire(CreateRole::class)
        ->fillForm([
            'name' => 'Test Role',
            'guard_name' => 'web',
            'permissions' => $permissions,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $role = Role::query()->where('name', 'Test Role')->sole();

    $activity = Activity::query()
        ->where('event', 'permissions_synced')
        ->where('subject_id', $role->getKey())
        ->sole();

    expect(data_get($activity->attribute_changes, 'attributes.permissions'))
        ->toEqualCanonicalizing($permissions)
        ->and(data_get($activity->attribute_changes, 'old.permissions'))
        ->toBeEmpty();
});

it('logs an activity when role permissions change', function (): void {
    $role = Role::factory()->create();
    $permissions = Permission::query()->limit(3)->pluck('id')->toArray();

    livewire(EditRole::class, [
        'record' => $role->getRouteKey(),
    ])
        ->fillForm([
            'permissions' => $permissions,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    assertDatabaseHas('activity_log', [
        'log_name' => Role::class,
        'event' => 'permissions_synced',
        'description' => 'Role permissions updated',
        'subject_type' => $role->getMorphClass(),
        'subject_id' => $role->getKey(),
    ]);

    $activity = Activity::query()
        ->where('event', 'permissions_synced')
        ->where('subject_id', $role->getKey())
        ->sole();

    // The lib builds the "Changes" tab from attribute_changes, not properties.
    expect(data_get($activity->attribute_changes, 'attributes.permissions'))
        ->toEqualCanonicalizing($permissions)
        ->and(data_get($activity->attribute_changes, 'old.permissions'))
        ->toBeEmpty();
});

it('does not log a permissions activity when permissions are unchanged', function (): void {
    $role = Role::factory()->create();

    livewire(EditRole::class, [
        'record' => $role->getRouteKey(),
    ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(
        Activity::query()
            ->where('event', 'permissions_synced')
            ->where('subject_id', $role->getKey())
            ->exists()
    )->toBeFalse();
});
