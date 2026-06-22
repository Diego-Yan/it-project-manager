{{-- [REVIEW-FIX] R5.1: 自定义 404 错误页 --}}
@extends('layouts.app')
@section('content')
<div class="flex flex-col items-center justify-center min-h-[60vh]">
    <h1 class="text-6xl font-bold text-gray-300 dark:text-gray-600">404</h1>
    <p class="mt-4 text-lg text-gray-500 dark:text-gray-400">{{ __('页面未找到') }}</p>
    <a href="/" class="mt-6 text-blue-600 hover:text-blue-700 underline">{{ __('返回首页') }}</a>
</div>
@endsection
