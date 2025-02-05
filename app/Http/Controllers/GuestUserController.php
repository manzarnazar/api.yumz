<?php

namespace App\Http\Controllers;

use App\Models\GuestUser;
use Illuminate\Http\Request;

class GuestUserController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'nullable|email|unique:guest_users,email',
            'phone_number' => 'nullable|string',
        ]);

        // Check if a guest with the same email already exists
        $guestUser = GuestUser::where('email', $request->email)->first();

        if (!$guestUser) {
            $guestUser = GuestUser::create($request->only(['name', 'email', 'phone_number']));
        }

        return response()->json(['guest_id' => $guestUser->id]);
    }
}
