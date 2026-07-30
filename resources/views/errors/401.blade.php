@extends('errors::layout')

@section('title', __('未授权'))
@section('code', '401')

@section('icon')
    <div class="relative inline-block">
        <div class="absolute inset-0 bg-[#1ed760]/20 rounded-full blur-xl icon-pulse"></div>
        <svg class="relative w-24 h-24 text-[#1ed760]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>
        </svg>
    </div>
@endsection

@section('message')
    {{ __('此页面需要身份验证。请登录后重试。') }}
@endsection

@section('actions')
    <a href="{{ url('/') }}" class="btn-spotify inline-flex items-center gap-2 px-8 py-3 text-sm">
        {{ __('返回首页') }}
    </a>
@endsection
