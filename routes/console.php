<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// AD 用户定时同步
$syncInterval = (int) env('AD_SYNC_INTERVAL', 60);
if ($syncInterval > 0 && env('AD_AUTH_ENABLED') === 'true') {
    Schedule::command('ad:sync-users')
        ->everyMinutes($syncInterval)
        ->withoutOverlapping()
        ->runInBackground()
        ->appendOutputTo(storage_path('logs/ad-sync.log'));
}

// [REVIEW-FIX] R6.1: 补充缺失的定时调度 — 这三个命令之前只有定义没有触发
// 截止日期检查：每天 9:00 和 15:00
Schedule::command('check:deadlines')
    ->twiceDaily(9, 15)
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/deadlines-check.log'));

// 每日项目概报：每天早上 8:30
Schedule::command('daily:digest')
    ->dailyAt('08:30')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/daily-digest.log'));

// Zabbix 告警轮询：每 5 分钟
Schedule::command('zabbix:poll')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/zabbix-poll.log'));
