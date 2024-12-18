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
            </div>
            @if (!$user)
                <div class="header-btn header-btn-l1 ms-auto d-none d-xs-inline-flex ">
                    <a class="fugu--btn btn-secondary mx-3 w-100" href="{{ url('/login') }}">
                        Sign In
                    </a>
                    <a class="fugu--btn btn-primary" href="{{ url('/register') }}">
                        Sign Up
                    </a>
                </div>
            @endif



            <!-- mobile menu trigger -->
            <div class="mobile-menu-trigger">
                <span></span>
            </div>
            <!--/.Mobile Menu Hamburger Ends-->
        </nav>
    </div>
</header>
