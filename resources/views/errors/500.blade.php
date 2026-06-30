@extends('errors::layout')

@section('title', __('服务器错误'))
@section('code', '500')

@section('icon')
<div class="relative inline-block">
    <div class="absolute inset-0 bg-[#f3727f]/20 rounded-full blur-xl icon-pulse"></div>
    <svg class="relative w-24 h-24 text-[#f3727f]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
    </svg>
</div>
@endsection

@section('message')
    {{ __('服务器遇到了意外错误，请稍后再试。如果问题持续存在，请联系管理员。') }}
@endsection

@section('actions')
    <a href="{{ url()->current() }}" class="btn-secondary inline-flex items-center gap-2 px-6 py-3 text-sm">
        {{ __('重试') }}
    </a>
    <a href="{{ url('/') }}" class="btn-spotify inline-flex items-center gap-2 px-8 py-3 text-sm">
        {{ __('返回首页') }}
    </a>
@endsection
