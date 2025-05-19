<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        Log::info('📌 schedule method was loaded');
        // أمر الجلب المجدول
        $schedule->command('app:fetch-rss-news')->everyMinute()->before(function () {
            Log::info('✅ Running scheduled fetch command...');
        });

        // أمر تجريبي يظهر إذا الكرون يشتغل أصلاً
        $schedule->call(function () {
            Log::info('🧪 Test cron log at: ' . now());
        })->everyMinute();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
