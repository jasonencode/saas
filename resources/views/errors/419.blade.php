@extends('errors::layout')

@section('title', __('页面已过期'))
@section('code', '419')

@section('icon')
<div class="relative inline-block">
    <div class="absolute inset-0 bg-[#ffa42b]/20 rounded-full blur-xl icon-pulse"></div>
    <svg class="relative w-24 h-24 text-[#ffa42b]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
</div>
@endsection

@section('message')
    {{ __('页面已过期，请刷新后重试。这通常是由于长时间未操作导致的。') }}
@endsection

@section('actions')
    <a href="{{ url()->current() }}" class="btn-spotify inline-flex items-center gap-2 px-8 py-3 text-sm">
        {{ __('刷新页面') }}
    </a>
@endsection
