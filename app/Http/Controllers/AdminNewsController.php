<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdminNews;
use Illuminate\Support\Str;

class AdminNewsController extends Controller
{
  //
  public function store(Request $request)
{
    //Validation
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'content' => 'required|string',
        'category_id' => 'required|exists:categories,id',
       

     ]);


// Store_Image
$image = $request->file('image');
$imageName = Str::uuid() . '.' . $image->getClientOriginalExtension();
$image->move(public_path('static/images'), $imageName);


//Creat_New
$news = AdminNews::create([
  'title' => $validated['title'],
  'content' => $validated['content'],
  'category_id' => $validated['category_id'],
  'img_url' => $imageName,
  ]);



//Check?True
return response()->json([
  'message' => 'Admin news created successfully.',
  'data' => $news
], 201);

}
}

