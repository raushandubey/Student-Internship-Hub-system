<?php

namespace App\Http\Controllers\Recruiter;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RecruiterProfileController extends Controller
{
    public function show()
    {
        $profile = auth()->user()->recruiterProfile;
        return view('recruiter.profile.show', compact('profile'));
    }

    public function edit()
    {
        $profile = auth()->user()->recruiterProfile;
        return view('recruiter.profile.edit', compact('profile'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'organization' => 'required|string|max:255',
            'description'  => 'nullable|string|max:2000',
            'website'      => 'nullable|url|max:255',
        ]);

        $user = auth()->user();
        $user->recruiterProfile()->updateOrCreate(
            ['user_id' => $user->id],
            $data
        );

        return redirect()->route('recruiter.profile.show')
            ->with('success', 'Profile updated successfully.');
    }

    public function updateLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $user = auth()->user();
        $profile = $user->recruiterProfile()->firstOrCreate(['user_id' => $user->id]);

        // Delete old logo
        if ($profile->logo_path) {
            Storage::disk('public')->delete($profile->logo_path);
        }

        $path = $request->file('logo')->store('recruiter-logos', 'public');
        $profile->update(['logo_path' => $path]);

        return back()->with('success', 'Logo updated successfully.');
    }
}
