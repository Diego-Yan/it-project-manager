@php
    // [REVIEW-FIX] I14: 替换默认 Laravel 欢迎页为登录重定向
    header('Location: ' . route('login'));
    exit;
@endphp
