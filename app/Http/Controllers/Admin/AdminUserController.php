<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class AdminUserController extends Controller
{
    /**
     * Display all students
     */
    public function index()
    {
        $students = User::where('role', 'student')
            ->with('profile')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.users.index', compact('students'));
    }

    /**
     * Show student profile details
     */
    public function show(User $user)
    {
        // Ensure we're only viewing students
        if ($user->role !== 'student') {
            abort(404);
        }

        $user->load('profile');
        return view('admin.users.show', compact('user'));
    }
}
