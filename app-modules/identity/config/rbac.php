<?php

declare(strict_types=1);

use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\ExternalIdentity\Models\IdentityResource;
use He4rt\Identity\Permissions\Permission;
use He4rt\Identity\Permissions\PermissionsEnum;
use He4rt\Identity\Permissions\Role;
use He4rt\Identity\Permissions\Roles;
use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\User;

return [
    'permissions' => [
        Roles::SuperAdmin->value => [
            User::class => PermissionsEnum::cases(),
            ExternalIdentity::class => PermissionsEnum::cases(),
            IdentityResource::class => PermissionsEnum::cases(),
            Role::class => PermissionsEnum::cases(),
            Permission::class => PermissionsEnum::cases(),
            Team::class => PermissionsEnum::cases(),
        ],
        Roles::User->value => [
            User::class => [PermissionsEnum::View],
            ExternalIdentity::class => [],
            IdentityResource::class => [],
            Role::class => [],
            Permission::class => [],
            Team::class => [],
        ],
    ],
];
