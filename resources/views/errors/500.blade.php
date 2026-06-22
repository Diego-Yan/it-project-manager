{{-- [REVIEW-FIX] R5.1: 自定义 500 错误页 — 生产环境不暴露内部错误详情 --}}
@extends('layouts.app')
@section('content')
<div class="flex flex-col items-center justify-center min-h-[60vh]">
    <h1 class="text-6xl font-bold text-gray-300 dark:text-gray-600">500</h1>
    <p class="mt-4 text-lg text-gray-500 dark:text-gray-400">{{ __('服务器内部错误，请稍后重试') }}</p>
    <a href="/" class="mt-6 text-blue-600 hover:text-blue-700 underline">{{ __('返回首页') }}</a>
</div>
@endsection
