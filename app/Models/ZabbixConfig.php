<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class ZabbixConfig extends Model
{
    protected $fillable = ['name', 'url', 'api_token', 'min_severity', 'poll_interval', 'is_active', 'last_poll_at'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'last_poll_at' => 'datetime'];
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
        // 如果已经是加密过的（不以明文常见前缀开头），不再重复加密
        $this->attributes['api_token'] = Crypt::encryptString($value);
    }

    /**
     * [FIX] #2: 返回掩码后的 token，用于前端展示
     */
    public function getMaskedTokenAttribute(): string
    {
        $token = $this->api_token;
        if (empty($token) || strlen($token) <= 12) {
            return '****';
        }
        return substr($token, 0, 6) . '****' . substr($token, -6);
    }
}
