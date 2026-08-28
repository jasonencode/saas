<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="基于 Laravel 和 Filament 构建的现代化 SaaS 基座，预配置多租户架构、权限系统与业务流程。">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <style>
        :root {
            /* Brand — functional use only */
            --color-spotify-green: #1ed760;
            --color-green-border: #1db954;

            /* Surfaces (Level 0 → 2) */
            --color-near-black: #121212;
            --color-dark-surface: #181818;
            --color-mid-dark: #1f1f1f;
            --color-dark-card: #252525;
            --color-mid-card: #272727;

            /* Text */
            --color-white: #ffffff;
            --color-light: #fdfdfd;
            --color-near-white: #cbcbcb;
            --color-silver: #b3b3b3;

            /* Borders */
            --color-border-gray: #4d4d4d;
            --color-light-border: #7c7c7c;

            /* Elevation */
            --shadow-medium: rgba(0, 0, 0, 0.3) 0 8px 8px;
            --shadow-heavy: rgba(0, 0, 0, 0.5) 0 8px 24px;

            /* Type — SpotifyMixUI intent with CJK-aware fallbacks */
            --font-ui: 'SpotifyMixUI', -apple-system, BlinkMacSystemFont, 'Segoe UI',
                'Helvetica Neue', Helvetica, Arial, 'PingFang SC', 'Hiragino Sans GB',
                'Microsoft YaHei', 'Noto Sans CJK SC', sans-serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--color-near-black);
            color: var(--color-white);
            min-height: 100vh;
            font-family: var(--font-ui);
            font-size: 1rem;
            font-weight: 400;
        }

        .container {
            width: 100%;
            max-width: 75rem;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        /* Header — Level 1 over Level 0, shadow-based separation */
        .header {
            position: sticky;
            top: 0;
            z-index: 100;
            padding: 1rem 0;
            background-color: rgba(18, 18, 18, 0.9);
            backdrop-filter: blur(12px);
            box-shadow: var(--shadow-medium);
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
        }

        .logo {
            font-size: 1rem;
            font-weight: 700;
            color: var(--color-white);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-shrink: 0;
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
        }

        .nav-link {
            color: var(--color-silver);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 400;
            transition: color 0.2s ease;
        }

        .nav-link:hover,
        .nav-link:focus-visible {
            color: var(--color-white);
            font-weight: 700;
        }

        /* Hero */
        .hero {
            padding: 6rem 0 4rem;
        }

        .hero-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 4rem;
        }

        .hero-content {
            flex: 1;
            max-width: 34rem;
        }

        /* Badge — green as live-status indicator */
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0.75rem;
            background-color: var(--color-mid-dark);
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            line-height: 1.5;
            color: var(--color-spotify-green);
            text-transform: uppercase;
            letter-spacing: 1.4px;
            margin-bottom: 1.5rem;
        }

        .hero-badge::before {
            content: '';
            width: 6px;
            height: 6px;
            background-color: var(--color-spotify-green);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        .hero-title {
            font-size: 3.25rem;
            font-weight: 700;
            line-height: 1.15;
            letter-spacing: -0.02em;
            margin-bottom: 1.5rem;
        }

        .hero-title span {
            color: var(--color-spotify-green);
        }

        .hero-description {
            font-size: 1.125rem;
            font-weight: 400;
            color: var(--color-near-white);
            line-height: 1.7;
            margin-bottom: 1.5rem;
        }

        .hero-highlights {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem 1.5rem;
            margin-bottom: 2rem;
        }

        .hero-highlight {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            font-weight: 400;
            color: var(--color-near-white);
        }

        .hero-highlight svg {
            width: 1rem;
            height: 1rem;
            color: var(--color-spotify-green);
            flex-shrink: 0;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        /* Visual panel — Level 1 surface, no borders, no glow */
        .hero-visual {
            flex: 1;
            max-width: 28rem;
        }

        .visual-card {
            background: linear-gradient(145deg, var(--color-dark-surface), var(--color-mid-dark));
            border-radius: 1.5rem;
            padding: 2.5rem;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .visual-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(30, 215, 96, 0.15) 0%, transparent 70%);
            pointer-events: none;
        }

        .deco-1 {
            position: absolute;
            top: 2rem;
            right: -2rem;
            width: 8rem;
            height: 8rem;
            background: var(--color-spotify-green);
            border-radius: 1rem;
            opacity: 0.1;
            transform: rotate(15deg);
        }

        .deco-2 {
            position: absolute;
            bottom: -3rem;
            left: -3rem;
            width: 10rem;
            height: 10rem;
            border: 2px solid var(--color-spotify-green);
            border-radius: 50%;
            opacity: 0.1;
        }

        /* Keep content above the glow and deco shapes */
        .visual-icon,
        .visual-title,
        .visual-subtitle,
        .visual-stats {
            position: relative;
            z-index: 1;
        }

        .visual-icon {
            width: 6rem;
            height: 6rem;
            background: linear-gradient(135deg, var(--color-spotify-green), var(--color-green-border));
            border-radius: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--color-near-black);
            margin-bottom: 2rem;
            box-shadow: 0 20px 40px rgba(30, 215, 96, 0.3);
        }

        .visual-icon svg {
            width: 2.5rem;
            height: 2.5rem;
        }

        .visual-title {
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1.3;
            margin-bottom: 0.75rem;
        }

        .visual-subtitle {
            font-size: 0.9375rem;
            font-weight: 400;
            color: var(--color-silver);
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .visual-stats {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--color-spotify-green);
        }

        .stat-label {
            font-size: 0.75rem;
            font-weight: 400;
            color: var(--color-silver);
            margin-top: 0.25rem;
        }

        /* Features */
        .features {
            padding: 4rem 0;
        }

        .section-header {
            text-align: center;
            max-width: 36rem;
            margin: 0 auto 2.5rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .section-subtitle {
            font-size: 0.875rem;
            font-weight: 400;
            color: var(--color-silver);
            line-height: 1.5;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.25rem;
        }

        .feature-card {
            position: relative;
            overflow: hidden;
            background: linear-gradient(145deg, var(--color-dark-surface), var(--color-mid-dark));
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 1rem;
            padding: 1.75rem 1.5rem;
            transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 10rem;
            height: 10rem;
            background: radial-gradient(circle at top right, rgba(30, 215, 96, 0.14) 0%, transparent 70%);
            pointer-events: none;
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-4px);
            border-color: rgba(30, 215, 96, 0.25);
            box-shadow: var(--shadow-heavy);
        }

        .feature-card:hover::before {
            opacity: 1;
        }

        /* Keep content above the glow */
        .feature-icon,
        .feature-title,
        .feature-desc {
            position: relative;
            z-index: 1;
        }

        .feature-icon {
            width: 3rem;
            height: 3rem;
            background: linear-gradient(135deg, var(--color-dark-card), var(--color-mid-card));
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 0.875rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--color-spotify-green);
            margin-bottom: 1.25rem;
            transition: background 0.3s ease, color 0.3s ease, box-shadow 0.3s ease;
        }

        .feature-card:hover .feature-icon {
            background: linear-gradient(135deg, var(--color-spotify-green), var(--color-green-border));
            color: var(--color-near-black);
            box-shadow: 0 8px 20px rgba(30, 215, 96, 0.3);
        }

        .feature-title {
            font-size: 1rem;
            font-weight: 600;
            line-height: 1.3;
            margin-bottom: 0.5rem;
        }

        .feature-desc {
            font-size: 0.875rem;
            font-weight: 400;
            color: var(--color-silver);
            line-height: 1.5;
        }

        /* Tech stack */
        .tech-stack {
            padding: 2rem 0;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .tech-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .tech-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--color-silver);
            text-transform: uppercase;
            letter-spacing: 1.4px;
        }

        .tech-items {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .tech-item {
            padding: 0.5rem 1rem;
            background-color: var(--color-mid-dark);
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 400;
            color: var(--color-near-white);
        }

        /* CTA */
        .cta {
            padding: 3rem 0;
            text-align: center;
        }

        .cta-card {
            background-color: var(--color-dark-surface);
            border-radius: 8px;
            padding: 3rem 1.5rem;
            box-shadow: var(--shadow-medium);
        }

        .cta-title {
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1.3;
            margin-bottom: 0.5rem;
        }

        .cta-description {
            font-size: 1rem;
            font-weight: 400;
            color: var(--color-silver);
            line-height: 1.5;
            margin: 0 auto 1.5rem;
            max-width: 32rem;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        /* Footer */
        .footer {
            padding: 2rem 0;
            text-align: center;
            color: var(--color-silver);
            font-size: 0.75rem;
            font-weight: 400;
        }

        .footer a {
            color: var(--color-near-white);
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        .footer a:hover {
            color: var(--color-white);
        }

        /* Buttons — pill geometry, uppercase, wide tracking */
        .btn-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.875rem 2.25rem;
            border-radius: 500px;
            font-family: inherit;
            font-size: 0.875rem;
            font-weight: 700;
            line-height: 1;
            text-transform: uppercase;
            letter-spacing: 1.4px;
            text-decoration: none;
            transition: background-color 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background-color: var(--color-spotify-green);
            color: #000000;
        }

        .btn-primary:hover,
        .btn-primary:focus-visible {
            background-color: #1fdf64;
            transform: scale(1.02);
        }

        .btn-secondary {
            background-color: transparent;
            color: var(--color-white);
            border: 1px solid var(--color-light-border);
        }

        .btn-secondary:hover,
        .btn-secondary:focus-visible {
            border-color: var(--color-white);
            background-color: var(--color-mid-dark);
        }

        /* Responsive — DESIGN.md collapsing strategy */
        @media (max-width: 1024px) {
            .hero-wrapper {
                flex-direction: column;
                align-items: stretch;
                gap: 2.5rem;
            }

            .hero-content,
            .hero-visual {
                max-width: 100%;
            }

            .hero-title {
                font-size: 2.75rem;
            }

            .features-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.25rem;
            }

            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            .hero {
                padding: 3rem 0 2rem;
            }

            .hero-title {
                font-size: 1.875rem;
            }

            .hero-description {
                font-size: 1rem;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-links {
                gap: 1.5rem;
            }

            .btn-pill {
                flex: 1 1 auto;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }

            .btn-primary:hover {
                transform: none;
            }

            .feature-card:hover {
                transform: none;
            }
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="/" class="logo">
                    <svg width="32" height="32" viewBox="0 0 32 32" fill="none" aria-hidden="true">
                        <rect width="32" height="32" rx="6" fill="#1ed760"/>
                        <path d="M8 23L16 9L24 23" stroke="#121212" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M11 18H21" stroke="#121212" stroke-width="3" stroke-linecap="round"/>
                    </svg>
                    JasonSaaS
                </a>
                <nav class="nav-links">
                    <a href="/docs" class="nav-link">文档</a>
                    <a href="https://github.com/jasonencode/saas" target="_blank" rel="noopener noreferrer" class="nav-link">GitHub</a>
                    <a href="/backend" class="nav-link">控制台</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Hero -->
    <section class="hero">
        <div class="container">
            <div class="hero-wrapper">
                <div class="hero-content">
                    <div class="hero-badge">Modern SaaS Foundation</div>
                    <h1 class="hero-title">让 SaaS 开发<br><span>快人一步</span></h1>
                    <p class="hero-description">
                        基于 Laravel 与 Filament 打造的企业级 SaaS 基座。多租户、权限、订单、支付——那些每个项目都要重写一遍的地基，已经替你打好。
                    </p>
                    <div class="hero-highlights">
                        <span class="hero-highlight">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M20 6 9 17l-5-5"/>
                            </svg>
                            双面板多租户架构
                        </span>
                        <span class="hero-highlight">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M20 6 9 17l-5-5"/>
                            </svg>
                            注解驱动的 RBAC
                        </span>
                        <span class="hero-highlight">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M20 6 9 17l-5-5"/>
                            </svg>
                            订单状态机闭环
                        </span>
                    </div>
                    <div class="hero-buttons">
                        <a href="/backend" class="btn-pill btn-primary">进入控制台</a>
                        <a href="https://github.com/jasonencode/saas" target="_blank" rel="noopener noreferrer" class="btn-pill btn-secondary">GitHub</a>
                    </div>
                </div>

                <div class="hero-visual">
                    <div class="visual-card">
                        <div class="deco-1"></div>
                        <div class="deco-2"></div>
                        <div class="visual-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 2 2 7l10 5 10-5-10-5Z"/>
                                <path d="m2 17 10 5 10-5"/>
                                <path d="m2 12 10 5 10-5"/>
                            </svg>
                        </div>
                        <h2 class="visual-title">Build faster, scale further.</h2>
                        <p class="visual-subtitle">
                            从概念到生产环境，只需几分钟。强大的架构设计，支持高并发、弹性扩展。
                        </p>
                        <div class="visual-stats">
                            <div class="stat-item">
                                <div class="stat-value">99.9%</div>
                                <div class="stat-label">可用性</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">&lt;50ms</div>
                                <div class="stat-label">响应时间</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">10K+</div>
                                <div class="stat-label">日活跃</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="features">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">为什么选择 JasonSaaS</h2>
                <p class="section-subtitle">开箱即用的功能特性，让您的 SaaS 开发更加高效</p>
            </div>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M13 2 3 14h9l-1 8 10-12h-9l1-8Z"/>
                        </svg>
                    </div>
                    <h3 class="feature-title">开箱即用</h3>
                    <p class="feature-desc">预配置的多租户架构和权限系统，无需从零开始</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"/>
                            <circle cx="12" cy="12" r="5"/>
                            <circle cx="12" cy="12" r="1"/>
                        </svg>
                    </div>
                    <h3 class="feature-title">优雅设计</h3>
                    <p class="feature-desc">遵循 Laravel 最佳实践和设计模式，代码规范清晰</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 12a9 9 0 1 1-3-6.7"/>
                            <path d="M21 3v6h-6"/>
                        </svg>
                    </div>
                    <h3 class="feature-title">完整生态</h3>
                    <p class="feature-desc">集成订单、支付、通知等业务流程，开箱即用</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.8-3.8a6 6 0 0 1-7.9 7.9l-6.9 6.9a2.1 2.1 0 0 1-3-3l6.9-6.9a6 6 0 0 1 7.9-7.9l-3.8 3.8Z"/>
                        </svg>
                    </div>
                    <h3 class="feature-title">灵活扩展</h3>
                    <p class="feature-desc">模块化设计，支持按需定制和扩展，满足业务变化</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Tech stack -->
    <section class="tech-stack">
        <div class="container">
            <div class="tech-content">
                <span class="tech-label">技术栈</span>
                <div class="tech-items">
                    <span class="tech-item">Laravel 13</span>
                    <span class="tech-item">Filament 5</span>
                    <span class="tech-item">Livewire 4</span>
                    <span class="tech-item">TailwindCSS 4</span>
                    <span class="tech-item">PostgreSQL</span>
                    <span class="tech-item">Redis</span>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta">
        <div class="container">
            <div class="cta-card">
                <h2 class="cta-title">准备好开始了吗？</h2>
                <p class="cta-description">
                    加入我们，开始构建您的下一个 SaaS 产品。快速、简单、强大。
                </p>
                <div class="cta-buttons">
                    <a href="/backend" class="btn-pill btn-primary">立即开始</a>
                    <a href="/docs" class="btn-pill btn-secondary">查看文档</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>v{{ app()->version() }} | Powered by <a href="https://github.com/jasonencode" target="_blank" rel="noopener noreferrer">JasonSaaS</a></p>
        </div>
    </footer>
</body>

</html>
