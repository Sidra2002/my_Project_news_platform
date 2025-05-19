<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\SourceNewsController;

class FetchRssNews extends Command
{
    /**
     * اسم الأمر الذي سيتم استخدامه في terminal.
     */
    protected $signature = 'app:fetch-rss-news';

    /**
     * وصف الأمر (يظهر في قائمة الأوامر).
     */
    protected $description = 'Fetch and store news from RSS feeds.';

    /**
     * تنفيذ الأمر.
     */
    public function handle()
    {
        // استدعاء دالة الجلب من الكنترولر مباشرة
        app(SourceNewsController::class)->fetchFromRss();

        // رسالة نجاح تظهر عند التشغيل اليدوي للأمر
        $this->info('✅ Fetch Successfully');
        

    }
}
