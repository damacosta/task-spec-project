<?php

declare(strict_types=1);

namespace App\Http\Api\Requests;

use He4rt\Identity\Teams\TeamStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreTeamRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            /**
             * Display name of the team.
             *
             * @example He4rt Developers
             */
            'name' => ['required', 'string', 'max:255'],

            /**
             * URL-safe identifier. Lowercase letters, digits and hyphens only.
             *
             * @example he4rt-developers
             */
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/', Rule::unique('identity_teams', 'slug')],

            /**
             * One sentence shown next to the team name.
             *
             * @example The community team behind the open source projects.
             */
            'description' => ['required', 'string', 'max:1000'],

            /**
             * Where administrative notices are sent.
             *
             * @example contact@he4rt.dev
             */
            'contact_email' => ['required', 'email', 'max:255'],

            /**
             * Lifecycle state the team starts in.
             *
             * @default active
             */
            'status' => ['sometimes', Rule::enum(TeamStatus::class)],

            /**
             * Identifier of the user who owns the team.
             *
             * @example 9f8c2b1a-4d3e-4f5a-8b6c-7d8e9f0a1b2c
             */
            'owner_id' => ['required', 'uuid', Rule::exists('identity_users', 'id')],
        ];
    }
}
