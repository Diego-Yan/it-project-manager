<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AD 认证开关
    |--------------------------------------------------------------------------
    */
    'enabled' => env('AD_AUTH_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | AD 服务器配置
    |--------------------------------------------------------------------------
    */
    'server' => env('AD_SERVER', 'ldap://your-ad-server.domain.com'),
    'port' => env('AD_PORT', 389),
    'use_tls' => env('AD_USE_TLS', false),
    'use_ssl' => env('AD_USE_SSL', false),

    /*
    |--------------------------------------------------------------------------
    | AD 域配置
    |--------------------------------------------------------------------------
    */
    'domain' => env('AD_DOMAIN', 'domain.com'),
    'base_dn' => env('AD_BASE_DN', 'DC=domain,DC=com'),

    /*
    |--------------------------------------------------------------------------
    | AD 管理员账号（用于查询用户）
    |--------------------------------------------------------------------------
    */
    'admin_username' => env('AD_ADMIN_USERNAME', 'admin'),
    'admin_password' => env('AD_ADMIN_PASSWORD', ''),

    /*
    |--------------------------------------------------------------------------
    | 用户同步配置
    |--------------------------------------------------------------------------
    */
    'sync' => [
        'auto_create_user' => env('AD_AUTO_CREATE_USER', true),
        'auto_sync_groups' => env('AD_AUTO_SYNC_GROUPS', false),
        'default_role' => env('AD_DEFAULT_ROLE', '普通员工'), // [FIX] #10: 与 BotController 和 Seeder 的角色名保持一致
        'sync_fields' => [
            'display_name' => 'displayname',
            'email' => 'mail',
            'phone' => 'telephonenumber',
            'department' => 'department',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 认证失败处理
    |--------------------------------------------------------------------------
    */
    'fallback_to_local' => env('AD_FALLBACK_TO_LOCAL', true),
    'lock_after_failed_attempts' => env('AD_LOCK_AFTER_FAILED', 5),
    'lock_minutes' => env('AD_LOCK_MINUTES', 30),
];
