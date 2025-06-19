<?php

namespace App\Providers;


use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
class EventServiceProvider extends ServiceProvider
{
    /**
     * هنا مكانه الصحيح
     */
    protected $listen = [
        \App\Events\NewsAdded::class => [
            \App\Listeners\SendNewsNotification::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}
