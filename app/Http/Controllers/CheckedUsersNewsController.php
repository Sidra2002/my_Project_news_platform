<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\checked_users_News;
use Illuminate\Support\Facades\Http;


class CheckedUsersNewsController extends Controller
{
    //
    public function check(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        $user = $request->user(); // Authenticated user

        $content = $request->input('content');

        // Send to external API
        $externalResponse = Http::post('https://f354-169-150-196-137.ngrok-free.app/predict', [
            'text' => $content,
        ]);

        if ($externalResponse->failed()) {
            return response()->json(['error' => 'External service failed'], 502);
        }

        $result = $externalResponse->body(); // expecting 'fake' or 'real'
        $isFake = strtolower(trim($result)) === 'fake';

        // Store in DB
        $record = checked_users_News::create([
            'content' => $content,
            'is_fake' => $isFake,
            'user_id' => $user->id,
        ]);

        return response()->json($record);
    }
}

