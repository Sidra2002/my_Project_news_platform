<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NewsReaction;
use Illuminate\Support\Facades\Auth;

class NewsReactionController extends Controller
{
    public function react(Request $request, $newsId)
    {
        $request->validate([
            'reaction' => 'required|in:like,dislike',
        ]);

        $userId = Auth::id();
        $reactionType = $request->reaction;

        $existing = NewsReaction::where('user_id', $userId)
                                ->where('news_id', $newsId)
                                ->first();

        if ($existing) {
            if ($existing->reaction_type === $reactionType) {
                // إلغاء التفاعل
                $existing->delete();
                return response()->json(['message' => 'Reaction removed']);
            } else {
                // تغيير نوع التفاعل
                $existing->reaction_type = $reactionType;
                $existing->save();
                return response()->json(['message' => 'Reaction updated']);
            }
        } else {
            // إنشاء تفاعل جديد
            NewsReaction::create([
                'user_id' => $userId,
                'news_id' => $newsId,
                'reaction_type' => $reactionType,
            ]);
            return response()->json(['message' => 'Reaction added']);
        }
    }
}
