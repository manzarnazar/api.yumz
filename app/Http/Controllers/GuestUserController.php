<?php

namespace App\Http\Controllers;

use App\Models\GuestUser;
use App\Models\User;
use Illuminate\Http\Request;

class GuestUserController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'firstname'   => 'required|string',
            'lastname'    => 'required|string',
            'email'        => 'nullable|email|unique:users,email',
            'phone' => 'nullable|string',
        ]);


        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            $user = User::create([
                'firstname' => $request->firstname,
                'lastname'  => $request->lastname,
                'email'      => $request->email,
                'phone'      => $request->phone,
            ]);
        }

        // Check if guest user already exists
        $guestUser = GuestUser::where('user_id', $user->id)->first();

        if (!$guestUser) {
            $guestUser = GuestUser::create([
                'user_id'      => $user->id,
                'email'        => $user->email,
                'phone_number' => $user->phone,
            ]);
        }

        return response()->json(['guest_id' => $guestUser->id, 'user_id' => $user->id]);
    }
}