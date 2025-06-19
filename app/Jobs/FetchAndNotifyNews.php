<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\SourceNews;
use App\Models\User;
use App\Models\user_recomandations;
use App\Models\Notifications;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;






class FetchAndNotifyNews implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
         
        // روابط RSS
        $feeds = [
            'سانا' => 'https://www.sana.sy/?feed=rss2',
            'عنب بلدي' => 'https://www.enabbaladi.net/feed',
        ];

        foreach ($feeds as $source => $url) {
            try {
                $response = Http::get($url);
                $xml = simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NOCDATA);
                $items = collect($xml->channel->item);

                foreach ($items as $item) {
                    $link = (string) $item->link;

                    // تجاهل الخبر إذا كان مكرر
                    if (SourceNews::where('url', $link)->exists()) {
                        continue;
                    }

                    // إنشاء الخبر
                    $news = SourceNews::create([
                        'title' => (string) $item->title,
                        'content' => (string) $item->description,
                        'url' => $link,
                        'img_url' => null, // يمكنك استخراج الصورة لاحقًا
                        'category_id' => $this->guessCategory((string) $item->title), // تقدير التصنيف
                    ]);

                    // إرسال إشعار للمستخدمين المهتمين بهذا التصنيف
                    $this->notifyUsers($news->category_id, $news->id);
                }
            } catch (\Exception $e) {
                Log::error("⚠️ RSS Fetch error from $source: " . $e->getMessage());
            }
        
    }
}

    private function notifyUsers($category_id, $news_id)
    {
        $users = userRecomandation::where('category_id', $category_id)->pluck('user_id');

        foreach ($users as $user_id) {
            Notification::create([
                'user_id' => $user_id,
                'news_id' => $news_id,
            ]);
        }

        Log::info("🔔 Notifications sent for news $news_id to users: " . implode(', ', $users->toArray()));
    }
private function guessCategory(string $text): int
{
    // تحميل الملف فقط أول مرة
    static $keywords = null;

    if ($keywords === null) {
        $json = file_get_contents(storage_path('app/classification_keyword.json'));
        $keywords = json_decode($json, true);
    }

    $text = mb_strtolower($text); // لجعل المقارنة غير حساسة لحالة الأحرف
    $scores = [];

    foreach ($keywords as $category => $words) {
        $score = 0;
        foreach ($words as $word) {
            if (mb_strpos($text, mb_strtolower($word)) !== false) {
                $score++;
            }
        }
        $scores[$category] = $score;
    }

    // تحديد التصنيف صاحب أعلى تطابق
    arsort($scores);
    $topCategory = key($scores);

    if ($scores[$topCategory] === 0) {
        return 1; // تصنيف افتراضي عند عدم التطابق
    }

    // جلب الـ category_id من قاعدة البيانات حسب اسم التصنيف
    $category = \App\Models\Category::where('name', $topCategory)->first();
    return $category ? $category->id : 1;
}
}
