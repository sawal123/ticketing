<div class="sticky">
    <div class="app-sidebar__overlay" data-bs-toggle="sidebar"></div>
    <div class="app-sidebar">
        <div class="side-header">
            <a class="header-brand1" href="index.html">
                <img src="{{ asset('storage/logo/' . $logo[0]->logo) }}" height="60"
                    class="header-brand-img desktop-logo" alt="logo">
                <img src="{{ asset('storage/logo/' . $logo[0]->logo) }}" height="60"
                    class="header-brand-img toggle-logo" alt="logo">
                <img src="{{ asset('storage/logo/' . $logo[0]->logo) }}" height="60"
                    class="header-brand-img light-logo" alt="logo">
                <img src="{{ asset('storage/logo/' . $logo[0]->logo) }}" height="60"
                    class="header-brand-img light-logo1" alt="logo">
            </a>
            <!-- LOGO -->
        </div>
        <div class="main-sidemenu">
            <div class="slide-left disabled" id="slide-left"><svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191"
                    width="24" height="24" viewBox="0 0 24 24">
                    <path d="M13.293 6.293 7.586 12l5.707 5.707 1.414-1.414L10.414 12l4.293-4.293z" />
                </svg></div>
            <ul class="side-menu">
                <li class="sub-category">
                    <h3>Menu</h3>
                </li>
                <li class="slide ">
                    <a class="side-menu__item has-link {{ request()->is('dashboard') ? 'active' : '' }}"
                        data-bs-toggle="slide" href="{{ url('/dashboard') }}"><i
                            class="side-menu__icon fe fe-home"></i><span class="side-menu__label">Dashboard</span></a>
                </li>
                <li class="slide ">
                    <a class="side-menu__item has-link {{ request()->is('dashboard/event') ? 'active' : '' }}"
                        data-bs-toggle="slide" href="{{ url('/dashboard/event') }}"><i
                            class="side-menu__icon fe fe-eye"></i><span class="side-menu__label">Event</span></a>
                </li>






                <li class="slide ">
                    <a class="side-menu__item has-link {{ request()->is('dashboard/voucher') ? 'active' : '' }}"
                        data-bs-toggle="slide" href="{{ url('/dashboard/voucher') }}"><i
                            class=" side-menu__icon fa fa-gift"></i><span class="side-menu__label">Voucher</span></a>
                </li>
                <li class="slide ">
                    <a class="side-menu__item has-link {{ request()->is('dashboard/partner') ? 'active' : '' }}"
                        data-bs-toggle="slide" href="{{ url('/dashboard/partner') }}"><i
                            class=" side-menu__icon fa fa-users"></i><span class="side-menu__label">Partner</span></a>
                </li>
                <hr>
                <li class="slide ">
                    <a class="side-menu__item has-link {{ request()->is('dashboard/money') ? 'active' : '' }}"
                        data-bs-toggle="slide" href="{{ url('/dashboard/money') }}"><i
                            class=" side-menu__icon fa fa-money"></i><span class="side-menu__label">Money</span></a>
                </li>
                <li class="slide ">
                    <a class="side-menu__item has-link {{ request()->is('dashboard/staff') ? 'active' : '' }}"
                        data-bs-toggle="slide" href="{{ url('/dashboard/staff') }}"><i
                            class=" side-menu__icon fa fa-users"></i><span class="side-menu__label">Staff</span></a>
                </li>

                <li class="slide {{ request()->is('dashboard/profile') ? 'active' : '' }}">
                    <a class="side-menu__item has-link" data-bs-toggle="slide"
                        href="{{ url('/dashboard/profile') }}"><i class=" side-menu__icon fa fa-user"></i><span
                            class="side-menu__label">Profile</span></a>
                </li>
                <li class="slide {{ request()->is('dashboard/user') ? 'active' : '' }}">
                    <a class="side-menu__item has-link" data-bs-toggle="slide" href="{{ url('/') }}"><i
                            class=" side-menu__icon fa fa-google"></i><span class="side-menu__label">Halaman
                            Utama</span></a>
                </li>
                <li class="slide {{ request()->is('dashboard/user') ? 'active' : '' }}">
                    <a class="side-menu__item has-link" data-bs-toggle="slide" href="{{ url('/out') }}"><i
                            class=" side-menu__icon fa fa-power-off"></i><span class="side-menu__label">Log
                            Out</span></a>
                </li>
            </ul>
            <div class="slide-right" id="slide-right"><svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191"
                    width="24" height="24" viewBox="0 0 24 24">
                    <path d="M10.707 17.707 16.414 12l-5.707-5.707-1.414 1.414L13.586 12l-4.293 4.293z" />
                </svg></div>
        </div>
    </div>
</div>
