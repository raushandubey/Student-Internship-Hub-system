<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * InternshipResource
 * 
 * Transforms Internship model for API responses.
 */
class InternshipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'organization' => $this->organization,
            'description' => $this->description,
            'location' => $this->location,
            'duration' => $this->duration,
            'work_type' => $this->work_type ?? 'onsite',
            'required_skills' => $this->required_skills,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'applications_count' => $this->whenCounted('applications'),
        ];
    }
}
