<?php

namespace App\Services;

use App\Models\Internship;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RecruiterInternshipService
{
    /**
     * Create a new internship for the recruiter
     */
    public function createInternship(array $data, $recruiterId)
    {
        $validator = Validator::make($data, [
            'title' => 'required|string|max:255',
            'organization' => 'required|string|max:255',
            'required_skills' => 'required|array',
            'duration' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return Internship::create([
            'recruiter_id' => $recruiterId,
            'title' => $data['title'],
            'organization' => $data['organization'],
            'required_skills' => $data['required_skills'],
            'duration' => $data['duration'],
            'location' => $data['location'],
            'description' => $data['description'] ?? null,
            'is_active' => true,
        ]);
    }

    /**
     * Update an existing internship with ownership check
     */
    public function updateInternship(Internship $internship, array $data, $recruiterId)
    {
        // Check ownership
        if ($internship->recruiter_id !== $recruiterId) {
            abort(403, 'Unauthorized to update this internship.');
        }

        $validator = Validator::make($data, [
            'title' => 'required|string|max:255',
            'organization' => 'required|string|max:255',
            'required_skills' => 'required|array',
            'duration' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $internship->update([
            'title' => $data['title'],
            'organization' => $data['organization'],
            'required_skills' => $data['required_skills'],
            'duration' => $data['duration'],
            'location' => $data['location'],
            'description' => $data['description'] ?? $internship->description,
            'is_active' => $data['is_active'] ?? $internship->is_active,
        ]);

        return $internship->fresh();
    }

    /**
     * Delete an internship with ownership check
     */
    public function deleteInternship(Internship $internship, $recruiterId)
    {
        // Check ownership
        if ($internship->recruiter_id !== $recruiterId) {
            abort(403, 'Unauthorized to delete this internship.');
        }

        return $internship->delete();
    }

    /**
     * Get all internships for a specific recruiter with data isolation
     */
    public function getRecruiterInternships($recruiterId, $withStats = true)
    {
        $query = Internship::forRecruiter($recruiterId);

        if ($withStats) {
            $query->withApplicationStats();
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}
