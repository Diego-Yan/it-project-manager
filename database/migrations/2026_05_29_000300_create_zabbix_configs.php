<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zabbix_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');             // https://zabbix.example.com/api_jsonrpc.php
            $table->string('api_token');       // Zabbix API token
            $table->integer('min_severity')->default(4); // 4=警告, 5=严重
            $table->integer('poll_interval')->default(10); // 分钟
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_poll_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zabbix_configs');
    }
};
