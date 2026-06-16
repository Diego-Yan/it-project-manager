<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'wechat' => [
        'corp_id'     => env('WECHAT_CORP_ID', ''),
        'corp_secret' => env('WECHAT_CORP_SECRET', ''),
        // [FIX] #5: 企业微信机器人回调签名验证
        'bot_token'   => env('WECHAT_BOT_TOKEN', ''),
    ],

    'dingtalk' => [
        'app_key'    => env('DINGTALK_APP_KEY', ''),
        'app_secret' => env('DINGTALK_APP_SECRET', ''),
        // [FIX] #5: 钉钉机器人回调签名验证
        'bot_secret' => env('DINGTALK_BOT_SECRET', ''),
    ],

    'embedding' => [
        'url'   => env('EMBEDDING_API_URL', ''),
        'key'   => env('EMBEDDING_API_KEY', ''),
        'model' => env('EMBEDDING_MODEL', ''),
    ],

    // [REVIEW-FIX] R6.6: 合并重复 logo 键 — APP_LOGO_URL 优先，LOGO_URL 作为兼容回退
    'logo' => [
        'url' => env('APP_LOGO_URL', env('LOGO_URL', '')),
    ],

    'llm' => [
        'url'   => env('LLM_API_URL', ''),
        'key'   => env('LLM_API_KEY', ''),
        'model' => env('LLM_MODEL', ''),
        // [REVIEW-FIX] R10.1: 管理员可关闭上下文注入，防止敏感数据外泄
        'send_context' => env('LLM_SEND_CONTEXT', true),
    ],


];
