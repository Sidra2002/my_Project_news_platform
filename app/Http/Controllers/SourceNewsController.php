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
    private static $classificationKeywords = null;
    private $defaultCategoryId = 13;

    public function fetchFromRss()
    {
        Log::info("عملية جلب الأخبار ");

        $sources = Source::all();

        foreach ($sources as $source) {
            Log::info(" معالجة المصدر: {$source->url}");

            try {
                $rss = simplexml_load_file($source->url);

                if (!$rss || !isset($rss->channel->item)) {
                    Log::warning(" الرابط لا يحتوي على  خبر", ['source' => $source->url]);
                    continue;
                }

                foreach ($rss->channel->item as $item) {
                    $title = (string) $item->title;
                    $description = (string) $item->description;
                    echo "  start  ";
                    try {
                        $content = $item->children('http://purl.org/rss/1.0/modules/content/')->encoded;
                        echo "good";
                    } catch (Exception $e) {
                        echo "false";
                        $content = "";
                    }
                    echo "  end  ";
                    $publishedAt = isset($item->pubDate) ? Carbon::parse($item->pubDate)->toDateTimeString() : Carbon::now();

                    $categoryId = $this->detectCategory($title);

                    if ($categoryId === null) {
                        echo "  sec if  ";
                        Log::warning("لم يتم تصنيف الخبر: $title");
                        continue;
                    }


                    // استخراج التصنيف
                    $categoryId = $this->detectCategory($title . ' ' . $description . ' ' . $content);
                    Log::info("تحديد التصنيف   $categoryId");
                    
                    // محاولة استخراج صورة (لو موجودة داخل content)
                    preg_match('/<img.*?src=["\'](.*?)["\']/', $content, $matches);
                    $imageUrl = $matches[1] ?? null;

                    $imageFileName = null;

                    if ($imageUrl) {
                        echo "  img yes  ";
                        try {
                            echo "  img try  ";
                            $imageContents = file_get_contents($imageUrl);
                            $ext = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'png';
                            $imageFileName = Str::uuid() . '.' . $ext;
                            \Illuminate\Support\Facades\File::ensureDirectoryExists(public_path('static/images'));
                            file_put_contents(public_path('static/images/' . $imageFileName), $imageContents);
                        } catch (\Exception $e) {
                            echo " img catch  ";
                            Log::warning("فشل تحميل الصورة: $imageUrl");
                            $imageFileName = null;
                        }
                    }

                    // تخزين الخبر إذا لم يكن موجود مسبقاً
                    if (!SourceNews::where('title', $title)->exists()) {
                        echo $imageFileName;
                        SourceNews::create([
                            'title' => $title,
                            'content' => strip_tags($description),
                            'category_id' => $categoryId,
                            'source_id' => $source->id,
                            'img_url' => $imageFileName,
                            'created_at' => $publishedAt,
                            'updated_at' => Carbon::now(),
                        ]);

                        Log::info("انضاف الخبر", ['title' => $title]);
                    }
                }
            } catch (\Exception $e) {
                Log::error("فشل تحميل  : {$source->url}", ['error' => $e->getMessage()]);
            }
        }

        return response()->json(['message' => 'تم جلب الأخبار بنجاح']);
    }



    private function detectCategory($text)
{
    if (self::$classificationKeywords === null) {
        self::$classificationKeywords = $this->loadClassificationKeywords();
    }

    $keywordsMapping = self::$classificationKeywords;

    if (!is_array($keywordsMapping)) {
        Log::warning(" ملف التصنيف غير صالح أو فارغ، سيتم استخدام التصنيف العام.");
        return $this->defaultCategoryId;
    }

    $categoryScores = [];
    foreach ($keywordsMapping as $categoryName => $keywords) {
        $score = 0;
        foreach ($keywords as $keyword) {
            if (mb_stripos($text, $keyword) !== false) {
                $score++;
            }
        }

        // نضع شرط العتبة
        if ($score >= 2) {
            $categoryScores[$categoryName] = $score;
        }
    }

    if (!empty($categoryScores)) {
        arsort($categoryScores);
        $topCategoryName = array_key_first($categoryScores);
        $category = Category::where('name', $topCategoryName)->first();
        if ($category) {
            return $category->id;
        }
    }

    // في حال لم يتم الكشف
    return $this->defaultCategoryId;
}


    private function loadClassificationKeywords()
    {
        $filePath = storage_path('app/classification_keyword.json');

        if (!file_exists($filePath)) {
            Log::warning(" ملف التصنيف غير موجود: {$filePath}");
            return null;
        }

        try {
            $json = file_get_contents($filePath);
            $data = json_decode($json, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error(" خطأ في تنسيق JSON: " . json_last_error_msg());
                return null;
            }

            Log::info(" تم تحميل ملف التصنيف بنجاح.");
            return $data;
        } catch (\Exception $e) {
            Log::error(" فشل في قراءة ملف التصنيف", ['error' => $e->getMessage()]);
            return null;
        }
    }
    public function getAllNews()
{
    $news = SourceNews::select('id', 'title', 'content', 'img_url', 'source_id', 'category_id', 'created_at', 'updated_at')
        ->orderByDesc('created_at')
        ->get();

    return response()->json($news);
}

    }

