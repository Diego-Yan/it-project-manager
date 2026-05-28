<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_categories', function (Blueprint $table) {
            // type: ops=运维项目, dev=开发项目
            $table->string('type', 10)->default('ops')->after('name');
        });

        // 现有分类全部归运维项目
        DB::table('project_categories')->update(['type' => 'ops']);

        // 添加开发项目下的初始分类
        $devCategories = [
            ['name' => 'HR系统',  'type' => 'dev', 'description' => '人力资源系统相关开发项目', 'color' => 'violet', 'icon' => 'folder', 'sort_order' => 10, 'is_active' => 1],
            ['name' => 'OA系统',  'type' => 'dev', 'description' => '办公自动化系统相关开发项目', 'color' => 'blue',   'icon' => 'folder', 'sort_order' => 11, 'is_active' => 1],
            ['name' => 'PMS系统', 'type' => 'dev', 'description' => '项目管理系统相关开发项目',  'color' => 'indigo', 'icon' => 'folder', 'sort_order' => 12, 'is_active' => 1],
            ['name' => 'ERP系统', 'type' => 'dev', 'description' => 'ERP系统相关开发项目',      'color' => 'amber',  'icon' => 'folder', 'sort_order' => 13, 'is_active' => 1],
        ];

        foreach ($devCategories as $cat) {
            DB::table('project_categories')->insert(array_merge($cat, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        // 删除开发项目分类
        DB::table('project_categories')->where('type', 'dev')->delete();

        Schema::table('project_categories', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
