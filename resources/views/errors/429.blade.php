@extends('layouts.app')
@section('content')
<div class="flex flex-col items-center justify-center min-h-[60vh] text-center">
    <div class="text-6xl font-bold text-orange-500 mb-4">429</div>
    <h1 class="text-2xl font-semibold text-zinc-800 dark:text-zinc-200 mb-2">请求过于频繁</h1>
    <p class="text-zinc-500 dark:text-zinc-400 mb-6">您的操作太快了，请稍等片刻后再试。</p>
    <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">返回仪表盘</a>
</div>
@endsection
