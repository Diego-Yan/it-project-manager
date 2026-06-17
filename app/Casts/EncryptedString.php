<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * [REVIEW-FIX] T5: 统一加密字段 Cast
 *
 * 替代 WebhookConfig 和 ZabbixConfig 中重复的 getXxxAttribute/setXxxAttribute 模式。
 * 用法: protected function casts(): array { return ['url' => EncryptedString::class]; }
 */
class EncryptedString implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (empty($value)) return null;
        try {
            return Crypt::decryptString($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            return $value; // 兼容未加密旧数据
        }
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (empty($value)) return null;
        try {
            Crypt::decryptString($value); // 已是加密值 → 直接存储
            return $value;
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            return Crypt::encryptString($value);
        }
    }
}
