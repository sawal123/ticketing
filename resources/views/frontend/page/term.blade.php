@extends('frontend.index')

@section('content')
    <style>
        /* ── PAGE LAYOUT ── */
        .page-wrap {
            max-width: 1180px;
            margin: 0 auto;
            padding: 48px 24px 120px;
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 40px;
            position: relative;
            z-index: 1;
            align-items: start;
            /* Prevent any child from breaking out */
            min-width: 0;
        }

        /* ── SIDEBAR / TOC ── */
        .sidebar {
            position: sticky;
            top: 88px;
            animation: fadeUp 0.5s ease both;
            min-width: 0;
        }

        .sidebar-header {
            font-family: 'Space Mono', monospace;
            font-size: 9px;
            color: var(--teal);
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .sidebar-header::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .toc-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 12px;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.18s;
            margin-bottom: 4px;
            text-align: left;
            background: none;
            width: 100%;
        }

        .toc-item:hover {
            background: var(--surface2);
            border-color: var(--border);
        }

        .toc-item.active {
            background: var(--surface2);
            border-color: rgba(108, 92, 231, 0.35);
        }

        .toc-item.active .toc-num {
            color: var(--gold);
        }

        .toc-item.active .toc-label {
            color: var(--text);
        }

        .toc-num {
            font-family: 'Space Mono', monospace;
            font-size: 10px;
            color: var(--muted);
            min-width: 20px;
            flex-shrink: 0;
        }

        .toc-label {
            font-size: 13px;
            color: var(--muted);
            line-height: 1.3;
            transition: color 0.18s;
        }

        .toc-item.active .toc-label {
            color: var(--text);
            font-weight: 500;
        }

        /* active bar */
        .toc-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            width: 3px;
            height: 20px;
            background: var(--gold);
            border-radius: 0 2px 2px 0;
            box-shadow: 0 0 8px rgba(245, 200, 66, 0.5);
        }

        .toc-item {
            position: relative;
        }

        /* ── CONTENT ── */
        .content-area {
            animation: fadeUp 0.5s 0.1s ease both;
            min-width: 0;
            /* Critical — prevents grid child overflow */
            width: 100%;
        }

        /* Page title */
        .page-title-block {
            margin-bottom: 40px;
            padding-bottom: 32px;
            border-bottom: 1px solid var(--border);
        }

        .page-eyebrow {
            font-family: 'Space Mono', monospace;
            font-size: 10px;
            color: var(--indigo);
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .page-eyebrow::before {
            content: '✦';
            color: var(--gold);
            font-size: 12px;
        }

        .page-title {
            font-family: 'Playfair Display', serif;
            font-size: 52px;
            font-weight: 900;
            line-height: 1.05;
            background: linear-gradient(135deg, var(--text) 0%, var(--gold) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 14px;
        }

        .page-meta {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .meta-pill {
            display: flex;
            align-items: center;
            gap: 6px;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 50px;
            padding: 5px 14px;
            font-family: 'Space Mono', monospace;
            font-size: 10px;
            color: var(--muted);
            letter-spacing: 0.5px;
        }

        .meta-pill.highlight {
            border-color: rgba(61, 217, 196, 0.3);
            color: var(--teal);
            background: rgba(61, 217, 196, 0.07);
        }

        /* Section cards */
        .tnc-section {
            margin-bottom: 32px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            overflow: hidden;
            transition: border-color 0.2s;
            scroll-margin-top: 96px;
            /* Prevent overflow */
            min-width: 0;
            width: 100%;
        }

        .tnc-section:hover {
            border-color: rgba(108, 92, 231, 0.3);
        }

        .tnc-section.active-section {
            border-color: rgba(245, 200, 66, 0.25);
        }

        .section-head {
            padding: 22px 28px;
            background: var(--surface2);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 14px;
            cursor: pointer;
            transition: background 0.2s;
            min-width: 0;
        }

        .section-head:hover {
            background: var(--surface3);
        }

        .section-num {
            font-family: 'Space Mono', monospace;
            font-size: 10px;
            color: var(--muted);
            letter-spacing: 1px;
            flex-shrink: 0;
        }

        .section-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
            flex-shrink: 0;
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 19px;
            font-weight: 700;
            flex: 1;
            min-width: 0;
            /* Allow title to shrink and wrap */
            word-break: break-word;
        }

        .section-toggle {
            font-size: 12px;
            color: var(--muted);
            transition: transform 0.25s;
            flex-shrink: 0;
        }

        .section-toggle.open {
            transform: rotate(180deg);
        }

        .section-body {
            padding: 24px 28px;
            display: block;
            min-width: 0;
            width: 100%;
        }

        .section-body.collapsed {
            display: none;
        }

        .section-intro {
            font-size: 14px;
            line-height: 1.8;
            color: rgba(240, 236, 255, 0.75);
            margin-bottom: 20px;
            word-break: break-word;
            overflow-wrap: break-word;
        }

        .section-intro strong {
            color: var(--text);
            font-weight: 600;
        }

        /* List items */
        .tnc-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            list-style: none;
            width: 100%;
            min-width: 0;
        }

        .tnc-list li {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 16px;
            background: var(--surface2);
            border: 1px solid var(--border2);
            border-radius: 12px;
            font-size: 13px;
            line-height: 1.7;
            color: rgba(240, 236, 255, 0.72);
            transition: all 0.15s;
            /* Key fix: text must not overflow the card */
            min-width: 0;
            width: 100%;
            word-break: break-word;
            overflow-wrap: break-word;
        }

        .tnc-list li:hover {
            border-color: rgba(108, 92, 231, 0.2);
            color: rgba(240, 236, 255, 0.88);
            background: var(--surface3);
        }

        /* Text span inside li must also shrink */
        .tnc-list li>span:last-child {
            flex: 1;
            min-width: 0;
            word-break: break-word;
            overflow-wrap: break-word;
        }

        .list-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--indigo);
            margin-top: 8px;
            flex-shrink: 0;
        }

        .list-dot.gold {
            background: var(--gold);
        }

        .list-dot.rose {
            background: var(--rose);
        }

        .list-dot.teal {
            background: var(--teal);
        }

        /* Sub-heading inside section */
        .sub-heading {
            font-family: 'Space Mono', monospace;
            font-size: 10px;
            color: var(--muted);
            letter-spacing: 2px;
            text-transform: uppercase;
            margin: 20px 0 10px;
        }

        /* Highlight box */
        .tnc-highlight {
            background: rgba(245, 200, 66, 0.06);
            border: 1px solid rgba(245, 200, 66, 0.2);
            border-radius: 14px;
            padding: 16px 20px;
            margin: 16px 0;
            font-size: 13px;
            line-height: 1.7;
            color: rgba(240, 236, 255, 0.8);
            word-break: break-word;
            overflow-wrap: break-word;
            width: 100%;
        }

        .tnc-highlight .hi-label {
            font-family: 'Space Mono', monospace;
            font-size: 9px;
            color: var(--gold);
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        /* Info box */
        .tnc-info {
            background: rgba(61, 217, 196, 0.06);
            border: 1px solid rgba(61, 217, 196, 0.2);
            border-radius: 14px;
            padding: 16px 20px;
            margin: 16px 0;
            font-size: 13px;
            line-height: 1.7;
            color: rgba(240, 236, 255, 0.8);
            word-break: break-word;
            overflow-wrap: break-word;
            width: 100%;
        }

        .tnc-info .hi-label {
            font-family: 'Space Mono', monospace;
            font-size: 9px;
            color: var(--teal);
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        /* Agreement bar */
        .agreement-bar {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 8px;
        }

        .agreement-text {
            flex: 1;
        }

        .agreement-title {
            font-family: 'Playfair Display', serif;
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .agreement-sub {
            font-size: 13px;
            color: var(--muted);
            line-height: 1.6;
        }

        .agreement-actions {
            display: flex;
            gap: 10px;
            flex-shrink: 0;
        }

        .btn-agree {
            background: linear-gradient(135deg, var(--gold), #f09040);
            color: #0a0612;
            border: none;
            padding: 13px 28px;
            border-radius: 14px;
            font-family: 'Space Mono', monospace;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .btn-agree:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(245, 200, 66, 0.4);
        }

        .btn-decline {
            background: none;
            border: 1px solid var(--border);
            color: var(--muted);
            padding: 13px 24px;
            border-radius: 14px;
            font-family: 'Space Mono', monospace;
            font-size: 11px;
            letter-spacing: 1px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .btn-decline:hover {
            border-color: var(--rose);
            color: var(--rose);
        }

        /* Progress indicator */
        .read-progress {
            position: fixed;
            top: 64px;
            left: 0;
            right: 0;
            height: 2px;
            z-index: 150;
            background: var(--surface2);
        }

        .read-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--indigo), var(--gold));
            transition: width 0.1s;
            width: 0%;
        }

        /* ANIMATIONS */
        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(16px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ── RESPONSIVE ── */

        /* Tablet */
        @media (max-width: 1024px) {
            .page-wrap {
                grid-template-columns: 200px 1fr;
                gap: 28px;
                padding: 36px 20px 80px;
            }
        }

        /* Mobile */
        @media (max-width: 768px) {
            nav {
                padding: 0 14px;
                gap: 10px;
                height: 56px;
            }

            .logo {
                font-size: 18px;
                flex-shrink: 0;
            }

            .nav-actions {
                display: none;
            }

            .nav-search-wrap {
                flex: 1;
                max-width: 100%;
                min-width: 0;
            }

            .btn-search {
                padding: 0 12px;
                font-size: 11px;
            }

            .read-progress {
                top: 56px;
            }

            /* Single column layout */
            .page-wrap {
                grid-template-columns: 1fr;
                padding: 16px 14px 48px;
                gap: 16px;
            }

            /* Both grid children must not overflow */
            .sidebar,
            .content-area {
                min-width: 0;
                width: 100%;
                position: static;
            }

            /* TOC becomes horizontal scrollable pills */
            .sidebar-header {
                margin-bottom: 10px;
            }

            .toc-scroll {
                display: flex;
                flex-direction: row;
                gap: 8px;
                overflow-x: auto;
                padding-bottom: 4px;
                scrollbar-width: none;
                -webkit-overflow-scrolling: touch;
                width: 100%;
            }

            .toc-scroll::-webkit-scrollbar {
                display: none;
            }

            .toc-item {
                flex-direction: row;
                white-space: nowrap;
                flex-shrink: 0;
                padding: 7px 14px;
                border-radius: 50px;
                margin-bottom: 0;
                border: 1px solid var(--border);
                background: var(--surface);
                width: auto;
                min-width: unset;
            }

            .toc-item.active {
                background: rgba(108, 92, 231, 0.15);
                border-color: rgba(108, 92, 231, 0.45);
            }

            .toc-item.active::before {
                display: none;
            }

            .toc-num {
                display: none;
            }

            .toc-label {
                font-size: 12px;
            }

            /* Page title */
            .page-title {
                font-size: 28px;
                line-height: 1.15;
            }

            .page-title-block {
                margin-bottom: 20px;
                padding-bottom: 20px;
            }

            .page-eyebrow {
                font-size: 9px;
            }

            .page-meta {
                gap: 6px;
                flex-wrap: wrap;
            }

            .meta-pill {
                font-size: 9px;
                padding: 4px 10px;
            }

            /* Section cards — ensure contained */
            .tnc-section {
                border-radius: 14px;
                margin-bottom: 16px;
            }

            .section-head {
                padding: 14px 14px;
                gap: 10px;
            }

            .section-icon {
                width: 28px;
                height: 28px;
                font-size: 14px;
                border-radius: 8px;
            }

            .section-title {
                font-size: 15px;
            }

            .section-num {
                font-size: 9px;
            }

            .section-body {
                padding: 14px;
            }

            .section-intro {
                font-size: 13px;
                line-height: 1.7;
            }

            /* List items */
            .tnc-list {
                gap: 8px;
            }

            .tnc-list li {
                padding: 10px 12px;
                font-size: 12px;
                line-height: 1.65;
                border-radius: 10px;
            }

            /* Highlight/info boxes */
            .tnc-highlight,
            .tnc-info {
                padding: 12px 14px;
                font-size: 12px;
                border-radius: 10px;
            }

            /* Agreement bar */
            .agreement-bar {
                flex-direction: column;
                padding: 18px 14px;
                border-radius: 14px;
                gap: 14px;
            }

            .agreement-title {
                font-size: 17px;
            }

            .agreement-sub {
                font-size: 12px;
            }

            .agreement-actions {
                width: 100%;
                flex-direction: column;
                gap: 8px;
            }

            .btn-agree,
            .btn-decline {
                width: 100%;
                padding: 14px;
                text-align: center;
                border-radius: 12px;
            }
        }

        @media (max-width: 380px) {
            nav {
                padding: 0 10px;
            }

            .page-wrap {
                padding: 12px 10px 40px;
            }

            .section-head {
                padding: 12px 10px;
                gap: 8px;
            }

            .section-body {
                padding: 12px 10px;
            }

            .page-title {
                font-size: 24px;
            }
        }

        ::-webkit-scrollbar {
            width: 5px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--surface2);
            border-radius: 4px;
        }
    </style>
    <div class="" style="height: 150px; "></div>
    {{-- <div class="row">
        <div class="col">
            <div class="container">
                <h3>Term & Condition</h3>
                <nav>
                    <div class="nav nav-tabs" id="nav-tab" role="tablist">
                        @foreach ($term as $terms)
                            <button class="nav-link {{ $loop->first ? 'active' : '' }}" id="nav-{{ $terms->uid }}-tab"
                                data-bs-toggle="tab" data-bs-target="#nav-{{ $terms->uid }}" type="button" role="tab"
                                aria-controls="nav-{{ $terms->uid }}" aria-selected="true">{{ $terms->title }}</button>
                        @endforeach


                    </div>
                </nav>
                <div class="tab-content pt-5" id="nav-tabContent">
                    @foreach ($term as $item)
                        <div class="tab-pane fade{{ $loop->first ? ' show active' : '' }}" id="nav-{{ $item->uid }}"
                            role="tabpanel" aria-labelledby="nav-{{ $item->uid }}-tab">
                            {!! $item->term !!}
                        </div>
                    @endforeach


                </div>
            </div>
        </div>
    </div> --}}

    <div class="page-wrap">

        <!-- SIDEBAR TOC -->
        <aside class="sidebar">
            <div class="sidebar-header">Daftar Isi</div>
            <div class="toc-scroll" id="tocList">
                @foreach ($term as $index => $item)
                    <button class="toc-item {{ $loop->first ? 'active' : '' }}"
                        onclick="scrollTo('sec-{{ $loop->iteration }}')" id="toc-{{ $loop->iteration }}">

                        <span class="toc-num">
                            {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}
                        </span>

                        <span class="toc-label">{{ $item->title }}</span>
                    </button>
                @endforeach
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="content-area">

            <!-- TITLE -->
            <div class="page-title-block">
                <div class="page-eyebrow">Legal · tGoTik.com</div>
                <h1 class="page-title">Syarat &amp;<br>Ketentuan</h1>
                <div class="page-meta">
                    <div class="meta-pill highlight">● Berlaku Aktif</div>
                    <div class="meta-pill">📅 Diperbarui: {{ now()->format('d M Y') }}</div>
                    <div class="meta-pill">🇮🇩 Bahasa Indonesia</div>
                </div>
            </div>

            <!-- INTRO -->
            <div class="tnc-highlight" style="margin-bottom:28px;">
                <div class="hi-label">⚠ Penting</div>
                Syarat dan Ketentuan ini merupakan dasar penggunaan layanan. Dengan menggunakan platform ini, pengguna
                dianggap telah menyetujui seluruh isi ketentuan yang berlaku.
            </div>

            <!-- LOOP SECTION -->
            @foreach ($term as $item)
                <div class="tnc-section" id="sec-{{ $loop->iteration }}">

                    <div class="section-head"
                        onclick="toggleSection('body-{{ $loop->iteration }}','toggle-{{ $loop->iteration }}')">

                        <span class="section-num">
                            {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}
                        </span>

                        <div class="section-icon" style="background:rgba(108,92,231,0.12);">📄</div>

                        <span class="section-title">{{ $item->title }}</span>

                        <span class="section-toggle open" id="toggle-{{ $loop->iteration }}">▲</span>
                    </div>

                    <div class="section-body" id="body-{{ $loop->iteration }}">
                        {!! $item->term !!}
                    </div>

                </div>
            @endforeach

            <!-- AGREEMENT BAR -->
            <div class="agreement-bar">
                <div class="agreement-text">
                    <div class="agreement-title">Setuju dengan Syarat & Ketentuan?</div>
                    <div class="agreement-sub">
                        Dengan mengklik "Saya Setuju", Anda menyatakan telah membaca, memahami, dan menyetujui seluruh
                        Syarat & Ketentuan yang berlaku.
                    </div>
                </div>
                <div class="agreement-actions">
                    <button class="btn-decline" onclick="decline()">Tolak</button>
                    <button class="btn-agree" onclick="agree()">✓ Saya Setuju</button>
                </div>
            </div>

        </main>
    </div>

    <script>
        // ===== TOGGLE SECTION =====
        function toggleSection(bodyId, toggleId) {
            const body = document.getElementById(bodyId);
            const toggle = document.getElementById(toggleId);

            const isOpen = toggle.classList.contains('open');

            toggle.classList.toggle('open', !isOpen);
            body.style.display = isOpen ? 'none' : 'block';
        }

        // ===== SCROLL TO =====
        function scrollTo(id) {
            const el = document.getElementById(id);

            if (el) {
                window.scrollTo({
                    top: el.offsetTop - 100,
                    behavior: 'smooth'
                });
            }

            // set active langsung saat klik
            document.querySelectorAll('.toc-item').forEach(btn => btn.classList.remove('active'));
            const activeBtn = document.querySelector(`[onclick="scrollTo('${id}')"]`);
            if (activeBtn) activeBtn.classList.add('active');
        }

        // ===== AUTO DETECT SECTION (DINAMIS) =====
        function getSections() {
            return Array.from(document.querySelectorAll('.tnc-section')).map(sec => sec.id);
        }

        function getTocs() {
            return Array.from(document.querySelectorAll('.toc-item')).map(btn => btn.id);
        }

        // ===== SCROLL SPY =====
        function onScroll() {
            const sections = getSections();
            const tocs = getTocs();

            let current = 0;

            sections.forEach((id, i) => {
                const el = document.getElementById(id);
                if (el && el.getBoundingClientRect().top < 150) {
                    current = i;
                }
            });

            tocs.forEach((id, i) => {
                const el = document.getElementById(id);
                if (el) {
                    el.classList.toggle('active', i === current);
                }
            });
        }

        window.addEventListener('scroll', onScroll, {
            passive: true
        });

        // ===== INIT =====
        document.addEventListener('DOMContentLoaded', function() {
            const firstSection = document.querySelector('.tnc-section');
            const firstToc = document.querySelector('.toc-item');

            if (firstSection && firstToc) {
                firstToc.classList.add('active');
            }
        });

        // ===== AGREEMENT =====
        function agree() {
            const btn = document.querySelector('.btn-agree');
            btn.textContent = '✓ Disetujui!';
            btn.style.background = 'linear-gradient(135deg, #2ecc71, #27ae60)';

            setTimeout(() => {
                alert('Terima kasih! Anda telah menyetujui Syarat & Ketentuan tGoTik.');
            }, 200);
        }

        function decline() {
            if (confirm(
                    'Apakah Anda yakin ingin menolak Syarat & Ketentuan?\nAnda tidak dapat menggunakan layanan tGoTik tanpa menyetujuinya.'
                )) {
                alert('Anda telah menolak Syarat & Ketentuan. Beberapa fitur mungkin tidak tersedia.');
            }
        }
    </script>
@endsection
