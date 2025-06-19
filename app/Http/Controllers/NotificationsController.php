<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notifications;
use Illuminate\Support\Facades\Auth;
use App\Services\FirebaseService;


class NotificationsController extends Controller
{
    //
     protected $firebase;

    // ✅ Inject FirebaseService
    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }
public function fetchNew()
    {
        $notifications = Notifications::with('news')
            ->where('user_id', Auth::id())
            ->where('seen', false)
            ->latest()
            ->get();

        return response()->json($notifications);
    }

    public function markAsSeen($id)
    {
        $notification = Notifications::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $notification->seen = true;
        $notification->save();

        return response()->json(['success' => true]);
    }



    public function sendFirebaseNotification(Request $request, FirebaseService $firebase)
{
    $request->validate([
        'device_token' => 'required|string',
        'title' => 'required|string',
        'body' => 'required|string',
    ]);

    try {
        $firebase->sendNotification(
            $request->device_token,
            $request->title,
            $request->body
        );

        return response()->json(['message' => 'Notification sent successfully']);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to send notification', 'details' => $e->getMessage()], 500);
    }
}

}
