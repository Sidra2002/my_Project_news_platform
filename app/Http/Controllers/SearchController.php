<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SourceNews;
use App\Models\category;
use Illuminate\Support\Collection;



class SearchController extends Controller
{
    
    public function filterByCategory(Request $request, $categoryName)
{
    // البحث عن التصنيف بالاسم
    $category = category::where('name', $categoryName)->first();

    // إذا لم يُعثر على التصنيف
    if (!$category) {
        return response()->json(['error' => 'Category not found'], 404);
    }

    // جلب الأخبار المرتبطة بهذا التصنيف
    $news = SourceNews::where('category_id', $category->id)
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json($news);
}
}
