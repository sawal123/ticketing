


<header class="site-header site-header--menu-right fugu--header-section fugu--header-three" id="sticky-menu">
    <div class="container-fluid">
        <nav class="navbar site-navbar">
            <!-- Brand Logo-->
            <div class="brand-logo">
                <a href="{{ url('/') }}">
                    <img src="{{ asset('storage/logo/' . $logo[0]->logo) }}" style="width: 100%" alt=""
                        class="light-version-logo">

                </a>
            </div>
            <div class="menu-block-wrapper">
                <div class="menu-overlay"></div>
                <nav class="menu-block" id="append-menu-header">
                    <div class="mobile-menu-head">
                        <div class="go-back">
                            <i class="fa fa-angle-left"></i>
                        </div>
                        <div class="current-menu-title"></div>
                        <div class="mobile-menu-close">&times;</div>
                    </div>
                    <ul class="site-menu-main">
                        <li class="nav-item nav-item-has-children p-2">
                            <div class="fugu--newsletter fugu--search " data-wow-delay=".30s">
                                <form action="{{ url('/cari') }}" method="GET">
                                    <input type="text" placeholder="Search..." name="cari"
                                        value="{{ old('cari') }}">
                                    <button type="submit" id="fugu--submit-btn">Search</button>
                                    <button type="submit" id="fugu--search-btn"><img
                                            src="{{ asset('landing/images/svg2/search.svg') }}" alt=""></button>
                                </form>
                            </div>
                        </li>
                        <style>
                            .fa-cart-plus {
                                transform: rotate(0deg) !important;
                            }

                            .trans {
                                color: #888888 !important;
                            }

                            .trans:hover {
                                color: #fff !important;
                            }
                        </style>
                        @if ($user)
                            <li class="nav-link nav-item-has-children">
                                <a href="{{ url('/transaksi') }}" class="nav-link-item drop-trigger trans">Transaksi <i
                                        class="fas fa-cart-plus"></i></a>
                            </li>
                            <li class="nav-item nav-link nav-item-has-children">
                                <a href="#" class="nav-link-item drop-trigger">{{ $user->name }} <i
                                        class="fas fa-angle-down"></i></a>
                                <ul class="sub-menu" id="submenu-1">
                                    @if (Auth::user()->role === 'penyewa')
                                        <li class="sub-menu--item">
                                            <a href="{{ url('/dashboard') }}">Dashboard</a>
                                        </li>
                                    @endif
                                    @if (Auth::user()->role === 'admin')
                                        <li class="sub-menu--item">
                                            <a href="{{ url('/admin') }}">Dashboard</a>
                                        </li>
                                    @endif
                                    <li class="sub-menu--item">
                                        <a href="{{ url('/profile') }}">Profile</a>
                                    </li>
                                    <li class="sub-menu--item">
                                        <a href="{{ url('/logout') }}">Log Out</a>
                                    </li>
                                </ul>
                            </li>
                        @else
                            <li class="fugu-responsive-btn">
                                <div class="row mt-2">
                                    <div class="col">
                                        <a class="btn btn-secondary" href="{{ url('/login') }}">
                                            Sign In
                                        </a>
                                    </div>
                                    <div class="col">
                                        <a class="btn btn-primary" href="{{ url('/register') }}">
                                            Sign Up
                                        </a>
                                    </div>
                                </div>
                            </li>
                        @endif
                    </ul>
                </nav>

        <!-- NAV -->
        {{-- <nav>
            <div class="brand-logo">
                <a href="{{ url('/') }}">
                    <img src="{{ asset('storage/logo/' . $logo[0]->logo) }}" style="width: 100%" alt=""
                        class="light-version-logo">

                </a>
            </div>
            <form action="{{ url('/cari') }}" method="GET" class="nav-search-wrap">
                <span class="nav-search-icon">⌕</span>
                <input type="text" placeholder="Cari event, konser, festival…" name="cari"
                    value="{{ old('cari') }}">
                <button type="submit" class="btn-search">Search</button>
            </form>

            <div class="nav-right">
                @if ($user)
                    <a href="{{ url('/transaksi') }}" class="nav-transaksi-pill">🛒 Transaksi</a>

                    <div class="nav-avatar-wrap">
                        <div class="nav-avatar-btn" id="avatarBtn" onclick="toggleDropdown()">
                            <div class="nav-avatar">P</div>
                            <span class="nav-username">{{ $user->name }}</span>
                            <span class="nav-chevron">▾</span>
                        </div>
                        <div class="user-dropdown" id="userDropdown">

                            <div class="dropdown-user-info">
                                <div class="dropdown-name">{{ $user->name }}</div>
                                <div class="dropdown-email">{{ $user->email }}</div>
                            </div>

                            @if (Auth::user()->role === 'penyewa')
                                <div class="dropdown-item">
                                    <a href="{{ url('/dashboard') }} class="di-icon"
                                        style="background:rgba(108,92,231,0.12);">👤Dashboard </a>
                                </div>
                            @endif
                            @if (Auth::user()->role === 'admin')
                                <div class="dropdown-item">
                                    <a href="{{ url('/admin') }} class="di-icon"
                                        style="background:rgba(108,92,231,0.12);">👤Dashboard </a>
                                </div>
                            @endif
                            <div class="dropdown-item">
                                <a href="{{ url('/transaksi') }}" class="di-icon"
                                    style="background:rgba(61,217,196,0.12);">🛒</a>Transaksi
                            </div>
                            <div class="dropdown-item">
                                <a href="{{ url('/profile') }}" class="di-icon"
                                    style="background:rgba(245,200,66,0.12);">👤</a>Profil Saya
                            </div>
                            <div class="dropdown-item">
                                <div class="di-icon" style="background:rgba(245,200,66,0.12);">🎫</div>Tiket Saya
                            </div>
                            <button class="dropdown-item">
                                <div class="di-icon" style="background:rgba(139,132,168,0.12);">⚙️</div>Pengaturan
                            </button>
                            <div class="dropdown-sep"></div>
                            <div class="dropdown-item logout">
                                <a href="{{ url('/logout') }}" class="di-icon"
                                    style="background:rgba(232,84,122,0.12);">🚪</a>Keluar
                            </div>
                        </div>
                    </div>
                @else
                    <a href="{{ url('/login') }}" class="btn-masuk">
                        <svg viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.7" />
                            <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="1.7"
                                stroke-linecap="round" />
                        </svg>
                        Sign In
                    </a>
                    <a href="{{ url('/register') }}" class="btn-masuk">
                        <svg viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.7" />
                            <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="1.7"
                                stroke-linecap="round" />
                        </svg>
                        Sign Up
                    </a>
                @endif

            </div>
        </nav> --}}
    </div>
    @if (!$user)
        <a href="{{ url('/login') }}" class="btn-masuk">
            <svg viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.7" />
                <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
            </svg>
            Sign In
        </a>
        <a href="{{ url('/register') }}" class="btn-masuk">
            <svg viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.7" />
                <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="1.7"
                    stroke-linecap="round" />
            </svg>
            Sign Up
        </a>
    @endif



    <!-- mobile menu trigger -->
    <div class="mobile-menu-trigger">
        <span></span>
    </div>
    <!--/.Mobile Menu Hamburger Ends-->
    </nav>
    </div>
</header>
