@extends('layouts.app')
@section('content')
<div class="flex flex-col items-center justify-center min-h-[60vh] text-center">
    <div class="text-6xl font-bold text-blue-500 mb-4">503</div>
    <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-200 mb-2">系统维护中</h1>
    <p class="text-zinc-500 dark:text-zinc-400 mb-6">系统正在维护，请稍后再试。</p>
    <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">刷新页面</a>
</div>
@endsection
