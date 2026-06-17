<?php

namespace App\Traits;

use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

/**
 * [REVIEW-FIX] C4: 统一的 webhook 发送 trait
 *
 * 替代 TicketBoard/ProjectDetail/MyTasks 中 ~12 处重复的 try/catch 块。
 * 用法: $this->dispatchWebhook('ticket.created', [...]);
 */
trait DispatchesWebhooks
{
    protected function dispatchWebhook(string $event, array $payload): void
    {
        try {
            NotificationService::send($event, $payload);
        } catch (\Throwable $e) {
            Log::warning("Webhook dispatch failed: {$event}", [
                'error'   => $e->getMessage(),
                'payload' => $payload,
            ]);
        }
    }
}
