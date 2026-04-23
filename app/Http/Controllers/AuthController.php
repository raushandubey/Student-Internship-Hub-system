<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:student,admin'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        Auth::login($user);

        return redirect()->route('dashboard');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            $user = Auth::user();

            // Check recruiter approval status (Requirements: 1.2, 4.2, 4.3)
            if ($user->isRecruiter()) {
                $profile = $user->recruiterProfile;

                if ($profile) {
                    $status = $profile->approval_status;

                    if ($status === 'pending') {
                        Auth::logout();
                        $request->session()->invalidate();
                        $request->session()->regenerateToken();
                        return back()->withErrors([
                            'email' => 'Your account is pending approval.',
                        ]);
                    }

                    if ($status === 'suspended') {
                        Auth::logout();
                        $request->session()->invalidate();
                        $request->session()->regenerateToken();
                        return back()->withErrors([
                            'email' => 'Your account has been suspended.',
                        ]);
                    }

                    if ($status === 'rejected') {
                        Auth::logout();
                        $request->session()->invalidate();
                        $request->session()->regenerateToken();
                        return back()->withErrors([
                            'email' => 'Your account application was not approved.',
                        ]);
                    }
                }
            }

            return redirect()->intended('dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    public function showRecruiterRegister()
    {
        return view('auth.recruiter-register');
    }

    public function registerRecruiter(Request $request)
    {
        $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'organization' => ['required', 'string', 'max:255'],
            'password'     => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'recruiter',
        ]);

        // Create a basic recruiter profile with the organization name
        // approval_status defaults to 'pending' per Requirement 1.1
        $user->recruiterProfile()->create([
            'organization'    => $request->organization,
            'approval_status' => 'pending',
        ]);

        // Do NOT log the user in — redirect to login with a pending message
        return redirect()->route('login')
            ->with('status', 'Your account is pending approval. You will receive an email once approved.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}