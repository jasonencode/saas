<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <style>
        :root {
            --color-spotify-green: #1ed760;
            --color-near-black: #121212;
            --color-dark-surface: #181818;
            --color-mid-dark: #1f1f1f;
            --color-white: #ffffff;
            --color-silver: #b3b3b3;
            --color-border-gray: #4d4d4d;
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
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            overflow-x: hidden;
        }

        /* Background Effects */
        .bg-gradient-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                radial-gradient(ellipse 80% 50% at 50% -20%, rgba(30, 215, 96, 0.15), transparent),
                radial-gradient(ellipse 60% 40% at 100% 50%, rgba(30, 215, 96, 0.08), transparent),
                radial-gradient(ellipse 50% 30% at 0% 80%, rgba(30, 215, 96, 0.06), transparent);
            pointer-events: none;
            z-index: 0;
        }

        .container {
            width: 100%;
            max-width: 75rem;
            margin: 0 auto;
            padding: 0 1.5rem;
            position: relative;
            z-index: 1;
        }

        /* Header */
        .header {
            position: sticky;
            top: 0;
            z-index: 100;
            padding: 1.5rem 0;
            background-color: rgba(18, 18, 18, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--color-white);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
        }

        .nav-link {
            color: var(--color-silver);
            text-decoration: none;
            font-size: 0.875rem;
            transition: color 0.2s;
        }

        .nav-link:hover {
            color: var(--color-white);
        }

        /* Hero Section */
        .hero {
            padding: 5rem 0 4rem;
        }

        .hero-wrapper {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            gap: 4rem;
        }

        .hero-content {
            flex: 1;
            max-width: 32rem;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.375rem 1rem;
            background-color: rgba(30, 215, 96, 0.1);
            border: 1px solid rgba(30, 215, 96, 0.3);
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--color-spotify-green);
            text-transform: uppercase;
            letter-spacing: 0.05em;
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
            50% { opacity: 0.5; }
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            line-height: 1.1;
            letter-spacing: -0.02em;
            margin-bottom: 1.5rem;
        }

        .hero-title span {
            color: var(--color-spotify-green);
        }

        .hero-description {
            font-size: 1.125rem;
            color: var(--color-silver);
            line-height: 1.7;
            margin-bottom: 2rem;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        /* Visual Card */
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

        .visual-icon {
            width: 6rem;
            height: 6rem;
            background: linear-gradient(135deg, var(--color-spotify-green), #1db954);
            border-radius: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 40px rgba(30, 215, 96, 0.3);
        }

        .visual-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }

        .visual-subtitle {
            color: var(--color-silver);
            font-size: 0.9375rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .visual-stats {
            display: flex;
            justify-content: space-between;
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
            color: var(--color-silver);
            margin-top: 0.25rem;
        }

        /* Decorative elements */
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

        /* Features Section */
        .features {
            padding: 4rem 0;
        }

        .section-header {
            text-align: center;
            max-width: 36rem;
            margin: 0 auto 3rem;
        }

        .section-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .section-subtitle {
            color: var(--color-silver);
            font-size: 1rem;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
        }

        .feature-card {
            background-color: var(--color-dark-surface);
            border-radius: 1rem;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.04);
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-4px);
            border-color: rgba(30, 215, 96, 0.3);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .feature-icon {
            width: 3rem;
            height: 3rem;
            background-color: rgba(30, 215, 96, 0.1);
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .feature-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .feature-desc {
            font-size: 0.875rem;
            color: var(--color-silver);
            line-height: 1.5;
        }

        /* Tech Stack */
        .tech-stack {
            padding: 3rem 0;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .tech-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 3rem;
            flex-wrap: wrap;
        }

        .tech-label {
            color: var(--color-silver);
            font-size: 0.875rem;
        }

        .tech-items {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .tech-item {
            padding: 0.5rem 1rem;
            background-color: var(--color-mid-dark);
            border-radius: 9999px;
            font-size: 0.8125rem;
            font-weight: 500;
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        /* CTA Section */
        .cta {
            padding: 5rem 0;
            text-align: center;
        }

        .cta-card {
            background: linear-gradient(145deg, var(--color-dark-surface), var(--color-mid-dark));
            border-radius: 1.5rem;
            padding: 4rem 2rem;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .cta-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60%;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--color-spotify-green), transparent);
        }

        .cta-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .cta-description {
            color: var(--color-silver);
            font-size: 1rem;
            margin-bottom: 2rem;
            max-width: 32rem;
            margin-left: auto;
            margin-right: auto;
        }

        /* Footer */
        .footer {
            padding: 2rem 0;
            text-align: center;
            color: var(--color-silver);
            font-size: 0.875rem;
        }

        .footer a {
            color: var(--color-spotify-green);
            text-decoration: none;
        }

        /* Buttons */
        .btn-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.875rem 2rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background-color: var(--color-spotify-green);
            color: #000000;
        }

        .btn-primary:hover {
            background-color: #2de26d;
            transform: scale(1.02);
            box-shadow: 0 10px 30px rgba(30, 215, 96, 0.3);
        }

        .btn-secondary {
            background-color: transparent;
            color: var(--color-white);
            border: 1px solid var(--color-border-gray);
        }

        .btn-secondary:hover {
            border-color: var(--color-white);
            background-color: rgba(255, 255, 255, 0.05);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero {
                grid-template-columns: 1fr;
                gap: 3rem;
                padding: 3rem 0;
                min-height: auto;
            }

            .hero-content {
                max-width: 100%;
                text-align: center;
            }

            .hero-buttons {
                justify-content: center;
            }

            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .hero-title {
                font-size: 2.5rem;
            }
        }

        @media (max-width: 640px) {
            .features-grid {
                grid-template-columns: 1fr;
            }

            .hero-title {
                font-size: 2rem;
            }

            .nav-links {
                display: none;
            }

            .visual-stats {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }
    </style>
</head>

<body>
    <div class="bg-gradient-overlay"></div>

    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="/" class="logo">
                    <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="32" height="32" rx="6" fill="#1ed760"/>
                        <path d="M8 23L16 9L24 23" stroke="#121212" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M11 18H21" stroke="#121212" stroke-width="3" stroke-linecap="round"/>
                    </svg>
                    JasonSaaS
                </a>
                <nav class="nav-links">
                    <a href="/docs" class="nav-link">文档</a>
                    <a href="/github" class="nav-link">GitHub</a>
                    <a href="/backend" class="nav-link">控制台</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-wrapper">
                <div class="hero-content">
                    <div class="hero-badge">Modern SaaS Foundation</div>
                    <h1 class="hero-title">Build <span>exceptional</span> SaaS applications</h1>
                    <p class="hero-description">
                        基于 Laravel 和 Filament 构建的现代化 SaaS 基座。预配置的多租户架构、完善的权限系统、优雅的前端组件，让您的 SaaS 开发快人一步。
                    </p>
                    <div class="hero-buttons">
                        <a href="/backend" class="btn-pill btn-primary">进入控制台</a>
                        <a href="https://github.com/jasonencode/saas" target="_blank" class="btn-pill btn-secondary">GitHub</a>
                    </div>
                </div>

                <div class="hero-visual">
                    <div class="visual-card">
                        <div class="deco-1"></div>
                        <div class="deco-2"></div>
                        <div class="visual-icon">🚀</div>
                        <h3 class="visual-title">Build faster, scale further.</h3>
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

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">为什么选择 JasonSaaS</h2>
                <p class="section-subtitle">开箱即用的功能特性，让您的 SaaS 开发更加高效</p>
            </div>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">⚡</div>
                    <h3 class="feature-title">开箱即用</h3>
                    <p class="feature-desc">预配置的多租户架构和权限系统，无需从零开始</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🎯</div>
                    <h3 class="feature-title">优雅设计</h3>
                    <p class="feature-desc">遵循 Laravel 最佳实践和设计模式，代码规范清晰</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔄</div>
                    <h3 class="feature-title">完整生态</h3>
                    <p class="feature-desc">集成订单、支付、通知等业务流程，开箱即用</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔧</div>
                    <h3 class="feature-title">灵活扩展</h3>
                    <p class="feature-desc">模块化设计，支持按需定制和扩展，满足业务变化</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Tech Stack -->
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

    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <div class="cta-card">
                <h2 class="cta-title">准备好开始了吗？</h2>
                <p class="cta-description">
                    加入我们，开始构建您的下一个 SaaS 产品。快速、简单、强大。
                </p>
                <div class="hero-buttons" style="justify-content: center;">
                    <a href="/backend" class="btn-pill btn-primary">
                        立即开始
                    </a>
                    <a href="/docs" class="btn-pill btn-secondary">
                        查看文档
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>v{{ app()->version() }} | Powered by <a href="https://github.com/jasonencode">JasonSaaS</a></p>
        </div>
    </footer>
</body>

</html>
