<?php

declare(strict_types=1);

namespace App\Http\Api\Resources;

use He4rt\Identity\Teams\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Team
 */
final class TeamResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            /** @example 9f8c2b1a-4d3e-4f5a-8b6c-7d8e9f0a1b2c */
            'id' => $this->id,
            /** @example He4rt Developers */
            'name' => $this->name,
            /** @example he4rt-developers */
            'slug' => $this->slug,
            /** @example The community team behind the open source projects. */
            'description' => $this->description,
            'status' => $this->status,
            /** @example contact@he4rt.dev */
            'contact_email' => $this->contact_email,
            /** @example 9f8c2b1a-4d3e-4f5a-8b6c-7d8e9f0a1b2c */
            'owner_id' => $this->owner_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
