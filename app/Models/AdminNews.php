<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdminNews extends Model
{
    //
    use HasFactory;

   
    protected $table = 'admin_news';
    //
    protected $fillable = [
        'title',
        'content',
        'category_id',
        'img_url',
        
    ];

    // Relationships

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    
}
