<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notifications extends Model
{
    //



// السماح بالحقول التي يمكن تعبئتها تلقائيًا
    protected $fillable = ['user_id', 'news_id', 'seen'];


    
    public function news()
{
    return $this->belongsTo(\App\Models\SourceNews::class, 'news_id');
}


}
