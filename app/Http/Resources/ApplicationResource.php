<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ApplicationResource
 * 
 * Transforms Application model for API responses.
 * 
 * Why API Resources?
 * - Consistent API response format
 * - Control over exposed data
 * - Easy versioning
 * - Interview: "I used API Resources for clean data transformation"
 */
class ApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'user_id'             => $this->user_id,
            'internship_id'       => $this->internship_id,
            'status'              => $this->status->value,
            'status_label'        => $this->status->label(),
            'status_color'        => $this->status->colorClass(),
            'is_terminal'         => $this->status->isTerminal(),
            'match_score'         => $this->match_score ?? 0,
            'allowed_transitions' => array_map(
                fn($s) => ['value' => $s->value, 'label' => $s->label()],
                $this->allowedTransitions()
            ),
            'internship'  => new InternshipResource($this->whenLoaded('internship')),
            'user'        => new UserResource($this->whenLoaded('user')),
            'applied_at'  => $this->created_at?->toISOString(),
            'created_at'  => $this->created_at?->toISOString(),
            'updated_at'  => $this->updated_at?->toISOString(),
        ];
    }
}
