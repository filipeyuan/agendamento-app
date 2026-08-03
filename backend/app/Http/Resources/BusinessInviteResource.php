<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\BusinessInvite;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BusinessInvite */
class BusinessInviteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'invited_by' => $this->whenLoaded('invitedBy', fn () => $this->invitedBy?->name),
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at,
        ];
    }
}
