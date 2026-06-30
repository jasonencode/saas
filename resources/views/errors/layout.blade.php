<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title') - {{ config('app.name', 'SaaS Foundation') }}</title>

        {{-- Tailwind CSS CDN --}}
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwindcss.config = {
                theme: {
                    extend: {
                        colors: {
                            'spotify-green': '#1ed760',
                            'spotify-green-dark': '#1db954',
                            'spotify-black': '#121212',
                            'spotify-dark': '#181818',
                            'spotify-card': '#252525',
                            'spotify-mid': '#1f1f1f',
                            'spotify-silver': '#b3b3b3',
                            'spotify-border': '#4d4d4d',
                            'error-red': '#f3727f',
                            'warning-orange': '#ffa42b',
                            'info-blue': '#539df5',
                        },
                    },
                },
            }
        </script>

        <style>
            body {
                font-family: 'CircularSp', 'Helvetica Neue', helvetica, arial, sans-serif;
                background-color: #121212;
                min-height: 100vh;
                color: #ffffff;
            }

            @keyframes float {
                0%, 100% { transform: translateY(0) rotate(0deg); opacity: 0.3; }
                50% { transform: translateY(-20px) rotate(180deg); opacity: 0.8; }
            }

            .particle {
                position: absolute;
                border-radius: 50%;
                animation: float linear infinite;
                pointer-events: none;
            }

            .error-code {
                background: linear-gradient(135deg, #1ed760 0%, #1db954 50%, #1ed760 100%);
                background-size: 200% 200%;
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
                animation: gradient-shift 3s ease infinite;
            }

            @keyframes gradient-shift {
                0% { background-position: 0% 50%; }
                50% { background-position: 100% 50%; }
                100% { background-position: 0% 50%; }
            }

            .btn-spotify {
                background-color: #1ed760;
                color: #000000;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.14px;
                border-radius: 9999px;
                transition: all 0.2s ease;
            }

            .btn-spotify:hover {
                background-color: #1db954;
                transform: scale(1.04);
                box-shadow: rgba(0,0,0,0.5) 0px 8px 24px;
            }

            .btn-secondary {
                background-color: #1f1f1f;
                color: #ffffff;
                font-weight: 700;
                border: 1px solid #4d4d4d;
                border-radius: 9999px;
                transition: all 0.2s ease;
            }

            .btn-secondary:hover {
                background-color: #252525;
                border-color: #ffffff;
            }

            .glass-card {
                background-color: #181818;
                border: 1px solid rgba(255,255,255,0.1);
                box-shadow: rgba(0,0,0,0.5) 0px 8px 24px;
                transition: all 0.3s ease;
            }

            .glass-card:hover {
                border-color: rgba(30, 215, 96, 0.3);
            }

            @keyframes pulse-slow {
                0%, 100% { opacity: 0.5; transform: scale(1); }
                50% { opacity: 0.8; transform: scale(1.05); }
            }

            .icon-pulse {
                animation: pulse-slow 3s ease-in-out infinite;
            }
        </style>

        @stack('styles')
    </head>
    <body class="antialiased">
        {{-- 背景装饰 --}}
        <div class="fixed inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
            <div class="absolute inset-0 bg-gradient-to-br from-[#121212] via-[#181818] to-[#1f1f1f]"></div>
            <div id="particles"></div>
        </div>

        {{-- 主内容 --}}
        <div class="relative min-h-screen flex items-center justify-center p-6">
            <div class="w-full max-w-lg">
                <div class="glass-card rounded-2xl p-8 sm:p-12">

                    {{-- 图标区域 --}}
                    <div class="text-center mb-8">
                        @yield('icon')
                    </div>

                    {{-- 错误代码 --}}
                    <div class="text-center mb-4">
                        <h1 class="error-code text-8xl sm:text-9xl font-bold tracking-tight" aria-hidden="true">
                            @yield('code')
                        </h1>
                    </div>

                    {{-- 错误标题 --}}
                    <div class="text-center mb-3">
                        <h2 class="text-2xl sm:text-3xl font-bold text-white">
                            @yield('title')
                        </h2>
                    </div>

                    {{-- 错误描述 --}}
                    <div class="text-center mb-10">
                        <p class="text-[#b3b3b3] text-lg leading-relaxed max-w-md mx-auto">
                            @yield('message')
                        </p>
                    </div>

                    {{-- 操作按钮 --}}
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                        @yield('actions')
                    </div>

                    {{-- 调试信息 (仅开发环境) --}}
                    @if(app()->hasDebugModeEnabled() && isset($exception))
                        <div class="mt-8 pt-6 border-t border-[#4d4d4d]">
                            <details class="group">
                                <summary class="flex items-center gap-2 text-sm text-[#b3b3b3] hover:text-white cursor-pointer transition-colors">
                                    <svg class="w-4 h-4 transition-transform group-open:rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                    <span>调试信息</span>
                                </summary>
                                <div class="mt-4 p-4 bg-[#252525] rounded-lg">
                                    <p class="text-sm text-[#b3b3b3] font-mono break-all">
                                        {{ $exception->getMessage() }}
                                    </p>
                                    @if($exception->getCode())
                                        <p class="mt-2 text-xs text-[#4d4d4d]">
                                            错误代码: {{ $exception->getCode() }}
                                        </p>
                                    @endif
                                </div>
                            </details>
                        </div>
                    @endif
                </div>

                {{-- 底部 --}}
                <div class="mt-8 text-center">
                    <p class="text-sm text-[#4d4d4d]">
                        {{ config('app.name', 'SaaS Foundation') }} &copy; {{ date('Y') }}
                    </p>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const container = document.getElementById('particles');
                const particleCount = 20;

                for (let i = 0; i < particleCount; i++) {
                    const particle = document.createElement('div');
                    particle.className = 'particle';

                    const size = Math.random() * 4 + 2;
                    const x = Math.random() * 100;
                    const y = Math.random() * 100;
                    const delay = Math.random() * 5;
                    const duration = Math.random() * 10 + 10;

                    particle.style.cssText = `
                        width: ${size}px;
                        height: ${size}px;
                        left: ${x}%;
                        top: ${y}%;
                        background: rgba(30, 215, 96, ${Math.random() * 0.3 + 0.1});
                        animation-delay: ${delay}s;
                        animation-duration: ${duration}s;
                    `;

                    container.appendChild(particle);
                }
            });
        </script>

        @stack('scripts')
    </body>
</html>
