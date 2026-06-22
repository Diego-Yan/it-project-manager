<?php

namespace App\Enums;

/**
 * [REVIEW-FIX-R5 #1 P3] 引入 PHP 8.1 backed enum 集中管理工单状态。
 *
 * 与 TicketPriority 配套，集中管理工单生命周期状态，防止状态字符串散落不一致。
 *
 * 用法:
 *   TicketStatus::from('open')->label()       // -> '未处理'
 *   TicketStatus::Open->canTransitionTo(TicketStatus::InProgress)  // -> true
 *   TicketStatus::values()                    // -> ['open','in_progress','resolved','closed']
 */
enum TicketStatus: string
{
    case Open       = 'open';
    case InProgress = 'in_progress';
    case Resolved   = 'resolved';
    case Closed     = 'closed';

    public function label(): string
    {
        return match($this) {
            self::Open       => __('未处理'),
            self::InProgress => __('处理中'),
            self::Resolved   => __('已解决'),
            self::Closed     => __('已关闭'),
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Open       => 'amber',
            self::InProgress => 'sky',
            self::Resolved   => 'green',
            self::Closed     => 'zinc',
        };
    }

    /** 当前状态允许的目标状态 */
    public function allowedTransitions(): array
    {
        return match($this) {
            self::Open       => [self::InProgress],
            self::InProgress => [self::Resolved],
            self::Resolved   => [self::Closed],
            self::Closed     => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public static function values(): array
    {
        return array_map(fn($c) => $c->value, self::cases());
    }

    public static function validationRule(): string
    {
        return 'in:' . implode(',', self::values());
    }
}
