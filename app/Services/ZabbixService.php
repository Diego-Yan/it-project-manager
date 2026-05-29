<?php

namespace App\Services;

use App\Models\ZabbixConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZabbixService
{
    private ZabbixConfig $config;

    public function __construct(ZabbixConfig $config)
    {
        $this->config = $config;
    }

    /**
     * 获取活跃告警 (severity >= min_severity)
     */
    public function getActiveTriggers(): array
    {
        try {
            $resp = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->config->api_token,
                'Content-Type' => 'application/json-rpc',
            ])->timeout(15)->post($this->config->url, [
                'jsonrpc' => '2.0',
                'method' => 'trigger.get',
                'params' => [
                    'output' => ['triggerid', 'description', 'priority', 'lastchange', 'hosts', 'comments'],
                    'selectHosts' => ['hostid', 'name'],
                    'min_severity' => (int) $this->config->min_severity,
                    'active' => true,
                    'only_true' => true,
                    'skipDependent' => true,
                    'monitored' => true,
                    'sortfield' => 'priority',
                    'sortorder' => 'DESC',
                ],
                'id' => 1,
            ]);

            if ($resp->failed()) {
                Log::error('Zabbix API failed', ['config' => $this->config->name, 'status' => $resp->status()]);
                return [];
            }

            return $resp->json()['result'] ?? [];
        } catch (\Exception $e) {
            Log::error('Zabbix connection error', ['config' => $this->config->name, 'error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * 测试连接
     */
    public function testConnection(): bool
    {
        try {
            $resp = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->config->api_token,
                'Content-Type' => 'application/json-rpc',
            ])->timeout(10)->post($this->config->url, [
                'jsonrpc' => '2.0',
                'method' => 'apiinfo.version',
                'params' => [],
                'id' => 1,
            ]);

            return $resp->successful() && isset($resp->json()['result']);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 严重级别中文名
     */
    public static function severityLabel(int $severity): string
    {
        return match ($severity) {
            0 => '未分类',
            1 => '信息',
            2 => '警告',
            3 => '一般严重',
            4 => '严重',
            5 => '灾难',
            default => '未知',
        };
    }

    public static function severityColor(int $severity): string
    {
        return match ($severity) {
            0, 1 => 'zinc',
            2 => 'sky',
            3 => 'amber',
            4 => 'red',
            5 => 'red',
            default => 'zinc',
        };
    }
}
