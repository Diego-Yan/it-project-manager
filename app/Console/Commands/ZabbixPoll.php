<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Models\ZabbixConfig;
use App\Services\NotificationService;
use App\Services\ZabbixService;
use Illuminate\Console\Command;

class ZabbixPoll extends Command
{
    protected $signature = 'zabbix:poll {--dry-run : 只显示将要创建的工单，不实际写入}';
    protected $description = '从 Zabbix 抓取告警并自动生成工单';

    public function handle(): int
    {
        $configs = ZabbixConfig::where('is_active', true)->get();

        if ($configs->isEmpty()) {
            $this->info('没有活跃的 Zabbix 连接配置');
            return 0;
        }

        $totalCreated = 0;
        $dryRun = $this->option('dry-run');

        foreach ($configs as $config) {
            $this->info("正在查询 Zabbix: {$config->name}");

            $svc = new ZabbixService($config);
            $triggers = $svc->getActiveTriggers();

            $this->info("  找到 " . count($triggers) . " 条活跃告警");

            foreach ($triggers as $trigger) {
                $triggerId = $trigger['triggerid'];
                $description = $trigger['description'] ?? '未知告警';
                $severity = (int) ($trigger['priority'] ?? 3);
                $hosts = collect($trigger['hosts'] ?? [])->pluck('name')->implode(', ');

                // 去重：trigger_id 已生成工单？
                $existing = Ticket::where('description', 'like', "%[Zabbix:{$triggerId}]%")
                    ->whereIn('status', ['open', 'in_progress'])
                    ->exists();

                if ($existing) {
                    $this->line("  ⏭ 跳过已有工单: {$description}");
                    continue;
                }

                $title = "[Zabbix] {$hosts}: {$description}";
                $ticketDescription = "Zabbix 告警自动生成\n"
                    . "主机: {$hosts}\n"
                    . "级别: " . ZabbixService::severityLabel($severity) . "\n"
                    . "描述: {$description}\n"
                    . "告警时间: " . date('Y-m-d H:i:s', (int) ($trigger['lastchange'] ?? time())) . "\n"
                    . "[Zabbix:{$triggerId}]"; // 用于去重标记

                if ($dryRun) {
                    $this->warn("  [DRY RUN] 将创建工单: {$title}");
                } else {
                    Ticket::create([
                        'title' => mb_substr($title, 0, 200),
                        'description' => $ticketDescription,
                        'type' => 'incident',
                        'priority' => $severity >= 4 ? 'high' : ($severity >= 3 ? 'medium' : 'low'),
                        'status' => 'open',
                        'source' => 'portal',
                        'created_by' => 1, // 系统用户
                    ]);
                    $totalCreated++;
                    $this->info("  ✅ 创建工单: {$title}");
                }

                $totalCreated++;
            }

            $config->update(['last_poll_at' => now()]);
        }

        $label = $dryRun ? '[DRY RUN] ' : '';
        $this->info("{$label}共创建 {$totalCreated} 个工单");

        return 0;
    }
}
