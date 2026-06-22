<?php

namespace App\Enums;

/**
 * [REVIEW-FIX-R5 #1 P3] 引入 PHP 8.1 backed enum 集中管理工单优先级。
 *
 * 第2-4轮多次发现优先级枚举在验证规则、Blade 模板、模型 label/color、SLA、
 * Zabbix 映射、Bot 映射等处散落为裸字符串，导致 urgent/critical 不一致、
 * 缺少验证规则等问题。本 Enum 作为单一事实来源，未来各处可逐步迁移引用。
 *
 * 用法:
 *   TicketPriority::from('critical')          // -> TicketPriority::Critical
 *   TicketPriority::Critical->value           // -> 'critical'
 *   TicketPriority::Critical->label()         // -> '紧急'
 *   TicketPriority::Critical->color()         // -> 'red'
 *   TicketPriority::cases()                   // -> 全部枚举值
 *   TicketPriority::values()                  // -> ['low','medium','high','critical']
 *   TicketPriority::validationRule()          // -> 'in:low,medium,high,critical'
 */
enum TicketPriority: string
{
    case Low      = 'low';
    case Medium   = 'medium';
    case High     = 'high';
    case Critical = 'critical';

    public function label(): string
    {
        return match($this) {
            self::Low      => __('低'),
            self::Medium   => __('中'),
            self::High     => __('高'),
            self::Critical => __('紧急'),
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Low      => 'zinc',
            self::Medium   => 'sky',
            self::High     => 'amber',
            self::Critical => 'red',
        };
    }

    /** SLA 排序权重（数字越小优先级越高） */
    public function sortOrder(): int
    {
        return match($this) {
            self::Critical => 0,
            self::High     => 1,
            self::Medium   => 2,
            self::Low      => 3,
        };
    }

    /** 生成 Laravel 验证规则字符串，如 'in:low,medium,high,critical' */
    public static function validationRule(): string
    {
        return 'in:' . implode(',', array_map(fn($c) => $c->value, self::cases()));
    }

    /** 获取所有值的数组 */
    public static function values(): array
    {
        return array_map(fn($c) => $c->value, self::cases());
    }
}
