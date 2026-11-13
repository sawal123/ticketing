<header class="gap-4">

    <a href="{{ url('/') }}" class="logo pr-5">
        <img src="{{ asset('storage/logo/' . $logo[0]->logo) }}" style="width: 100px" alt="" class="">
    </a>

    @if ($user)
   

        <button type="button" class="nav-search-trigger" onclick="openSearchModal()" aria-label="Buka pencarian (Ctrl + K)" title="Buka pencarian (Ctrl + K)">
            <span class="nav-search-icon">⌕</span>
            <span class="nav-search-placeholder">Cari event, konser, festival…</span>
            <kbd class="nav-search-kbd">
                <span class="kbd-label">Ctrl K</span>
            </kbd>
        </button>

        <div class="nav-right">
            <button type="button" class="theme-toggle-btn" onclick="toggleGotikTheme()" aria-label="Ganti Tema" title="Ganti Tema">
                <svg class="theme-icon-dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                </svg>
                <svg class="theme-icon-light" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="5"></circle>
                    <line x1="12" y1="1" x2="12" y2="3"></line>
                    <line x1="12" y1="21" x2="12" y2="23"></line>
                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                    <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                    <line x1="1" y1="12" x2="3" y2="12"></line>
                    <line x1="21" y1="12" x2="23" y2="12"></line>
                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                    <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                </svg>
            </button>
            <div class="nav-avatar-wrap">
                <div class="nav-avatar-btn" id="avatarBtn" onclick="toggleDropdown()">
                    <div class="nav-avatar"> {{ strtoupper(substr($user->name, 0, 1)) }}</div>
                    <span class="nav-username">{{ $user->name }}</span>
                    <span class="nav-chevron">▾</span>
                </div>

                <div class="user-dropdown" id="userDropdown">
                    <div class="dropdown-user-info">
                        <div class="dropdown-name">{{ $user->name }}</div>
                        <div class="dropdown-email">{{ $user->email }}</div>
                    </div>
                    @if (Auth::user()->role === 'penyewa')
                        <a class="dropdown-item" href="{{ url('/dashboard') }}">
                            <div class="di-icon" style="background:rgba(108,92,231,0.12);">👤</div>Dashboard
                        </a>
                    @endif
                    @if (Auth::user()->role === 'admin')
                        <a class="dropdown-item" href="{{ url('/admin') }}">
                            <div class="di-icon" style="background:rgba(108,92,231,0.12);">👤</div>Dashboard
                        </a>
                    @endif
                    <a href="{{ url('/profile') }}" class="dropdown-item">
                        <div class="di-icon" style="background:rgba(108,92,231,0.12);">👤</div>Profil Saya
                    </a>
                    <a href="{{ url('/transaksi') }}" class="dropdown-item">
                        <div class="di-icon" style="background:rgba(61,217,196,0.12);">🛒</div>Transaksi
                    </a>
                    {{-- <a href="#"  class="dropdown-item text-white ">
                        <div class="di-icon " style="background:rgba(245,200,66,0.12);">🎫</div>Tiket Saya
                    </a> --}}
                    {{-- <a href=""  class="dropdown-item  text-white">
                        <div class="di-icon " style="background:rgba(139,132,168,0.12);">⚙️</div>Pengaturan
                    </a> --}}

                    <div class="dropdown-sep"></div>
                    <form action="{{ route('logout') }}" method="post" style="margin:0;">
                        @csrf
                        <button type="submit" class="dropdown-item logout" style="border:0;background:transparent;width:100%;text-align:left;">
                            <span class="di-icon" style="background:rgba(232,84,122,0.12);">🚪</span>
                            Keluar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @else
        <button type="button" class="nav-search-trigger" onclick="openSearchModal()" aria-label="Buka pencarian (Ctrl + K)" title="Buka pencarian (Ctrl + K)">
            <span class="nav-search-icon">⌕</span>
            <span class="nav-search-placeholder">Cari event, konser, festival…</span>
            <kbd class="nav-search-kbd">
                <span class="kbd-label">Ctrl K</span>
            </kbd>
        </button>
        <div class="nav-right" style="display:flex; align-items:center; gap:10px;">
            <button type="button" class="theme-toggle-btn" onclick="toggleGotikTheme()" aria-label="Ganti Tema" title="Ganti Tema">
                <svg class="theme-icon-dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                </svg>
                <svg class="theme-icon-light" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="5"></circle>
                    <line x1="12" y1="1" x2="12" y2="3"></line>
                    <line x1="12" y1="21" x2="12" y2="23"></line>
                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                    <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                    <line x1="1" y1="12" x2="3" y2="12"></line>
                    <line x1="21" y1="12" x2="23" y2="12"></line>
                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                    <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                </svg>
            </button>
            <a href="{{ url('/login') }}" class="btn-masuk">
                <svg viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.7" />
                    <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="1.7"
                        stroke-linecap="round" />
                </svg>
                <span class="btn-text">Sign In</span>
            </a>
        </div>
    @endif

</header>

