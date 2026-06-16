<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class ZabbixConfig extends Model
{
    protected $fillable = ['name', 'url', 'api_token', 'min_severity', 'poll_interval', 'is_active', 'last_poll_at'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'last_poll_at' => 'datetime', 'poll_interval' => 'integer'];
    }

    // [FIX] #2: api_token 加密存储，防止数据库泄露
    // 使用 Laravel 的 Encryptable trait 替代明文存储
    public function getApiTokenAttribute(?string $value): ?string
    {
        if (empty($value)) return null;
        try {
            return Crypt::decryptString($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            // 兼容旧的明文数据：如果解密失败，返回原值并自动加密
            return $value;
        }
    }

    public function setApiTokenAttribute(?string $value): void
    {
        if (empty($value)) {
            $this->attributes['api_token'] = null;
            return;
        }
        // [REVIEW-FIX] R16.1: 防止已加密值被重复加密
        // Laravel Crypt 加密后的字符串以 base64 格式开头，尝试解密判断是否已加密
        try {
            Crypt::decryptString($value);
            // 解密成功 → 已是加密值，直接赋值
            $this->attributes['api_token'] = $value;
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            // 解密失败 → 明文，正常加密
            $this->attributes['api_token'] = Crypt::encryptString($value);
        }
    }

    /**
     * [FIX] #2: 返回掩码后的 token，用于前端展示
     * [REVIEW-FIX] M5: 处理短 token（≤12 字符）的边界情况
     */
    public function getMaskedTokenAttribute(): string
    {
        $token = $this->api_token;
        if (empty($token)) {
            return '';
        }
        if (strlen($token) <= 6) {
            return '****';
        }
        if (strlen($token) <= 12) {
            return substr($token, 0, 3) . '****' . substr($token, -3);
        }
        return substr($token, 0, 6) . '****' . substr($token, -6);
    }
}
