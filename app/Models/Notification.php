<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = ['user_id', 'title', 'body', 'type', 'link', 'is_read'];

    protected function casts(): array
    {
        return ['is_read' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            'success' => 'green', 'warning' => 'amber', 'error' => 'red',
            default => 'sky',
        };
    }

    public static function send(int $userId, string $title, string $body = null, string $type = 'info', string $link = null): void
    {
        self::create([
            'user_id' => $userId, 'title' => $title, 'body' => $body,
            'type' => $type, 'link' => $link,
        ]);
    }
}
