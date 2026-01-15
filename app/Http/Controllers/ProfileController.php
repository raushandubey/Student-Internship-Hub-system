<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show()
    {
        $profile = Auth::user()->profile;
        return view('profile.show', compact('profile'));
    }

    public function edit()
    {
        $profile = Auth::user()->profile;
        return view('profile.edit', compact('profile'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'academic_background' => 'nullable|string|max:255',
            'skills' => 'nullable|string',
            'career_interests' => 'nullable|string',
            'resume' => 'nullable|file|mimes:pdf|max:2048',
            'aadhaar_number' => 'nullable|string|max:12',
        ]);

        $user = Auth::user();
        $profile = $user->profile ?? new Profile(['user_id' => $user->id]);

        $profile->name = $request->name;
        $profile->academic_background = $request->academic_background;
        $profile->skills = $request->skills ? explode(',', $request->skills) : [];
        $profile->career_interests = $request->career_interests;
        $profile->aadhaar_number = $request->aadhaar_number;

        if ($request->hasFile('resume')) {
            if ($profile->resume_path) {
                Storage::delete($profile->resume_path);
            }
            $profile->resume_path = $request->file('resume')->store('resumes');
        }

        $profile->save();

        return redirect()->route('profile.show')->with('success', 'Profile updated successfully!');
    }
}