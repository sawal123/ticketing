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
@include('marketing-guide._styles')
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
            @foreach ($sections->groupBy('nav_group') as $navGroup => $groupSections)
                <div class="nav-section">
                    <div class="nav-section-title">{{ $navGroup }}</div>
                    @foreach ($groupSections as $navSection)
                        <a href="#{{ $navSection->slug }}" class="nav-link" data-id="{{ $navSection->slug }}">{{ $navSection->title }}</a>
                    @endforeach
                </div>
            @endforeach
        </div>
    </aside>

    <!-- Drawer (Mobile) -->
    <div class="drawer" id="drawer">
        <div class="drawer-content">
            @foreach ($sections->groupBy('nav_group') as $navGroup => $groupSections)
                <div class="nav-section">
                    <div class="nav-section-title">{{ $navGroup }}</div>
                    @foreach ($groupSections as $navSection)
                        <a href="#{{ $navSection->slug }}" class="nav-link" data-id="{{ $navSection->slug }}">{{ $navSection->title }}</a>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>

    <!-- Main Content -->
    <main class="main-content">
        @foreach ($sections as $section)
            @php
                $isHero = $section->activeBlocks->contains(
                    fn ($b) => $b->type === 'text' && ! empty(data_get($b->data, 'badge'))
                );
                $isCta = $section->activeBlocks->contains(fn ($b) => $b->type === 'cta');
            @endphp
            <section id="{{ $section->slug }}" @class(['cta-section' => $isCta])>
                @if (! $isHero && ! $isCta)
                    <h2>{{ $section->title }}</h2>
                @endif
                @foreach ($section->activeBlocks as $block)
                    @include('marketing-guide._block', ['block' => $block])
                @endforeach
            </section>
        @endforeach
    </main>
<script>
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
        updateActiveLink();
        updateProgressBar();
    </script>
</body>
</html>