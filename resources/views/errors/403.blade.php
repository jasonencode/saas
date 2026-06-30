@extends('errors::layout')

@section('title', __('禁止访问'))
@section('code', '403')

@section('icon')
<div class="relative inline-block">
    <div class="absolute inset-0 bg-[#f3727f]/20 rounded-full blur-xl icon-pulse"></div>
    <svg class="relative w-24 h-24 text-[#f3727f]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
    </svg>
</div>
@endsection

@section('message')
    {{ __('抱歉，您没有权限访问此页面。请联系管理员获取授权。') }}
@endsection

@section('actions')
    <a href="{{ url()->previous() }}" class="btn-secondary inline-flex items-center gap-2 px-6 py-3 text-sm">
        {{ __('返回上页') }}
    </a>
    <a href="{{ url('/') }}" class="btn-spotify inline-flex items-center gap-2 px-8 py-3 text-sm">
        {{ __('返回首页') }}
    </a>
@endsection
