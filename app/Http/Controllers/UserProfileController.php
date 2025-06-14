<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;


class UserProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        $user->load(['preferences.category']); // eager load

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'preferences' => $user->preferences->map(function ($pref) {
                return [
                    'id' => $pref->category->id,
                    'name' => $pref->category->name,
                ];
            }),
        ]);
    }



    // تعديل معلومات المستخدم
    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'preferences' => 'sometimes|array',
            'preferences.*' => 'exists:categories,id',
        ]);

        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }
        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }
        $user->save();

        if (isset($validated['preferences'])) {
            UserRecomandation::where('user_id', $user->id)->delete();

            foreach ($validated['preferences'] as $categoryId) {
                UserRecomandation::create([
                    'user_id' => $user->id,
                    'category_id' => $categoryId,
                ]);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Profile updated successfully.',
        ]);
    }

}