<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FirebaseController extends Controller
{
    //
    public function updateFirebaseToken(Request $request)
{
    $request->validate([
        'firebase_token' => 'required|string',
    ]);

    $user = Auth::user();
    $user->firebase_token = $request->firebase_token;
    $user->save();

    return response()->json(['message' => '✅ تم تحديث Firebase Token بنجاح.']);
}
}
