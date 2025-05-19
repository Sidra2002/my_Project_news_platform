<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SourceNews extends Model
{
    //
    protected $fillable = [
        'title',
        'content',
        'img_url',
        'category_id',
        'source_id',
    ];

// Relationships

public function category()
{
    return $this->belongsTo(Category::class);
}

public function source()
{
    return $this->belongsTo(Source::class);
}



}
