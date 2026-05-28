<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// AD 用户定时同步
// 执行间隔由 AD_SYNC_INTERVAL (分钟) 控制，默认 60 分钟
$syncInterval = (int) env('AD_SYNC_INTERVAL', 60);
if ($syncInterval > 0 && env('AD_AUTH_ENABLED') === 'true') {
    Schedule::command('ad:sync-users')
        ->everyMinutes($syncInterval)
        ->withoutOverlapping()
        ->runInBackground()
        ->appendOutputTo(storage_path('logs/ad-sync.log'));
}
