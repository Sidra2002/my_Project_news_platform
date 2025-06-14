<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    //
    public function index(): JsonResponse
    {
        $categories = Category::select('id', 'name')->get();
        return response()->json([
            'status' => true,
            'categories' => $categories,
        ]);
    }
}
