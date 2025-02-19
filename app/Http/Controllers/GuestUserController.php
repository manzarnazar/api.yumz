<?php

namespace App\Http\Controllers;

use App\Models\GuestUser;
use App\Models\User;
use Illuminate\Http\Request;

class GuestUserController extends Controller
{
    public function store(Request $request)
    {
        // Validate input
        $validatedData = $request->validate([
            'firstname'   => 'required|string',
            'lastname'    => 'required|string',
            'email'       => 'nullable|email|unique:users,email',
            'phone'       => 'nullable|string',
        ]);
    
        // Ensure email and phone are strings (not arrays)
        $email = is_array($request->email) ? null : $request->email;
        $phone = is_array($request->phone) ? null : $request->phone;
    
        // Check if the user already exists
        $user = User::where('phone', $phone)->first();
    
        if (!$user) {
            $user = User::create([
                'firstname' => $validatedData['firstname'],
                'lastname'  => $validatedData['lastname'],
                'email'     => $email,
                'phone'     => $phone,
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