<!-- SEARCH MODAL -->
<div id="searchModal" class="search-modal" aria-hidden="true" style="display:none;">
    <div class="search-modal-backdrop" onclick="closeSearchModal()"></div>
    <div class="search-modal-container" role="dialog" aria-modal="true" aria-labelledby="searchModalTitle">
        <div class="search-modal-header">
            <span class="search-modal-icon">⌕</span>
            <form action="{{ url('/cari') }}" method="GET" class="search-modal-form" id="searchModalForm">
                <input type="text" name="cari" id="searchModalInput" class="search-modal-input"
                    placeholder="Cari event, konser, festival, atau kota…" autocomplete="off">
            </form>
            <button type="button" class="search-modal-close" onclick="closeSearchModal()" aria-label="Tutup pencarian">
                <span class="search-modal-close-icon">✕</span>
                <kbd class="search-modal-close-kbd">ESC</kbd>
            </button>
        </div>

        <div class="search-modal-body">
            <div class="search-modal-label">Pencarian Populer</div>
            <div class="search-modal-tags">
                <a href="{{ url('/cari?cari=konser') }}" class="search-tag">
                    <span class="tag-icon">🎵</span> Konser Musik
                </a>
                <a href="{{ url('/cari?cari=festival') }}" class="search-tag">
                    <span class="tag-icon">🎪</span> Festival
                </a>
                <a href="{{ url('/cari?cari=wisata') }}" class="search-tag">
                    <span class="tag-icon">🏖️</span> Wisata
                </a>
                <a href="{{ url('/cari?cari=medan') }}" class="search-tag">
                    <span class="tag-icon">📍</span> Medan
                </a>
                <a href="{{ url('/search') }}" class="search-tag featured">
                    <span class="tag-icon">✨</span> Semua Event
                </a>
            </div>
        </div>

        <div class="search-modal-footer">
            <div class="search-modal-hint">
                <kbd>↵ Enter</kbd> untuk mencari
            </div>
            <div class="search-modal-hint">
                <kbd>Esc</kbd> untuk keluar
            </div>
        </div>
    </div>
</div>

<style>
/* Critical defensive styling for header search trigger, modal, & controls */
.search-modal:not(.is-open) {
    display: none !important;
}
.nav-search-trigger {
    flex: 1;
    max-width: 480px;
    display: inline-flex;
    align-items: center;
    background: var(--surface2, #1a1530);
    border: 1px solid var(--border, rgba(108, 92, 231, 0.2));
    border-radius: 50px;
    padding: 0 10px 0 16px;
    height: 42px;
    color: var(--text, #f0ecff);
    cursor: pointer;
    box-sizing: border-box;
    font-family: inherit;
    text-align: left;
    transition: all 0.2s ease;
}
.nav-search-trigger:focus,
.nav-search-trigger:hover {
    border-color: rgba(108, 92, 231, 0.45);
}
.nav-search-icon {
    font-size: 17px;
    color: var(--muted, #8b84a8);
    margin-right: 10px;
    line-height: 1;
    flex-shrink: 0;
}
.nav-search-placeholder {
    flex: 1;
    font-size: 13.5px;
    color: var(--muted, #8b84a8);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.nav-search-kbd {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: var(--surface3, #221d3a);
    border: 1px solid var(--border, rgba(108, 92, 231, 0.2));
    border-radius: 8px;
    padding: 3px 8px;
    font-size: 11px;
    font-weight: 600;
    color: var(--muted, #8b84a8);
    flex-shrink: 0;
    line-height: 1;
}
.theme-toggle-btn {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    border: 1px solid var(--border, rgba(108, 92, 231, 0.2));
    background: var(--surface2, #1a1530);
    color: var(--text, #f0ecff);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-sizing: border-box;
    padding: 0;
    flex-shrink: 0;
}
.theme-toggle-btn svg {
    width: 18px;
    height: 18px;
}
[data-theme="light"] .nav-search-trigger {
    background: #ffffff;
    border-color: rgba(108, 92, 231, 0.16);
    color: #171324;
}
[data-theme="light"] .nav-search-kbd {
    background: #f1f3f9;
    border-color: rgba(0, 0, 0, 0.08);
    color: #64748b;
}
[data-theme="light"] .theme-toggle-btn {
    background: #ffffff;
    border-color: rgba(108, 92, 231, 0.16);
    color: #171324;
}
@media (max-width: 768px) {
    .nav-search-kbd {
        display: none !important;
    }
}
</style>

<script>
    function openSearchModal(e) {
        if (e && typeof e.stopPropagation === 'function') {
            e.stopPropagation();
        }
        const modal = document.getElementById('searchModal');
        if (!modal) return;
        modal.style.display = 'flex';
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        const input = document.getElementById('searchModalInput');
        if (input) {
            setTimeout(function() {
                input.focus();
                input.select();
            }, 60);
        }
    }

    function closeSearchModal() {
        const modal = document.getElementById('searchModal');
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }

    function handleNavSearchClick(e) {
        if (window.innerWidth <= 768) {
            if (e && typeof e.preventDefault === 'function') {
                e.preventDefault();
            }
            openSearchModal(e);
        }
    }

    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
            e.preventDefault();
            const modal = document.getElementById('searchModal');
            if (modal && modal.classList.contains('is-open')) {
                closeSearchModal();
            } else {
                openSearchModal(e);
            }
        } else if (e.key === 'Escape') {
            const modal = document.getElementById('searchModal');
            if (modal && modal.classList.contains('is-open')) {
                closeSearchModal();
            }
        }
    });
</script>
