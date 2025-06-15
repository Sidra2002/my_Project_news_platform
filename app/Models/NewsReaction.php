<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsReaction extends Model
{
    //
    protected $fillable = ['user_id', 'news_id', 'reaction_type'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function news()
    {
        return $this->belongsTo(SourceNews::class, 'news_id');
    }
    public function getReactionLabelAttribute()
    {
        return match ($this->reaction_type) {
            1 => 'like',
            -1 => 'dislike',
            default => 'unknown',
        };
    }
}
