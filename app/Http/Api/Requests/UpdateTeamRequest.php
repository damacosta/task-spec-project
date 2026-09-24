<?php

declare(strict_types=1);

namespace App\Http\Api\Requests;

use He4rt\Identity\Teams\TeamStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateTeamRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],

            /**
             * One sentence shown next to the team name.
             *
             * @example The community team behind the open source projects.
             */
            'description' => ['sometimes', 'string', 'max:1000'],

            /**
             * Where administrative notices are sent.
             *
             * @example contact@he4rt.dev
             */
            'contact_email' => ['sometimes', 'email', 'max:255'],

            /**
             * Lifecycle state to move the team to.
             *
             * @example suspended
             */
            'status' => ['sometimes', Rule::enum(TeamStatus::class)],
        ];
    }
}
