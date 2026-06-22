<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// AD 用户定时同步
// [REVIEW-FIX] I2+M8: 使用 config() 替代 env()，兼容 config:cache
$syncInterval = (int) config('ad-auth.sync_interval', 60);
if ($syncInterval > 0 && config('ad-auth.enabled')) {
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

// [REVIEW-FIX] C4: 每日自动备份 SQLite 数据库
// [REVIEW-FIX-R1 #4 P2] 调度补上 --prune 选项：原调度未带该参数，旧备份永不清理，
// 导致 storage/app/backups/ 目录持续增长直至磁盘写满。现在每日自动清理 30 天前旧备份。
Schedule::command('db:backup --prune')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/db-backup.log'));

// [REVIEW-FIX-R3 #1 P1] 补充缺失的队列 worker 调度。
// 问题：QUEUE_CONNECTION=database（.env/.env.example 均如此），SendWebhookNotification Job
// 通过 dispatch() 投递到 database 队列，但 console.php 中无 queue:work 调度，
// 也无 systemd/supervisor 常驻 worker 配置 → 队列任务永远不会被消费 → webhook 通知形同虚设。
// 修复：每分钟运行 queue:work --stop-when-empty，处理完积压任务后自动退出，
// 避免长驻进程的内存泄漏风险（SQLite + 小型部署的推荐方案）。
Schedule::command('queue:work --stop-when-empty --max-time=60')
    ->everyMinute()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/queue-worker.log'));

// [REVIEW-FIX-R4 #5 P3] 补充 failed_jobs 表定时清理：
// 队列 Job 重试 3 次失败后会写入 failed_jobs 表，无清理机制则该表无限增长。
// 每日凌晨 4:00 清理 7 天前的失败 Job 记录（保留近期记录供排障）。
Schedule::command('queue:prune-failed --hours=168')
    ->dailyAt('04:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/queue-prune.log'));
