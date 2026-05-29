<?php

namespace App\Livewire\Admin;

use Livewire\Component;

class BotSettings extends Component
{
    public function render()
    {
        $wechatUrl = url('/api/bot/wechat');
        $dingtalkUrl = url('/api/bot/dingtalk');

        return view('livewire.admin.bot-settings', compact('wechatUrl', 'dingtalkUrl'))
            ->layout('layouts.app', ['title' => 'Bot 配置']);
    }
}
