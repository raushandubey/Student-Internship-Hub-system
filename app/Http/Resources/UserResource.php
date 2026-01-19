<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * UserResource
 * 
 * Transforms User model for API responses.
 * Note: Sensitive data (password, etc.) is never exposed.
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
