@extends('errors::layout')

@section('title', __('请求过于频繁'))
@section('code', '429')

@section('icon')
<div class="relative inline-block">
    <div class="absolute inset-0 bg-[#ffa42b]/20 rounded-full blur-xl icon-pulse"></div>
    <svg class="relative w-24 h-24 text-[#ffa42b]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
    </svg>
</div>
@endsection

@section('message')
    {{ __('您的请求过于频繁，请稍后再试。这是为了保护系统稳定运行。') }}
@endsection

@section('actions')
    <a href="{{ url()->previous() }}" class="btn-secondary inline-flex items-center gap-2 px-6 py-3 text-sm">
        {{ __('返回上页') }}
    </a>
    <a href="{{ url('/') }}" class="btn-spotify inline-flex items-center gap-2 px-8 py-3 text-sm">
        {{ __('返回首页') }}
    </a>
@endsection
