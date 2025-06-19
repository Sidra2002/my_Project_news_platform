<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SourceNews;
use App\Models\user_recomandations;
use App\Models\Notifications;  
use App\Events\NewsAdded;




class ManualNewsController extends Controller
{
    //
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'content' => 'required|string',
            'img_url' => 'nullable|url',
            'source_id' => 'required|exists:sources,id',
            'category_id' => 'nullable|exists:categories,id',
            'created_at' => 'nullable|date',
            'updated_at' => 'nullable|date',
        ]);

        $news = SourceNews::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'img_url' => $validated['img_url'] ?? null,
            'source_id' => $validated['source_id'],
            'category_id' => $validated['category_id'],
            'created_at' => $validated['created_at'] ?? now(),
            'updated_at' => $validated['updated_at'] ?? now(),
        ]);

        // إطلاق الحدث
        event(new NewsAdded($news));

        return response()->json([
            'message' => '✅ تمت إضافة الخبر وتم إطلاق الحدث بنجاح.',
            'news' => $news
        ]);
    }
}

