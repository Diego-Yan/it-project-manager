<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\Sla;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BotController extends Controller
{
    /**
     * 企业微信群机器人回调
     * POST /api/bot/wechat
     */
    public function wechat(Request $request)
    {
        $data = $request->all();
        Log::info('WeChat bot message', $data);

        $content = $data['text']['content'] ?? $data['msg'] ?? '';
        $userName = $data['from']['name'] ?? '企微用户';
        $userId = $data['from']['userid'] ?? null;

        return $this->handleMessage($content, $userName, $userId, 'wechat');
    }

    public function dingtalk(Request $request)
    {
        $data = $request->all();
        Log::info('DingTalk bot message', $data);

        $content = $data['text']['content'] ?? '';
        $userName = $data['senderNick'] ?? '钉钉用户';
        $userId = $data['senderId'] ?? null;

        return $this->handleMessage($content, $userName, $userId, 'dingtalk');
    }

    /**
     * 查找或创建系统用户
     */
    private function findOrCreateUser(string $userId, string $userName, string $platform): User
    {
        $field = $platform === 'wechat' ? 'wechat_userid' : 'dingtalk_userid';

        $user = User::where($field, $userId)->first();

        if (!$user) {
            // 尝试匹配现有用户（同名）
            $user = User::where('name', $userName)->where('source', $platform)->first();

            if (!$user) {
                $user = User::create([
                    'name' => $userName,
                    'username' => $platform . '_' . ($userId ?: uniqid()),
                    'password' => bcrypt(str()->random(32)),
                    'source' => $platform,
                    $field => $userId,
                    'is_active' => true,
                ]);
                $user->assignRole('普通员工');
            } elseif ($userId) {
                $user->update([$field => $userId]);
            }
        }

        return $user;
    }

    private function handleMessage(string $content, string $userName, ?string $userId, string $platform): array
    {
        $content = trim($content);
        if (empty($content)) {
            return $this->reply('请输入报修内容，例如："3楼打印机没墨了"', $platform);
        }

        // ── 命令：查工单状态 ──────────────────────────────
        if (preg_match('/^(状态|查询|status)\s*#?(\d+)$/i', $content, $m)) {
            $ticket = Ticket::find($m[2]);
            if (!$ticket) {
                return $this->reply("未找到工单 #{$m[2]}", $platform);
            }
            return $this->reply(
                "📋 工单 #{$ticket->id}\n" .
                "标题: {$ticket->title}\n" .
                "类型: {$ticket->typeLabel} | 优先级: {$ticket->priorityLabel}\n" .
                "状态: {$ticket->statusLabel}\n" .
                "处理人: " . ($ticket->assignee->name ?? '未分配') . "\n" .
                "创建: {$ticket->created_at->format('m/d H:i')}",
                $platform
            );
        }

        // ── 普通消息 → 创建工单 ────────────────────────────
        $priority = 'medium';
        if (preg_match('/(紧急|urgent|急)/i', $content)) {
            $priority = 'high';
        }

        // 匹配或创建系统用户
        $user = $this->findOrCreateUser($userId ?? '', $userName, $platform);

        $ticket = Ticket::create([
            'title'       => mb_substr($content, 0, 200),
            'type'        => 'request',
            'priority'    => $priority,
            'status'      => 'open',
            'source'      => $platform === 'wechat' ? 'portal' : 'portal',
            'created_by'  => $user->id,
            'description' => "来自{$platform}手机端: {$userName}\n{$content}",
            'sla_deadline' => Sla::getDeadline($priority),
        ]);

        $ticketId = $ticket->id;
        return $this->reply(
            "✅ 工单已创建\n" .
            "工单号: #{$ticketId}\n" .
            "标题: {$ticket->title}\n" .
            "优先级: {$ticket->priorityLabel}\n" .
            "输入「状态 {$ticketId}」查看进度",
            $platform
        );
    }

    /**
     * 格式化返回消息
     */
    private function reply(string $text, string $platform): array
    {
        if ($platform === 'dingtalk') {
            return [
                'msgtype' => 'markdown',
                'markdown' => [
                    'title' => 'ITSM 工单系统',
                    'text'  => $text,
                ],
            ];
        }

        // 企业微信 / 通用
        return [
            'msgtype' => 'markdown',
            'markdown' => [
                'content' => $text,
            ],
        ];
    }
}
