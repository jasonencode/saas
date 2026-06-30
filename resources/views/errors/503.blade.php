@extends('errors::layout')

@section('title', __('服务不可用'))
@section('code', '503')

@section('icon')
<div class="relative inline-block">
    <div class="absolute inset-0 bg-[#539df5]/20 rounded-full blur-xl icon-pulse"></div>
    <svg class="relative w-24 h-24 text-[#539df5]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17l-5.657-5.657a8 8 0 1111.314 0z" />
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01" />
    </svg>
</div>
@endsection

@section('message')
    {{ __('系统正在维护中，请稍后再试。我们正在努力恢复服务。') }}
@endsection

@section('actions')
    <a href="{{ url()->current() }}" class="btn-spotify inline-flex items-center gap-2 px-8 py-3 text-sm">
        {{ __('刷新') }}
    </a>
@endsection
