<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SourceNews;
use App\Models\category;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;



class SearchController extends Controller
{
     public function search(Request $request)
    {
        $query = $request->input('q');
        Log::info(" Search query raw: {$query}");
        if (! $query) {
            Log::warning(' Empty query received');
            return response()->json(['error' => 'Query is required'], 400);
        }

        // 1. جلب جميع الأخبار
        $allNews = SourceNews::select('id','title','content','img_url','created_at')->get();
        Log::info(' Total news fetched: ' . $allNews->count());

        // 2. تجهيز الاستعلام: تنظيف + تجذير
        $queryWords = $this->preprocessText($query);
        Log::info(' Query words after preprocess: ' . implode(', ', $queryWords));

        // 3. preprocess لكل مستند وبناء مصفوفة كلمات
        $initialDocsWords = [];
        foreach ($allNews as $news) {
            $text = html_entity_decode(strip_tags($news->title . ' ' . $news->content));
            $initialDocsWords[$news->id] = $this->preprocessText($text);
        }

        // 4. حساب IDF منعم على كامل المستندات
        $idf = $this->computeIDF($initialDocsWords);
        Log::info(' Initial IDF computed for ' . count($idf) . ' terms');

        // 5. تطبيق فلترة AND إذا كان الاستعلام أكثر من كلمة
        $docsWords = $initialDocsWords;
        $filteredNews = $allNews;
        if (count($queryWords) > 1) {
            $filteredNews = $allNews->filter(function($news) use ($docsWords, $queryWords) {
                $words = $docsWords[$news->id] ?? [];
                foreach ($queryWords as $qw) {
                    if (! in_array($qw, $words)) {
                        return false;
                    }
                }
                return true;
            })->values();
            Log::info(' News count after AND filter: ' . $filteredNews->count());

            if ($filteredNews->isEmpty()) {
                Log::info(' No news matched AND filter, returning empty array');
                return response()->json([]);
            }
            // إبقاء كل الهياكل متزامنة
            $docsWords = array_filter($docsWords, fn($w, $id) => $filteredNews->pluck('id')->contains($id), ARRAY_FILTER_USE_BOTH);
        }

        // 6. بناء متجه TF‑IDF لكل مستند متبقٍ
        $docsTFIDF = [];
        foreach ($docsWords as $id => $words) {
            $tf = $this->computeTF($words);
            $docsTFIDF[$id] = $this->computeTFIDF($tf, $idf);
            Log::info(" News ID {$id} TF‑IDF vector size: " . count($docsTFIDF[$id]));
        }

        // 7. تجهيز استعلام المستخدم كمتجه TF‑IDF
        $queryTF     = $this->computeTF($queryWords);
        $queryTFIDF  = $this->computeTFIDF($queryTF, $idf);
        Log::info(' Query TF‑IDF vector size: ' . count($queryTFIDF));

        // 8. حساب درجات Cosine similarity
        $scores = [];
        foreach ($docsTFIDF as $id => $vector) {
            $scores[$id] = $this->cosineSimilarity($queryTFIDF, $vector);
            Log::info(" News ID {$id} cosine score: " . $scores[$id]);
        }
        arsort($scores);

        // 9. تجميع النتائج (score > 0)
        $results = [];
        foreach ($scores as $id => $score) {
            if ($score > 0) {
                $results[] = $filteredNews->firstWhere('id', $id);
            }
        }
        Log::info(' Results count with score > 0: ' . count($results));

        return response()->json($results);
    }

    /**
     * تنظيف النص (normalization) + تجذير خفيف Arabic light stemming
     */
    private function preprocessText(string $text): array
    {
        //  إلى حروف صغيرة
        $text = mb_strtolower($text, 'UTF-8');
        //  إزالة التشكيل
        $text = preg_replace('/[\x{064B}-\x{0652}\x{0670}]/u', '', $text);
        //  توحيد الألف
        $text = str_replace(['أ','إ','آ','ٱ'], 'ا', $text);
        //  توحيد الهاء المربوطة
        $text = str_replace('ة','ه',$text);
        //  إزالة الـ التعريف
        $text = preg_replace('/\bال(?=[\p{Arabic}])/u','',$text);
        //  استبدال غير الحروف/أرقام بمسافة
        $text = preg_replace('/[^\p{Arabic}\p{L}\p{N}\s]/u',' ',$text);
        //  حذف الفراغات الزائدة
        $text = preg_replace('/\s+/',' ',trim($text));

        //  تجزيء إلى كلمات
        $words = explode(' ',$text);

        //  تطبيق التجذير الخفيف
        $stemmed = array_map([$this,'arabicLightStem'], $words);

        //  إزالة stopwords وتصفية الكلمات القصيرة
        $stopwords = ['في','من','على','عن','إلى','التي','الذي','أن','إن','ما','لا','سوريا','بعد','قبل','الا'];
        return array_values(array_filter($stemmed, fn($w) =>
            ! in_array($w, $stopwords) && mb_strlen($w) > 2
        ));
    }

