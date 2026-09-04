<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="referrer" content="no-referrer">
    <title>Cara Kerja Gotik - Platform Ticketing & Event Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #7c3aed;
            --primary-dark: #6d28d9;
            --primary-light: #a78bfa;
            --secondary: #0f172a;
            --surface: #ffffff;
            --surface-alt: #f8fafc;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border: #e2e8f0;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--text-primary);
            background-color: #ffffff;
            line-height: 1.6;
        }

        /* Progress Bar */
        .progress-bar {
            position: fixed;
            top: 0;
            left: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
            width: 0%;
            z-index: 999;
            transition: width 0.1s ease;
        }

        /* Header */
        header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 72px;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            padding: 0 24px;
            z-index: 100;
            padding-top: 3px;
        }

        .header-container {
            max-width: 100%;
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 24px;
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            letter-spacing: -0.5px;
        }

        .menu-toggle {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-primary);
            font-size: 24px;
            padding: 8px;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 72px;
            width: 280px;
            height: calc(100vh - 72px);
            background: var(--surface-alt);
            border-right: 1px solid var(--border);
            overflow-y: auto;
            padding: 32px 0;
            z-index: 90;
        }

        .sidebar-content {
            display: flex;
            flex-direction: column;
            gap: 32px;
            padding: 0 24px;
        }

        .nav-section {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .nav-section-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
            padding: 0 12px 8px 12px;
        }

        .nav-link {
            padding: 10px 12px;
            border-radius: 8px;
            text-decoration: none;
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            cursor: pointer;
            display: block;
        }

        .nav-link:hover {
            background-color: rgba(124, 58, 237, 0.1);
            color: var(--primary);
        }

        .nav-link.active {
            background-color: rgba(124, 58, 237, 0.15);
            color: var(--primary);
            font-weight: 600;
        }

        /* Main Content */
        .main-wrapper {
            display: flex;
            margin-top: 72px;
        }

        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 0;
            background: white;
        }

        section {
            padding: 80px 64px;
            border-bottom: 1px solid var(--border);
            opacity: 0;
            animation: fadeInUp 0.6s ease forwards;
        }

        section:nth-child(1) { animation-delay: 0.1s; }
        section:nth-child(2) { animation-delay: 0.2s; }
        section:nth-child(3) { animation-delay: 0.3s; }

        @@keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Typography */
        h1 {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 16px;
            letter-spacing: -1px;
            color: var(--text-primary);
        }

        h2 {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 24px;
            letter-spacing: -0.5px;
            color: var(--text-primary);
        }

        h3 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 12px;
            color: var(--text-primary);
        }

        p {
            color: var(--text-secondary);
            font-size: 16px;
            line-height: 1.7;
            margin-bottom: 16px;
        }

        .subtitle {
            font-size: 20px;
            color: var(--text-secondary);
            margin-bottom: 32px;
            font-weight: 500;
            line-height: 1.6;
        }

        /* Badge */
        .badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            background: rgba(124, 58, 237, 0.1);
            color: var(--primary);
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 16px;
        }

        .badge-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        /* Button */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-secondary {
            background: var(--surface-alt);
            color: var(--text-primary);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: var(--border);
        }

        .btn-large {
            padding: 16px 40px;
            font-size: 16px;
        }

        /* Cards */
        .card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 24px;
            transition: all 0.3s ease;
        }

        .card:hover {
            border-color: var(--primary);
            box-shadow: var(--shadow-md);
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 12px;
            color: var(--text-primary);
        }

        .card-description {
            font-size: 14px;
            color: var(--text-secondary);
            line-height: 1.6;
        }

        /* Grid */
        .grid {
            display: grid;
            gap: 24px;
            margin-bottom: 32px;
        }

        .grid-2 {
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        }

        .grid-3 {
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        }

        /* Workflow */
        .workflow {
            display: flex;
            flex-direction: column;
            gap: 24px;
            margin-bottom: 32px;
        }

        .workflow-step {
            display: flex;
            align-items: center;
            gap: 20px;
            position: relative;
        }

        .workflow-step::after {
            content: '';
            position: absolute;
            left: 20px;
            top: 60px;
            width: 2px;
            height: 60px;
            background: var(--border);
        }

        .workflow-step:last-child::after {
            display: none;
        }

        .workflow-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(124, 58, 237, 0.1), rgba(167, 139, 250, 0.1));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 24px;
            flex-shrink: 0;
            z-index: 1;
        }

        .workflow-content {
            flex: 1;
        }

        .workflow-content h4 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-primary);
        }

        .workflow-content p {
            margin: 0;
            font-size: 14px;
        }

        /* Flow Diagram */
        .flow-diagram {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 32px;
        }

        .flow-box {
            background: var(--surface-alt);
            border: 2px solid var(--border);
            border-radius: 8px;
            padding: 16px 24px;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
            text-align: center;
            min-width: 140px;
        }

        .flow-arrow {
            font-size: 24px;
            color: var(--primary);
            flex-shrink: 0;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: var(--surface-alt);
            border-radius: 12px;
            padding: 24px;
            text-align: center;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 14px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        /* Placeholder */
        .placeholder {
            background: linear-gradient(135deg, var(--surface-alt) 0%, rgba(124, 58, 237, 0.05) 100%);
            border: 2px dashed var(--border);
            border-radius: 12px;
            padding: 60px 40px;
            text-align: center;
            color: var(--text-secondary);
            font-size: 16px;
            margin-bottom: 32px;
        }

        .placeholder-icon {
            font-size: 48px;
            margin-bottom: 16px;
            color: var(--primary);
        }

        /* Ticket Examples */
        .ticket-card {
            background: white;
            border: 2px solid var(--border);
            border-radius: 12px;
            padding: 32px 24px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .ticket-card:hover {
            border-color: var(--primary);
            box-shadow: var(--shadow-lg);
            transform: translateY(-4px);
        }

        .ticket-type {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--primary);
            margin-bottom: 12px;
        }

        .ticket-price {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .ticket-quantity {
            font-size: 12px;
            color: var(--text-secondary);
            margin-bottom: 16px;
        }

        /* QR Code Mockup */
        .qr-mockup {
            background: white;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 32px;
            max-width: 400px;
            margin: 0 auto 32px;
        }

        .qr-header {
            text-align: center;
            margin-bottom: 24px;
        }

        .qr-event-name {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .qr-event-date {
            font-size: 13px;
            color: var(--text-secondary);
        }

        .qr-code {
            width: 200px;
            height: 200px;
            background: var(--surface-alt);
            border: 2px solid var(--border);
            border-radius: 8px;
            margin: 0 auto 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 80px;
            color: var(--primary);
        }

        .qr-footer {
            font-size: 13px;
            color: var(--text-secondary);
            text-align: center;
        }

        /* FAQ */
        .faq-container {
            max-width: 700px;
            margin: 0 auto;
        }

        .faq-item {
            margin-bottom: 16px;
        }

        .faq-question {
            width: 100%;
            padding: 20px 24px;
            background: var(--surface-alt);
            border: 1px solid var(--border);
            border-radius: 8px;
            text-align: left;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            color: var(--text-primary);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .faq-question:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .faq-question.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            border-radius: 8px 8px 0 0;
        }

        .faq-icon {
            font-size: 20px;
            transition: transform 0.3s ease;
        }

        .faq-question.active .faq-icon {
            transform: rotate(180deg);
        }

        .faq-answer {
            max-height: 0;
            overflow: hidden;
            background: white;
            border: 1px solid var(--border);
            border-top: none;
            border-radius: 0 0 8px 8px;
            transition: max-height 0.3s ease;
            padding: 0 24px;
        }

        .faq-answer.active {
            max-height: 500px;
            padding: 24px;
        }

        .faq-answer p {
            margin: 0;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            text-align: center;
            padding: 80px 64px;
            border-radius: 0;
        }

        .cta-section h2 {
            color: white;
            margin-bottom: 16px;
        }

        .cta-section p {
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 32px;
            font-size: 18px;
        }

        .btn-cta {
            background: white;
            color: var(--primary);
        }

        .btn-cta:hover {
            background: var(--surface-alt);
        }

        /* Drawer (Mobile Navigation) */
        .drawer {
            position: fixed;
            left: -280px;
            top: 72px;
            width: 280px;
            height: calc(100vh - 72px);
            background: var(--surface-alt);
            border-right: 1px solid var(--border);
            overflow-y: auto;
            padding: 32px 0;
            z-index: 95;
            transition: left 0.3s ease;
        }

        .drawer.active {
            left: 0;
        }

        .drawer-overlay {
            position: fixed;
            top: 72px;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 92;
        }

        .drawer-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* Responsive */
        @@media (max-width: 1024px) {
            .sidebar {
                width: 240px;
            }

            .main-content {
                margin-left: 240px;
            }

            section {
                padding: 60px 40px;
            }

            h1 {
                font-size: 40px;
            }

            h2 {
                font-size: 28px;
            }
        }

        @@media (max-width: 768px) {
            .sidebar {
                display: none;
            }

            .main-content {
                margin-left: 0;
            }

            .menu-toggle {
                display: block;
            }

            .drawer-content {
                padding: 0 24px;
            }

            section {
                padding: 60px 24px;
            }

            h1 {
                font-size: 32px;
            }

            h2 {
                font-size: 24px;
            }

            .subtitle {
                font-size: 16px;
            }

            .grid-3 {
                grid-template-columns: 1fr;
            }

            .flow-diagram {
                flex-direction: column;
            }

            .flow-arrow {
                transform: rotate(90deg);
                margin: -8px 0;
            }

            .cta-section {
                padding: 60px 24px;
            }

            .qr-mockup {
                max-width: 100%;
            }

            .faq-container {
                max-width: 100%;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @@media (max-width: 480px) {
            h1 {
                font-size: 28px;
                margin-bottom: 12px;
            }

            h2 {
                font-size: 20px;
            }

            .subtitle {
                font-size: 15px;
                margin-bottom: 24px;
            }

            section {
                padding: 48px 16px;
            }

            .btn-large {
                width: 100%;
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .faq-question {
                padding: 16px;
                font-size: 14px;
            }

            .faq-answer {
                padding: 0 16px;
            }

            .faq-answer.active {
                padding: 16px;
            }

            .card {
                padding: 16px;
            }

            .placeholder {
                padding: 40px 20px;
            }

            .placeholder-icon {
                font-size: 36px;
            }
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--surface-alt);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary);
        }
    </style>
</head>
<body>
    <!-- Progress Bar -->
    <div class="progress-bar" id="progressBar"></div>

    <!-- Header -->
    <header>
        <div class="header-container">
            <a href="#hero" class="logo">GOTIK</a>
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </header>

    <!-- Drawer Overlay -->
    <div class="drawer-overlay" id="drawerOverlay"></div>

    <!-- Sidebar / Drawer -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-content" id="sidebarContent">
            <!-- Navigation will be populated by JavaScript -->
        </div>
    </aside>

    <!-- Drawer (Mobile) -->
    <div class="drawer" id="drawer">
        <div class="drawer-content">
            <!-- Navigation will be populated by JavaScript -->
        </div>
    </div>

    <!-- Main Content -->
    <main class="main-content">
        <!-- 1. Hero / Pengenalan -->
        <section id="pengenalan">
            <div class="badge">Panduan Privat</div>
            <h1>Cara Kerja Gotik</h1>
            <p class="subtitle">Kelola event, penjualan tiket, pembayaran hingga proses check-in dalam satu platform.</p>
            @if (! empty($recipientName))
                <p style="color: var(--text-secondary); margin-bottom: 12px;">
                    Panduan ini disiapkan untuk {{ $recipientName }}
                </p>
            @endif
            <p style="color: var(--text-secondary); margin-bottom: 32px;">
                <i class="fas fa-calendar-alt"></i> Akses tersedia hingga {{ $expiresAt->locale('id')->translatedFormat('j F Y') }}
            </p>
            <a href="#menjadi-penyelenggara" class="btn btn-primary">
                <i class="fas fa-arrow-right"></i> Mulai Sekarang
            </a>
        </section>

        <!-- 2. Cara Kerja Gotik -->
        <section id="cara-kerja">
            <h2>Cara Kerja Gotik</h2>
            <p>Berikut adalah workflow lengkap dari awal event didaftarkan hingga dana dicairkan ke rekening Anda.</p>

            <div class="workflow">
                <div class="workflow-step">
                    <div class="workflow-icon"><i class="fas fa-edit"></i></div>
                    <div class="workflow-content">
                        <h4>Daftarkan Event</h4>
                        <p>Mulai dengan mendaftarkan event baru di platform Gotik dan lengkapi informasi dasar.</p>
                    </div>
                </div>

                <div class="workflow-step">
                    <div class="workflow-icon"><i class="fas fa-handshake"></i></div>
                    <div class="workflow-content">
                        <h4>Kesepakatan & Aktivasi</h4>
                        <p>Tim Gotik akan melakukan verifikasi dan aktivasi akun event Anda.</p>
                    </div>
                </div>

                <div class="workflow-step">
                    <div class="workflow-icon"><i class="fas fa-ticket-alt"></i></div>
                    <div class="workflow-content">
                        <h4>Setup Tiket & Pembayaran</h4>
                        <p>Atur kategori tiket, harga, dan metode pembayaran yang ingin diaktifkan.</p>
                    </div>
                </div>

                <div class="workflow-step">
                    <div class="workflow-icon"><i class="fas fa-rocket"></i></div>
                    <div class="workflow-content">
                        <h4>Mulai Penjualan</h4>
                        <p>Bagikan link event dan mulai menerima pembelian tiket dari pembeli.</p>
                    </div>
                </div>

                <div class="workflow-step">
                    <div class="workflow-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="workflow-content">
                        <h4>Pantau Transaksi</h4>
                        <p>Gunakan dashboard untuk memantau penjualan dan transaksi secara real-time.</p>
                    </div>
                </div>

                <div class="workflow-step">
                    <div class="workflow-icon"><i class="fas fa-qrcode"></i></div>
                    <div class="workflow-content">
                        <h4>Scan Tiket Saat Event</h4>
                        <p>Gunakan fitur scanner untuk memverifikasi tiket dan check-in pengunjung.</p>
                    </div>
                </div>

                <div class="workflow-step">
                    <div class="workflow-icon"><i class="fas fa-wallet"></i></div>
                    <div class="workflow-content">
                        <h4>Penarikan Dana</h4>
                        <p>Ajukan penarikan dana ke rekening bank Anda setelah event selesai.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- 3. Menjadi Penyelenggara -->
        <section id="menjadi-penyelenggara">
            <h2>Menjadi Penyelenggara</h2>
            <p>Proses sederhana untuk mulai mengelola event Anda bersama Gotik.</p>

            <div class="flow-diagram">
                <div class="flow-box">📞 Hubungi Gotik</div>
                <div class="flow-arrow">→</div>
                <div class="flow-box">💬 Diskusi Event</div>
                <div class="flow-arrow">→</div>
                <div class="flow-box">✅ Kesepakatan</div>
                <div class="flow-arrow">→</div>
                <div class="flow-box">👤 Akun Penyewa</div>
                <div class="flow-arrow">→</div>
                <div class="flow-box">🚀 Aktivasi Event</div>
                <div class="flow-arrow">→</div>
                <div class="flow-box">💰 Mulai Penjualan</div>
            </div>

            <div class="grid grid-3">
                <div class="card">
                    <h3 style="margin-bottom: 16px;"><i class="fas fa-phone" style="color: var(--primary); margin-right: 8px;"></i> Hubungi Tim</h3>
                    <p>Hubungi tim Gotik untuk mendiskusikan kebutuhan dan detail event Anda.</p>
                </div>
                <div class="card">
                    <h3 style="margin-bottom: 16px;"><i class="fas fa-file-contract" style="color: var(--primary); margin-right: 8px;"></i> Verifikasi</h3>
                    <p>Tim kami akan melakukan verifikasi data dan kesepakatan syarat & ketentuan.</p>
                </div>
                <div class="card">
                    <h3 style="margin-bottom: 16px;"><i class="fas fa-lock-open" style="color: var(--primary); margin-right: 8px;"></i> Aktivasi</h3>
                    <p>Akun Anda aktif dan siap untuk mulai mengatur detail event dan tiket.</p>
                </div>
            </div>
        </section>

        <!-- 4. Setup Event -->
        <section id="setup-event">
            <h2>Setup Event</h2>
            <p>Lengkapi informasi event dengan mengisi detail penting di bawah ini.</p>

            <div class="placeholder">
                <div class="placeholder-icon"><i class="fas fa-cog"></i></div>
                <p><strong>Form Setup Event Gotik</strong></p>
                <p style="margin-bottom: 0; font-size: 13px;">Nama Event • Deskripsi • Tanggal & Waktu • Lokasi • Kategori • Banner Event</p>
            </div>

            <div class="grid grid-2">
                <div class="card">
                    <h3 style="margin-bottom: 16px;"><i class="fas fa-info-circle" style="color: var(--primary); margin-right: 8px;"></i> Informasi Dasar</h3>
                    <p>Masukkan nama event, deskripsi singkat, dan pilih kategori event yang sesuai.</p>
                </div>
                <div class="card">
                    <h3 style="margin-bottom: 16px;"><i class="fas fa-map-marker-alt" style="color: var(--primary); margin-right: 8px;"></i> Lokasi & Waktu</h3>
                    <p>Tentukan tanggal, waktu, dan lokasi penyelenggaraan event dengan detail.</p>
                </div>
                <div class="card">
                    <h3 style="margin-bottom: 16px;"><i class="fas fa-images" style="color: var(--primary); margin-right: 8px;"></i> Media Visual</h3>
                    <p>Upload banner atau poster event untuk menarik perhatian calon pembeli.</p>
                </div>
                <div class="card">
                    <h3 style="margin-bottom: 16px;"><i class="fas fa-align-left" style="color: var(--primary); margin-right: 8px;"></i> Deskripsi Detail</h3>
                    <p>Jelaskan detail lengkap event, fasilitas, speaker, dan informasi penting lainnya.</p>
                </div>
            </div>
        </section>

        <!-- 5. Tiket & Harga -->
        <section id="tiket-harga">
            <h2>Tiket & Harga</h2>
            <p>Buat beberapa kategori tiket dengan harga dan kuota yang berbeda-beda.</p>

            <div class="grid grid-3">
                <div class="ticket-card">
                    <div class="ticket-type">Presale</div>
                    <div class="ticket-price">Rp 150K</div>
                    <div class="ticket-quantity">Kuota: 100 tiket</div>
                    <p style="font-size: 13px; margin-bottom: 0;">Diskon khusus untuk pembeli awal. Tersedia untuk 1 minggu pertama.</p>
                </div>

                <div class="ticket-card">
                    <div class="ticket-type">Regular</div>
                    <div class="ticket-price">Rp 200K</div>
                    <div class="ticket-quantity">Kuota: 500 tiket</div>
                    <p style="font-size: 13px; margin-bottom: 0;">Harga reguler untuk periode penjualan normal event.</p>
                </div>

                <div class="ticket-card">
                    <div class="ticket-type">VIP</div>
                    <div class="ticket-price">Rp 500K</div>
                    <div class="ticket-quantity">Kuota: 50 tiket</div>
                    <p style="font-size: 13px; margin-bottom: 0;">Akses premium dengan benefit eksklusif dan tempat duduk terbaik.</p>
                </div>
            </div>

            <p style="margin-top: 32px; text-align: center; color: var(--text-secondary);">
                💡 Tip: Gunakan multiple tier pricing untuk maksimalkan revenue dan ciptakan sense of urgency dengan penawaran terbatas.
            </p>
        </section>

        <!-- 6. Cara Pembeli Membeli Tiket -->
        <section id="cara-pembeli-beli">
            <h2>Cara Pembeli Membeli Tiket</h2>
            <p>Proses pembelian tiket yang mudah dan cepat untuk calon pengunjung event Anda.</p>

            <div class="flow-diagram">
                <div class="flow-box">🔍 Pilih Event</div>
                <div class="flow-arrow">→</div>
                <div class="flow-box">🎫 Pilih Tiket</div>
                <div class="flow-arrow">→</div>
                <div class="flow-box">📝 Isi Data</div>
                <div class="flow-arrow">→</div>
                <div class="flow-box">✔️ Verifikasi</div>
                <div class="flow-arrow">→</div>
                <div class="flow-box">💳 Pembayaran</div>
                <div class="flow-arrow">→</div>
                <div class="flow-box">📧 E-Ticket</div>
            </div>

            <div class="grid grid-2">
                <div class="card">
                    <h3 style="margin-bottom: 16px;"><i class="fas fa-search" style="color: var(--primary); margin-right: 8px;"></i> Cari Event</h3>
                    <p>Pembeli dapat mencari event yang tersedia di platform atau mengakses link event langsung.</p>
                </div>
                <div class="card">
                    <h3 style="margin-bottom: 16px;"><i class="fas fa-check-circle" style="color: var(--primary); margin-right: 8px;"></i> Verifikasi Data</h3>
                    <p>Sistem akan memverifikasi data pembeli sebelum proses pembayaran dimulai.</p>
                </div>
                <div class="card">
                    <h3 style="margin-bottom: 16px;"><i class="fas fa-credit-card" style="color: var(--primary); margin-right: 8px;"></i> Metode Pembayaran</h3>
                    <p>Pembeli dapat memilih dari berbagai metode pembayaran yang tersedia di Gotik.</p>
                </div>
                <div class="card">
                    <h3 style="margin-bottom: 16px;"><i class="fas fa-check" style="color: var(--success); margin-right: 8px;"></i> Konfirmasi & E-Ticket</h3>
                    <p>Setelah pembayaran berhasil, pembeli langsung menerima e-ticket via email.</p>
                </div>
            </div>
        </section>

        <!-- 7. Pembayaran -->
        <section id="pembayaran">
            <h2>Pembayaran</h2>
            <p>Platform Gotik mendukung berbagai metode pembayaran untuk kemudahan pelanggan Anda.</p>

            <div class="placeholder">
                <div class="placeholder-icon"><i class="fas fa-credit-card"></i></div>
                <p><strong>Metode Pembayaran Gotik</strong></p>
                <p style="margin-bottom: 0; font-size: 13px;">E-Wallet • Transfer Bank • QRIS • Cicilan • Kartu Kredit</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value">2.5 Juta</div>
                    <div class="stat-label">Transaksi/Bulan</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">99.9%</div>
                    <div class="stat-label">Uptime</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">&lt;2 Detik</div>
                    <div class="stat-label">Proses Pembayaran</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">100%</div>
                    <div class="stat-label">Keamanan PCI DSS</div>
                </div>
            </div>

            <div class="grid grid-2">
                <div class="card">
                    <h3 style="margin-bottom: 16px;"><i class="fas fa-lock" style="color: var(--primary); margin-right: 8px;"></i> Keamanan Transaksi</h3>
                    <p>Semua transaksi dilindungi dengan enkripsi tingkat bank dan standar keamanan internasional.</p>
                </div>
                <div class="card">
                    <h3 style="margin-bottom: 16px;"><i class="fas fa-redo" style="color: var(--primary); margin-right: 8px;"></i> Instant Settlement</h3>
                    <p>Pembayaran yang berhasil langsung terdaftar di sistem dan dapat ditarik kapan saja.</p>
                </div>
            </div>
        </section>

        <!-- 8. Dashboard & Transaksi -->
        <section id="dashboard-transaksi">
            <h2>Dashboard & Transaksi</h2>
            <p>Monitor semua aktivitas dan transaksi event Anda melalui dashboard yang intuitif.</p>

            <div class="placeholder">
                <div class="placeholder-icon"><i class="fas fa-chart-bar"></i></div>
                <p><strong>Screenshot Dashboard Gotik</strong></p>
                <p style="margin-bottom: 0; font-size: 13px;">Real-time Analytics • Sales Chart • Transaction History • Performance Metrics</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value">1,250</div>
                    <div class="stat-label">Total Transaksi</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">Rp 250M</div>
                    <div class="stat-label">Total Pendapatan</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">98.5%</div>
                    <div class="stat-label">Tiket Terjual</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">4.8★</div>
                    <div class="stat-label">Rating Event</div>
                </div>
            </div>

            <div class="grid grid-2">
                <div class="card">
                    <h3 style="margin-bottom: 16px;"><i class="fas fa-list" style="color: var(--primary); margin-right: 8px;"></i> Histori Transaksi</h3>
                    <p>Lihat semua transaksi pembelian tiket dengan detail lengkap (pembeli, jumlah, tanggal, status).</p>
                </div>
                <div class="card">
                    <h3 style="margin-bottom: 16px;"><i class="fas fa-download" style="color: var(--primary); margin-right: 8px;"></i> Export & Laporan</h3>
                    <p>Download laporan transaksi dalam format Excel atau PDF untuk keperluan administratif.</p>
                </div>
                <div class="card">
                    <h3 style="margin-bottom: 16px;"><i class="fas fa-eye" style="color: var(--primary); margin-right: 8px;"></i> Monitoring Real-time</h3>
                    <p>Monitor penjualan tiket dan pendapatan secara langsung dengan grafik dan statistik.</p>
                </div>
                <div class="card">
                    <h3 style="margin-bottom: 16px;"><i class="fas fa-cog" style="color: var(--primary); margin-right: 8px;"></i> Pengaturan Event</h3>
                    <p>Ubah detail event, harga tiket, dan pengaturan pembayaran kapan saja sesuai kebutuhan.</p>
                </div>
            </div>
        </section>

        <!-- 9. QR Ticket -->
        <section id="qr-ticket">
            <h2>QR Ticket</h2>
            <p>Setiap pembelian tiket akan mendapatkan e-ticket dengan QR Code unik untuk check-in.</p>

            <div class="qr-mockup">
                <div class="qr-header">
                    <div class="qr-event-name">TechFest 2026</div>
                    <div class="qr-event-date">25 September 2026 • Jakarta Convention Center</div>
                </div>
                <div class="qr-code">
                    <i class="fas fa-qrcode"></i>
                </div>
                <div style="background: var(--surface-alt); padding: 16px; border-radius: 8px; margin-bottom: 16px; text-align: left; font-size: 13px;">
                    <p style="margin: 0 0 8px 0;"><strong>Pemegang Tiket:</strong> John Doe</p>
                    <p style="margin: 0 0 8px 0;"><strong>Tipe Tiket:</strong> VIP</p>
                    <p style="margin: 0 0 8px 0;"><strong>No. Tiket:</strong> GOT-2026-001234</p>
                    <p style="margin: 0;"><strong>Waktu Masuk:</strong> 08:00 - 23:59</p>
                </div>
                <div class="qr-footer">
                    Tunjukkan QR Code ini pada saat check-in. Satu tiket hanya untuk satu orang.
                </div>
            </div>

            <div class="grid grid-2">
                <div class="card">
                    <h3 style="margin-bottom: 16px;"><i class="fas fa-mobile-alt" style="color: var(--primary); margin-right: 8px;"></i> E-Ticket Digital</h3>
                    <p>Pembeli menerima e-ticket dalam format digital yang dapat diakses lewat email atau aplikasi.</p>
                </div>
                <div class="card">
                    <h3 style="margin-bottom: 16px;"><i class="fas fa-shield-alt" style="color: var(--primary); margin-right: 8px;"></i> Keamanan Ticket</h3>
                    <p>QR Code unik dan terenkripsi mencegah pemalsuan atau duplikasi tiket event.</p>
                </div>
            </div>
        </section>

        <!-- 10. Scanner & Check-in -->
        <section id="scanner-checkin">
            <h2>Scanner & Check-in</h2>
            <p>Proses check-in pengunjung yang cepat dan efisien menggunakan aplikasi scanner Gotik.</p>

            <div class="flow-diagram">
                <div class="flow-box">👥 Pengunjung Datang</div>
                <div class="flow-arrow">→</div>
                <div class="flow-box">📱 QR Dipindai</div>
                <div class="flow-arrow">→</div>
                <div class="flow-box">✓ Verifikasi Tiket</div>
                <div class="flow-arrow">→</div>
                <div class="flow-box">✅ Check-in Berhasil</div>
            </div>

            <div class="placeholder">
                <div class="placeholder-icon"><i class="fas fa-mobile-alt"></i></div>
                <p><strong>Screenshot Scanner App Gotik</strong></p>
                <p style="margin-bottom: 0; font-size: 13px;">Camera QR Scanner • Real-time Status • Ticket Validation • Check-in Counter</p>
            </div>

            <div class="grid grid-2">
                <div class="card">
                    <h3 style="margin-bottom: 16px;"><i class="fas fa-camera" style="color: var(--primary); margin-right: 8px;"></i> Scanner Real-time</h3>
                    <p>Gunakan kamera smartphone untuk memindai QR Code tiket dengan cepat dan akurat.</p>
                </div>
                <div class="card">
                    <h3 style="margin-bottom: 16px;"><i class="fas fa-wifi" style="color: var(--primary); margin-right: 8px;"></i> Offline Mode</h3>
                    <p>Fitur scanner dapat bekerja tanpa koneksi internet dengan sinkronisasi otomatis saat online.</p>
                </div>
                <div class="card">
                    <h3 style="margin-bottom: 16px;"><i class="fas fa-chart-pie" style="color: var(--primary); margin-right: 8px;"></i> Statistik Live</h3>
                    <p>Lihat statistik check-in real-time untuk mengetahui berapa banyak pengunjung yang sudah masuk.</p>
                </div>
                <div class="card">
                    <h3 style="margin-bottom: 16px;"><i class="fas fa-users" style="color: var(--primary); margin-right: 8px;"></i> Multi-checker</h3>
                    <p>Buat multiple scanner untuk mempercepat proses check-in di berbagai pintu masuk event.</p>
                </div>
            </div>
        </section>

        <!-- 11. Laporan -->
        <section id="laporan">
            <h2>Laporan</h2>
            <p>Akses laporan komprehensif tentang penjualan, transaksi, dan check-in event Anda.</p>

            <div class="grid grid-2">
                <div class="card">
                    <h3 style="margin-bottom: 16px;"><i class="fas fa-ticket-alt" style="color: var(--primary); margin-right: 8px;"></i> Laporan Penjualan Tiket</h3>
                    <p>Detail penjualan per kategori tiket, termasuk jumlah terjual, revenue, dan performa penjualan.</p>
                </div>
                <div class="card">
                    <h3 style="margin-bottom: 16px;"><i class="fas fa-exchange-alt" style="color: var(--primary); margin-right: 8px;"></i> Laporan Transaksi</h3>
                    <p>Rincian lengkap setiap transaksi pembayaran, metode pembayaran, dan status pembayaran.</p>
                </div>
                <div class="card">
                    <h3 style="margin-bottom: 16px;"><i class="fas fa-sign-in-alt" style="color: var(--primary); margin-right: 8px;"></i> Laporan Check-in</h3>
                    <p>Statistik check-in pengunjung, jumlah pengunjung yang hadir, dan perbandingan dengan kapasitas.</p>
                </div>
                <div class="card">
                    <h3 style="margin-bottom: 16px;"><i class="fas fa-download" style="color: var(--primary); margin-right: 8px;"></i> Export Laporan</h3>
                    <p>Download laporan dalam format Excel, PDF, atau CSV untuk kebutuhan administrasi dan audit.</p>
                </div>
            </div>

            <div class="placeholder" style="margin-top: 32px;">
                <div class="placeholder-icon"><i class="fas fa-file-pdf"></i></div>
                <p><strong>Contoh Laporan Event Gotik</strong></p>
                <p style="margin-bottom: 0; font-size: 13px;">Comprehensive Report • Sales Analytics • Transaction Details • Attendance Summary</p>
            </div>
        </section>

        <!-- 12. Penarikan Dana -->
        <section id="penarikan-dana">
            <h2>Penarikan Dana</h2>
            <p>Proses pencairan dana yang mudah dan aman ke rekening bank Anda.</p>

            <div class="flow-diagram">
                <div class="flow-box">💰 Penjualan</div>
                <div class="flow-arrow">→</div>
                <div class="flow-box">📊 Rekap Transaksi</div>
                <div class="flow-arrow">→</div>
                <div class="flow-box">📤 Ajukan Penarikan</div>
                <div class="flow-arrow">→</div>
                <div class="flow-box">✓ Verifikasi</div>
                <div class="flow-arrow">→</div>
                <div class="flow-box">🏦 Dana Dikirim</div>
            </div>

            <div class="grid grid-2">
                <div class="card">
                    <h3 style="margin-bottom: 16px;"><i class="fas fa-calculator" style="color: var(--primary); margin-right: 8px;"></i> Perhitungan Otomatis</h3>
                    <p>Sistem secara otomatis menghitung pendapatan Anda setelah dikurangi biaya platform dan pajak.</p>
                </div>
                <div class="card">
                    <h3 style="margin-bottom: 16px;"><i class="fas fa-clock" style="color: var(--primary); margin-right: 8px;"></i> Proses Cepat</h3>
                    <p>Dana Anda akan ditransfer dalam waktu 1-3 hari kerja setelah persetujuan.</p>
                </div>
                <div class="card">
                    <h3 style="margin-bottom: 16px;"><i class="fas fa-hand-holding-usd" style="color: var(--primary); margin-right: 8px;"></i> Biaya Transparan</h3>
                    <p>Tidak ada biaya tersembunyi. Semua biaya ditampilkan dengan jelas sebelum penarikan dana.</p>
                </div>
                <div class="card">
                    <h3 style="margin-bottom: 16px;"><i class="fas fa-shield-alt" style="color: var(--primary); margin-right: 8px;"></i> Keamanan Dana</h3>
                    <p>Dana Anda disimpan dalam escrow account terpisah dan sepenuhnya aman.</p>
                </div>
            </div>
        </section>

        <!-- 13. FAQ -->
        <section id="faq">
            <h2>Pertanyaan yang Sering Diajukan</h2>
            <p style="margin-bottom: 40px;">Temukan jawaban untuk pertanyaan umum tentang platform Gotik.</p>

            <div class="faq-container">
                <div class="faq-item">
                    <button class="faq-question">
                        <span>Apakah penyelenggara harus memiliki website?</span>
                        <span class="faq-icon"><i class="fas fa-chevron-down"></i></span>
                    </button>
                    <div class="faq-answer">
                        <p>Tidak perlu. Platform Gotik menyediakan halaman event lengkap yang dapat digunakan untuk menjual tiket. Anda hanya perlu membagikan link event kepada calon pembeli, baik melalui media sosial, email, atau channel lainnya.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question">
                        <span>Apakah Gotik menyediakan halaman penjualan tiket?</span>
                        <span class="faq-icon"><i class="fas fa-chevron-down"></i></span>
                    </button>
                    <div class="faq-answer">
                        <p>Ya, Gotik menyediakan halaman penjualan tiket yang fully customizable untuk setiap event. Halaman tersebut responsif, modern, dan dioptimalkan untuk konversi tinggi. Anda dapat mengatur desain, deskripsi, harga, dan kategori tiket sesuai kebutuhan.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question">
                        <span>Apakah tiket memiliki QR Code?</span>
                        <span class="faq-icon"><i class="fas fa-chevron-down"></i></span>
                    </button>
                    <div class="faq-answer">
                        <p>Ya, setiap tiket yang dibeli akan memiliki QR Code unik yang dikirimkan melalui email ke pembeli. QR Code ini digunakan untuk verifikasi tiket saat check-in event.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question">
                        <span>Bagaimana proses check-in di event?</span>
                        <span class="faq-icon"><i class="fas fa-chevron-down"></i></span>
                    </button>
                    <div class="faq-answer">
                        <p>Pengunjung datang ke event dan menunjukkan QR Code dari e-ticket mereka. Petugas menggunakan aplikasi scanner Gotik untuk memindai QR Code, sistem akan memverifikasi tiket, dan pengunjung dapat langsung masuk ke event.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question">
                        <span>Bagaimana pencairan dana dilakukan?</span>
                        <span class="faq-icon"><i class="fas fa-chevron-down"></i></span>
                    </button>
                    <div class="faq-answer">
                        <p>Anda dapat mengajukan penarikan dana melalui dashboard setelah event selesai. Dana akan dihitung otomatis setelah dikurangi biaya platform. Proses verifikasi memakan waktu 1-2 hari, dan dana akan ditransfer ke rekening bank Anda dalam 1-3 hari kerja.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question">
                        <span>Berapa biaya platform Gotik?</span>
                        <span class="faq-icon"><i class="fas fa-chevron-down"></i></span>
                    </button>
                    <div class="faq-answer">
                        <p>Biaya platform Gotik bervariasi tergantung pada paket dan volume transaksi Anda. Hubungi tim Gotik untuk mendapatkan penawaran khusus dan skema biaya yang sesuai dengan kebutuhan event Anda.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- 14. CTA - Hubungi Gotik -->
        <section class="cta-section" id="hubungi">
            <h2>Siap Menjalankan Event Bersama Gotik?</h2>
            <p>Hubungi tim Gotik sekarang untuk memulai perjalanan event Anda dan memaksimalkan penjualan tiket.</p>
            <a href="mailto:hello@gotik.io" class="btn btn-large btn-cta">
                <i class="fas fa-envelope"></i> Hubungi Tim Gotik
            </a>
        </section>
    </main>

    <script>
        // Navigation structure
        const navStructure = [
            {
                title: 'MENGENAL GOTIK',
                items: [
                    { label: 'Pengenalan', id: 'pengenalan' },
                    { label: 'Cara Kerja Gotik', id: 'cara-kerja' }
                ]
            },
            {
                title: 'MEMULAI EVENT',
                items: [
                    { label: 'Menjadi Penyelenggara', id: 'menjadi-penyelenggara' },
                    { label: 'Setup Event', id: 'setup-event' },
                    { label: 'Tiket & Harga', id: 'tiket-harga' }
                ]
            },
            {
                title: 'PENJUALAN',
                items: [
                    { label: 'Cara Pembeli Membeli Tiket', id: 'cara-pembeli-beli' },
                    { label: 'Pembayaran', id: 'pembayaran' },
                    { label: 'Dashboard & Transaksi', id: 'dashboard-transaksi' }
                ]
            },
            {
                title: 'HARI-H EVENT',
                items: [
                    { label: 'QR Ticket', id: 'qr-ticket' },
                    { label: 'Scanner & Check-in', id: 'scanner-checkin' }
                ]
            },
            {
                title: 'KEUANGAN',
                items: [
                    { label: 'Laporan', id: 'laporan' },
                    { label: 'Penarikan Dana', id: 'penarikan-dana' }
                ]
            },
            {
                title: 'LAINNYA',
                items: [
                    { label: 'FAQ', id: 'faq' },
                    { label: 'Hubungi Gotik', id: 'hubungi' }
                ]
            }
        ];

        // Render navigation
        function renderNavigation() {
            let navHTML = '';
            navStructure.forEach(section => {
                navHTML += `<div class="nav-section">
                    <div class="nav-section-title">${section.title}</div>`;
                section.items.forEach(item => {
                    navHTML += `<a href="#${item.id}" class="nav-link" data-id="${item.id}">${item.label}</a>`;
                });
                navHTML += `</div>`;
            });

            document.getElementById('sidebarContent').innerHTML = navHTML;

            // Duplicate for drawer
            const drawerContent = document.querySelector('.drawer-content');
            drawerContent.innerHTML = navHTML;
        }

        // Mobile drawer toggle
        const menuToggle = document.getElementById('menuToggle');
        const drawer = document.getElementById('drawer');
        const drawerOverlay = document.getElementById('drawerOverlay');

        menuToggle.addEventListener('click', () => {
            drawer.classList.toggle('active');
            drawerOverlay.classList.toggle('active');
        });

        drawerOverlay.addEventListener('click', () => {
            drawer.classList.remove('active');
            drawerOverlay.classList.remove('active');
        });

        // Close drawer when link clicked
        document.querySelectorAll('.drawer-content .nav-link').forEach(link => {
            link.addEventListener('click', () => {
                drawer.classList.remove('active');
                drawerOverlay.classList.remove('active');
            });
        });

        // Active link tracking
        function updateActiveLink() {
            const sections = document.querySelectorAll('section');
            const scrollPosition = window.scrollY + 200;

            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;

                if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
                    document.querySelectorAll('.nav-link').forEach(link => {
                        link.classList.remove('active');
                    });
                    document.querySelectorAll(`[data-id="${section.id}"]`).forEach(link => {
                        link.classList.add('active');
                    });
                }
            });
        }

        // Progress bar
        function updateProgressBar() {
            const scrollTop = window.scrollY;
            const docHeight = document.documentElement.scrollHeight - window.innerHeight;
            const scrolled = (scrollTop / docHeight) * 100;
            document.getElementById('progressBar').style.width = scrolled + '%';
        }

        // FAQ Accordion
        document.querySelectorAll('.faq-question').forEach(question => {
            question.addEventListener('click', function() {
                const answer = this.nextElementSibling;
                const isActive = this.classList.contains('active');

                // Close all other FAQs
                document.querySelectorAll('.faq-question.active').forEach(q => {
                    if (q !== this) {
                        q.classList.remove('active');
                        q.nextElementSibling.classList.remove('active');
                    }
                });

                // Toggle current FAQ
                this.classList.toggle('active');
                answer.classList.toggle('active');
            });
        });

        // Smooth scroll for navigation links
        document.querySelectorAll('.nav-link, a[href^="#"]').forEach(link => {
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href === '#' || !href.startsWith('#')) return;

                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        // Event listeners
        window.addEventListener('scroll', () => {
            updateActiveLink();
            updateProgressBar();
        });

        // Initialize
        renderNavigation();
        updateActiveLink();
        updateProgressBar();

        // Intersection Observer for animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -100px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animation = 'fadeInUp 0.6s ease forwards';
                }
            });
        }, observerOptions);

        document.querySelectorAll('section').forEach(section => {
            observer.observe(section);
        });
    </script>
</body>
</html>
