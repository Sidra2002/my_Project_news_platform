<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class checked_users_News extends Model
{
    //
    protected $fillable = [
        'title',
        'content',
        'user_id',
    ];

    // Relationships

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    
}
