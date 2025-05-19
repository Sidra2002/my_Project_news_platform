<?php

namespace App\Http\Controllers;

use App\Models\Source;
use App\Models\Category;
use App\Models\SourceNews;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;

class SourceNewsController extends Controller
{
    public function fetchFromRss()
{
    $sources = Source::all();

    foreach ($sources as $source) {
        try {
            // قراءة ملف RSS باستخدام SimpleXML
            $rss = simplexml_load_file($source->url);

            if (!$rss || !isset($rss->channel->item)) {
                Log::warning("الرابط لا يحتوي على عناصر خبر", ['source' => $source->url]);
                continue;
            }

            foreach ($rss->channel->item as $item) {
                $title = (string) $item->title;
                $content = (string) $item->description;
                $publishedAt = isset($item->pubDate) ? Carbon::parse($item->pubDate)->toDateTimeString() : Carbon::now();

                $categoryId = $this->detectCategory($title);
                
if ($categoryId === null) {
    Log::warning("لم يتم تصنيف الخبر: $title");
    continue;
}

                // محاولة استخراج صورة (لو موجودة داخل content)
                preg_match('/<img.*?src=["\'](.*?)["\']/', $content, $matches);
                $imageUrl = $matches[1] ?? null;

                $imageFileName = 'default.png';

                if ($imageUrl) {
                    try {
                        $imageContents = file_get_contents($imageUrl);
                        $ext = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'png';
                        $imageFileName = Str::uuid() . '.' . $ext;

                        \Illuminate\Support\Facades\File::ensureDirectoryExists(public_path('static/images'));
                        file_put_contents(public_path('static/images/' . $imageFileName), $imageContents);
                    } catch (\Exception $e) {
                        Log::warning("فشل تحميل الصورة: $imageUrl");
                        $imageFileName = 'default.png';
                    }
                }

                // تخزين الخبر إذا لم يكن موجود مسبقاً
                if (!SourceNews::where('title', $title)->exists()) {
                    SourceNews::create([
                        'title' => $title,
                        'content' => strip_tags($content),
                        'category_id' => $categoryId,
                        'source_id' => $source->id,
                        'img_url' => $imageFileName,
                        'created_at' => $publishedAt,
                        'updated_at' => Carbon::now(),
                    ]);

                    Log::info("تمت إضافة خبر جديد", ['title' => $title]);
                }
            }

        } catch (\Exception $e) {
            Log::error("فشل تحميل أو معالجة RSS من المصدر: {$source->url}", ['error' => $e->getMessage()]);
        }
    }

    return response()->json(['message' => 'تم جلب الأخبار بنجاح']);
}

         private function detectCategory($title)
    {
        $keywords = [
        'Politics' => [
            'رئيس', 'رئاسة', 'برلمان', 'مجلس', 'وزير', 'وزارة', 'حكومة', 'سياسة', 'سياسي', 'برلماني',
            'انتخابات', 'تحالف', 'حزب', 'أحزاب', 'قانون', 'دستور', 'تصويت', 'سلطة', 'إصلاح', 'رئاسة الوزراء',
            'المعارضة', 'السلطة', 'سيادي', 'قرارات', 'جلسة', 'تشريع', 'تشريعي', 'إقالة', 'تعديل وزاري', 'مبعوث',
            'الخارجية', 'مفاوضات', 'اتفاق', 'معاهدة', 'أزمة سياسية'
        ],
            'Economy' => [
            'اقتصاد', 'بنك', 'مصرف', 'الليرة', 'الدولار', 'الذهب', 'العملات', 'أسواق', 'بورصة', 'تضخم',
            'ضرائب', 'موازنة', 'ميزانية', 'رواتب', 'سعر الصرف', 'دعم', 'استثمار', 'مشروع', 'الشركات', 'أرباح',
            'خسائر', 'تجارة', 'صادرات', 'واردات', 'نمو', 'انكماش', 'ديون', 'سندات', 'تمويل', 'عجز',
            'عقار', 'عقارات', 'الإنتاج', 'الصناعة'
        ],
             'Technology' => [
            'تكنولوجيا', 'تقنية', 'ذكاء اصطناعي', 'إنترنت', 'شبكات', 'تطبيق', 'تطبيقات', 'برمجة', 'أندرويد', 'آيفون',
            'حاسوب', 'كمبيوتر', 'روبوت', 'موبايل', 'هواتف', 'برمجيات', 'أمن سيبراني', 'الاختراق', 'تسريبات', 'تشفير',
            'ألعاب إلكترونية', 'الواقع الافتراضي', 'ميتا', 'ميتافيرس', 'رقمي', 'كود', 'أبل', 'غوغل', 'سامسونغ', 'نظام تشغيل',
            'ذكاء رقمي', 'خوارزمية', 'معالجة البيانات'
        ],
            'Health' => [
            'صحة', 'مريض', 'مستشفى', 'مشفى', 'دواء', 'أدوية', 'لقاح', 'تطعيم', 'علاج', 'عناية',
            'فيروس', 'كورونا', 'أوبئة', 'انفلونزا', 'أعراض', 'تشخيص', 'تحاليل', 'طبيب', 'عيادة', 'مرض',
            'إصابات', 'إصابة', 'وفيات', 'وفاة', 'نقل دم', 'كوفيد', 'جرعة', 'ضغط', 'سكري', 'حرارة',
            'جلطة', 'غرق', 'حادث'
        ],
            'Sports' => [
            'رياضة', 'كرة قدم', 'كرة سلة', 'لاعب', 'منتخب', 'مباراة', 'بطولة', 'كأس', 'أهداف', 'ملعب',
            'شوط', 'فوز', 'خسارة', 'تعادل', 'تصفيات', 'مدرب', 'النتيجة', 'الدوري', 'ترتيب', 'نقطة',
            'جولة', 'نادي', 'تحكيم', 'ركلة جزاء', 'اللاعبون', 'هزيمة', 'أداء', 'إصابة رياضية', 'سباق', 'ألعاب قوى',
            'مونديال', 'الأولمبياد'
        ],
        ];
         $title = Str::lower(trim($title));

        foreach ($keywords as $category => $words) {
            foreach ($words as $word) {
                if (Str::contains($title, $word)) {
                    $cat = Category::where('name', $category)->first();
                    return $cat ? $cat->id : null;
                }
            }
        }
        
        // تصنيف افتراضي: Uncategorized
        $defaultCategory = Category::firstOrCreate(
            ['name' => 'Uncategorized'],
            ['created_at' => now(), 'updated_at' => now()]
        );

        return $defaultCategory->id;
           
    }
        
}
    
      
    

   


