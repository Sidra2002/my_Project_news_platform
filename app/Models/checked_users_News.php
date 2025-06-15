<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class checked_users_News extends Model
{
    //
    protected $fillable = [
       'content',
        'user_id',
        'is_fake',
    ];

    // Relationships

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    
}
