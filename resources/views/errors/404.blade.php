@extends('errors::layout')

@section('title', __('页面未找到'))
@section('code', '404')

@section('icon')
<div class="relative inline-block">
    <div class="absolute inset-0 bg-[#1ed760]/20 rounded-full blur-xl icon-pulse"></div>
    <svg class="relative w-24 h-24 text-[#1ed760]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 7.5v6m3-3h-6" />
    </svg>
</div>
@endsection

@section('message')
    {{ __('您访问的页面不存在或已被移除。请检查链接是否正确。') }}
@endsection

@section('actions')
    <a href="{{ url()->previous() }}" class="btn-secondary inline-flex items-center gap-2 px-6 py-3 text-sm">
        {{ __('返回上页') }}
    </a>
    <a href="{{ url('/') }}" class="btn-spotify inline-flex items-center gap-2 px-8 py-3 text-sm">
        {{ __('返回首页') }}
    </a>
@endsection