    /**
     * Arabic Light Stemmer: إزالة بادئات ولواحق شائعة
     */
   private function arabicLightStem(string $word): string
{
    // بادئات أساسية فقط (لاحقة التعريف والروابط)
    $prefixes = [
        'ال',   // التعريف
        'وال',  // واو العاطفة + التعريف
        'فال',  // فاء السببية + التعريف
        'بال',  // باء السببية + التعريف
        'كال',  // كاف التشبيه + التعريف
        'لل',   // لام الملكية + التعريف
    ];

    foreach ($prefixes as $p) {
        if (mb_strpos($word, $p) === 0 && mb_strlen($word) - mb_strlen($p) >= 3) {
            $word = mb_substr($word, mb_strlen($p));
            break;
        }
    }

    // لواحق بسيطة: جمع، تأنيث، ضمائر متصلة
    $suffixes = [
        'ون', 'ين',   // جمع المذكر
        'ات',         // جمع المؤنث
        'ة',          // تاء التأنيث
        'ه', 'ت',     // هاء الغائب، تاء التأنيث/المضارع
        'ي', 'ك',     // ياء المتكلم، كاف المخاطب
        'نا', 'كم', 'كن', 'هم', 'هما', 'ها', // ضمائر
    ];

    foreach ($suffixes as $s) {
        if (mb_substr($word, -mb_strlen($s)) === $s
            && mb_strlen($word) - mb_strlen($s) >= 3) {
            $word = mb_substr($word, 0, mb_strlen($word) - mb_strlen($s));
            break;
        }
    }

    return $word;
}

    /** Term Frequency */
    private function computeTF(array $words): array
    {
        $tf = []; $count = count($words);
        foreach ($words as $w) {
            $tf[$w] = ($tf[$w] ?? 0) + 1;
        }
        foreach ($tf as $w => $freq) {
            $tf[$w] = $freq / $count;
        }
        return $tf;
    }

    /** Inverse Document Frequency مع التنعيم */
    private function computeIDF(array $docs): array
    {
        $df = []; $N = count($docs);
        foreach ($docs as $words) {
            foreach (array_unique($words) as $w) {
                $df[$w] = ($df[$w] ?? 0) + 1;
            }
        }
        foreach ($df as $w => $docFreq) {
            // log((N+1)/(df+1)) + 1 لتجنب صفرية وتنعيم
            $df[$w] = log(($N + 1) / ($docFreq + 1)) + 1;
        }
        return $df;
    }

    /** بناء متجه TF‑IDF */
    private function computeTFIDF(array $tf, array $idf): array
    {
        $v = [];
        foreach ($tf as $w => $val) {
            $v[$w] = $val * ($idf[$w] ?? 0);
        }
        return $v;
    }

    /** Cosine Similarity بين متجهين */
    private function cosineSimilarity(array $v1, array $v2): float
    {
        $dot = $n1 = $n2 = 0;
        $keys = array_unique(array_merge(array_keys($v1), array_keys($v2)));
        foreach ($keys as $w) {
            $a = $v1[$w] ?? 0;
            $b = $v2[$w] ?? 0;
            $dot += $a * $b;
            $n1  += $a * $a;
            $n2  += $b * $b;
        }
        return ($n1 && $n2) ? ($dot / sqrt($n1 * $n2)) : 0;
    }

 
    public function filterByCategory(Request $request, $categoryName)
{
    // البحث عن التصنيف بالاسم
    $category = category::where('name', $categoryName)->first();

    // إذا لم يُعثر على التصنيف
    if (!$category) {
        return response()->json(['error' => 'Category not found'], 404);
    }

    // جلب الأخبار المرتبطة بهذا التصنيف
    $news = SourceNews::where('category_id', $category->id)
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json($news);
}

}
