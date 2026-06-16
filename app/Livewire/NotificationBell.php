<?php

namespace App\Livewire;

use App\Models\Notification;
use Livewire\Component;

class NotificationBell extends Component
{
    public bool $showDropdown = false;
    public int $unreadCount = 0;
    public $notifications;

    public function mount(): void
    {
        $this->loadNotifications();
    }

    public function toggleDropdown(): void
    {
        $this->showDropdown = !$this->showDropdown;
        if ($this->showDropdown) {
            $this->loadNotifications();
        }
    }

    public function markRead(int $id): void
    {
        Notification::where('id', $id)
            ->where('user_id', auth()->id())
            ->update(['is_read' => true]);
        $this->loadNotifications();
    }

    public function markAllRead(): void
    {
        Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true]);
        $this->loadNotifications();
    }

    // [REVIEW-FIX] R7.1: 2次查询(get+count)→1次查询，未读数从 collection 计算
    private function loadNotifications(): void
    {
        $this->notifications = Notification::where('user_id', auth()->id())
            ->latest()->limit(20)->get();
        $this->unreadCount = $this->notifications->where('is_read', false)->count();
    }

    public function render()
    {
        return view('livewire.notification-bell');
    }
}
