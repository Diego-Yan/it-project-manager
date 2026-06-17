@extends('layouts.app')
@section('content')
<div class="flex flex-col items-center justify-center min-h-[60vh] text-center">
    <div class="text-6xl font-bold text-amber-500 mb-4">419</div>
    <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-200 mb-2">页面已过期</h1>
    <p class="text-zinc-500 dark:text-zinc-400 mb-6">您的会话已过期，请刷新页面后重新操作。</p>
    <a href="{{ url()->previous() }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">返回上一页</a>
</div>
@endsection
