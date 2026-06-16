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
     * [FIX] #5: 企业微信群机器人回调 - 添加签名验证
     * POST /api/bot/wechat
     */
    public function wechat(Request $request)
    {
        // [FIX] #5: 验证企业微信签名（如果配置了 token/aes_key）
        if (!$this->verifyWechatSignature($request)) {
            Log::warning('WeChat bot signature verification failed', ['ip' => $request->ip()]);
            return response()->json(['errcode' => 403, 'errmsg' => '签名验证失败'], 403);
        }

        $data = $request->all();
        Log::info('WeChat bot message', $data);

        $content = $data['text']['content'] ?? $data['msg'] ?? '';
        $userName = $data['from']['name'] ?? '企微用户';
        $userId = $data['from']['userid'] ?? null;

        return $this->handleMessage($content, $userName, $userId, 'wechat');
    }

    /**
     * [FIX] #5: 钉钉机器人回调 - 添加签名验证
     * POST /api/bot/dingtalk
     */
    public function dingtalk(Request $request)
    {
        // [FIX] #5: 验证钉钉签名
        if (!$this->verifyDingTalkSignature($request)) {
            Log::warning('DingTalk bot signature verification failed', ['ip' => $request->ip()]);
            return response()->json(['errcode' => 403, 'errmsg' => '签名验证失败'], 403);
        }

        $data = $request->all();
        Log::info('DingTalk bot message', $data);

        $content = $data['text']['content'] ?? '';
        $userName = $data['senderNick'] ?? '钉钉用户';
        $userId = $data['senderId'] ?? null;

        return $this->handleMessage($content, $userName, $userId, 'dingtalk');
    }

    /**
     * [FIX] #5: 验证企业微信回调签名
     */
    private function verifyWechatSignature(Request $request): bool
    {
        $token = config('services.wechat.bot_token');
        if (empty($token)) {
            // [REVIEW-FIX] H2: 未配置时拒绝请求（生产环境），仅本地开发允许
            Log::warning('WeChat bot token not configured — rejecting webhook');
            if (app()->isProduction()) {
                abort(503, 'Bot webhook not configured');
            }
            return false;
        }

        $signature = $request->query('msg_signature', '');
        $timestamp = $request->query('timestamp', '');
        $nonce = $request->query('nonce', '');

        if (empty($signature) || empty($timestamp) || empty($nonce)) {
            return false;
        }

        $tmpArr = [$token, $timestamp, $nonce];
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode('', $tmpArr);
        $calculated = sha1($tmpStr);

        return hash_equals($calculated, $signature);
    }

    /**
     * [FIX] #5: 验证钉钉回调签名
     */
    private function verifyDingTalkSignature(Request $request): bool
    {
        $secret = config('services.dingtalk.bot_secret');
        if (empty($secret)) {
            // [REVIEW-FIX] H2: 未配置时拒绝请求（生产环境），仅本地开发允许
            Log::warning('DingTalk bot secret not configured — rejecting webhook');
            if (app()->isProduction()) {
                abort(503, 'Bot webhook not configured');
            }
            return false;
        }

        $timestamp = $request->query('timestamp', '');
        $sign = $request->query('sign', '');

        if (empty($timestamp) || empty($sign)) {
            return false;
        }

        $stringToSign = $timestamp . "\n" . $secret;
        $calculated = base64_encode(hash_hmac('sha256', $stringToSign, $secret, true));

        return hash_equals($calculated, $sign);
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
                // [FIX] #10: 使用配置中的默认角色，保持一致
                $defaultRole = config('ad-auth.sync.default_role', '普通员工');
                $user = User::create([
                    'name' => $userName,
                    'username' => $platform . '_' . ($userId ?: uniqid()),
                    'password' => bcrypt(\Illuminate\Support\Str::random(32)), // [FIX] #3
                    'source' => $platform,
                    $field => $userId,
                    'is_active' => true,
                ]);
                $user->assignRole($defaultRole);
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
                        'source'      => 'im_' . $platform,  // [REVIEW-FIX] R9.4: 区分 IM 平台来源
            'created_by'  => $user->id,
                        // [REVIEW-FIX] R9.4: 截断描述内容防滥用 + 区分平台来源
            'description' => mb_substr("来自{$platform}: {$userName}\n{$content}", 0, 5000),
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
