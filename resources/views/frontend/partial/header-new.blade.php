<header class="gap-4">

    <a href="{{ url('/') }}" class="logo pr-5">
        <img src="{{ asset('storage/logo/' . $logo[0]->logo) }}" style="width: 100px" alt="" class="">
    </a>

    @if ($user)
   

        <div class="nav-search-wrap">
            <span class="nav-search-icon">⌕</span>

            <form action="{{ url('/cari') }}" method="GET" style="display:flex; align-items:center; flex:1; ">
                <input type="text" placeholder="Cari event, konser, festival…" name="cari"
                    value="{{ old('cari') }}">

                <button type="submit" class="btn-search">Search</button>
            </form>


        </div>

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
        <div class="nav-search-wrap">
            <span class="nav-search-icon">⌕</span>
            <form action="{{ url('/cari') }}" method="GET" style="display:flex; align-items:center; flex:1;">
                <input type="text" placeholder="Cari event, konser, festival…" name="cari"
                    value="{{ old('cari') }}">

                <button type="submit" class="btn-search">Search</button>
            </form>
        </div>
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
