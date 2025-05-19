<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class user_recomandations extends Model
{
    //
    protected $fillable = [
        
        'category_id',
        'user_id',
        
    ];

    // Relationships

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
