<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\user_recomandations;
use Illuminate\Support\Facades\Auth;


class UserRecomandationsController extends Controller
{
    //
    public function store(Request $request)
    {
        $request->validate([
            'category_ids' => 'required|array',
            'category_ids.*' => 'exists:categories,id',
       ]);

        $user = Auth::user();

        // حذف التوصيات السابقة (إن وجدت)
        user_recomandations::where('user_id', $user->id)->delete();

        // إضافة التوصيات الجديدة
        foreach ($request->category_ids as $categoryId) {
             user_recomandations::create([
                'user_id' => $user->id,
                'category_id' => $categoryId,
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Preferences saved successfully.',
        ]);
    }
}
