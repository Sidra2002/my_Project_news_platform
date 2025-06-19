<?php

namespace App\Listeners;

use App\Events\NewsAdded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\user_recomandations;
use App\Models\Notifications;
use App\Models\User;
use App\Services\FirebaseService;


class SendNewsNotification
{
    protected $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    public function handle(NewsAdded $event): void
    {
        $news = $event->news;

        $userIds = user_recomandations::where('category_id', $news->category_id)->pluck('user_id');

        foreach ($userIds as $userId) {
            // حفظ الإشعار في قاعدة البيانات
            Notifications::create([
                'user_id' => $userId,
                'news_id' => $news->id,
                'seen' => false,
            ]);

            // جلب التوكن للمستخدم (يفترض أن يكون لديك حقل firebase_token في جدول users)
            $user = User::find($userId);
            if ($user && $user->firebase_token) {
                $this->firebase->sendNotification(
                    $user->firebase_token,
                    $news->title,
                    substr(strip_tags($news->content), 0, 100) . '...' // جزء من المحتوى
                );
            }
        }
    }
}