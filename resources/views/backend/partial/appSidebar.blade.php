<div class="sticky">
    <div class="app-sidebar__overlay" data-bs-toggle="sidebar"></div>
    <div class="app-sidebar">
        <div class="side-header">
            <a class="header-brand1" href="#">
                <img src="{{ asset('storage/logo/' . $logo[0]->logo) }}" height="60"  class="header-brand-img desktop-logo"
                    alt="logo">
                <img src="{{ asset('storage/logo/' . $logo[0]->logo) }}" height="60"  class="header-brand-img toggle-logo"
                    alt="logo">
                <img src="{{ asset('storage/logo/' . $logo[0]->logo) }}" height="60"  class="header-brand-img light-logo"
                    alt="logo">
                <img src="{{ asset('storage/logo/' . $logo[0]->logo) }}" height="60"  class="header-brand-img light-logo1"
                    alt="logo">
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
                    <a class="side-menu__item has-link {{request()->is('admin') ? 'active': ''}}" data-bs-toggle="slide" href="{{ url('/admin') }}"><i
                            class="side-menu__icon fe fe-home"></i><span class="side-menu__label">Admin Dashboard</span></a>
                </li>
                <li class="slide ">
                    <a class="side-menu__item has-link {{request()->is('admin/event') ? 'active': ''}}" data-bs-toggle="slide" href="{{ url('/admin/event') }}"><i
                            class="side-menu__icon fe fe-eye"></i><span class="side-menu__label">Event</span></a>
                </li>
                
                <li class="slide ">
                    <a class="side-menu__item has-link {{request()->is('admin/transaksi') ? 'active': ''}}" data-bs-toggle="slide" href="{{ url('/admin/transaksi') }}"><i
                            class=" side-menu__icon fa fa-database"></i><span class="side-menu__label">Transaksi</span></a>
                </li>
                <li class="slide ">
                    <a class="side-menu__item has-link {{request()->is('admin/penarikan') ? 'active': ''}}" data-bs-toggle="slide" href="{{ url('/admin/penarikan') }}"><i
                            class=" side-menu__icon fa fa-paper-plane"></i><span class="side-menu__label">Penarikan</span></a>
                </li>

                <hr>

                <li class="slide ">
                    <a class="side-menu__item {{request()->is('admin/setting*') ? 'active': ''}}" data-bs-toggle="slide" href="javascript:void(0)"><i
                            class="side-menu__icon fa fa-cog"></i><span
                            class="side-menu__label">Setting</span><i
                            class="angle fe fe-chevron-right"></i>
                    </a>
                    <ul class="slide-menu">
                        <li class="panel sidetab-menu">
                            <div class="tab-menu-heading p-0 pb-2 border-0">
                                <div class="tabs-menu ">
                                    <!-- Tabs -->
                                    <ul class="nav panel-tabs">
                                        <li><a href="#side13" class="d-flex active" data-bs-toggle="tab"><i class="fe fe-monitor me-2"></i><p>Home</p></a></li>
                                        <li><a href="#side14" data-bs-toggle="tab" class="d-flex"><i class="fe fe-message-square me-2"></i><p>Chat</p></a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="panel-body tabs-menu-body p-0 border-0">
                                <div class="tab-content">
                                    <div class="tab-pane active" id="side13">
                                        <ul class="sidemenu-list">
                                            <li class="side-menu-label1"><a href="javascript:void(0)">Setting</a></li>
                                            <li><a href="{{ url('/admin/setting/slide') }}" class="slide-item {{request()->is('admin/setting/slide') ? 'active': ''}}"><span class="side-menu__label">Slide</span></a></li>
                                            <li><a href="{{ url('admin/setting/seo') }}"" class="slide-item {{request()->is('admin/setting/seo') ? 'active': ''}}">SEO</a></li>
                                            <li><a href="{{ url('admin/setting/term') }}"" class="slide-item {{request()->is('admin/setting/term') ? 'active': ''}}">Term and Condition</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>
                </li>

              
                
                <li class="slide ">
                    <a class="side-menu__item {{request()->is('admin/user*') ? 'active': ''}}" data-bs-toggle="slide" href="javascript:void(0)"><i
                            class="side-menu__icon fe fe-users "></i><span
                            class="side-menu__label">User</span><i
                            class="angle fe fe-chevron-right"></i>
                    </a>
                    <ul class="slide-menu">
                        <li class="panel sidetab-menu">
                            <div class="tab-menu-heading p-0 pb-2 border-0">
                                <div class="tabs-menu ">
                                    <!-- Tabs -->
                                    <ul class="nav panel-tabs">
                                        <li><a href="#side13" class="d-flex active" data-bs-toggle="tab"><i class="fe fe-monitor me-2"></i><p>Home</p></a></li>
                                        <li><a href="#side14" data-bs-toggle="tab" class="d-flex"><i class="fe fe-message-square me-2"></i><p>Chat</p></a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="panel-body tabs-menu-body p-0 border-0">
                                <div class="tab-content">
                                    <div class="tab-pane active" id="side13">
                                        <ul class="sidemenu-list">
                                            <li class="side-menu-label1"><a href="javascript:void(0)">User</a></li>
                                            <li><a href="{{ url('/admin/user') }}" class="slide-item {{request()->is('admin/user') ? 'active': ''}}"> Role User</a></li>
                                            <li><a href="{{ url('/admin/user/admin') }}"" class="slide-item {{request()->is('admin/user/admin') ? 'active': ''}}"> Role Admin</a></li>
                                            <li><a href="{{ url('/admin/user/penyewa') }}"" class="slide-item {{request()->is('admin/user/penyewa') ? 'active': ''}}"> Role Penyewa</a></li>
                                            <li><a href="{{ url('/admin/user/cashes') }}" class="slide-item {{request()->is('admin/user/cashes') ? 'active': ''}}"> Role Cashes</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>
                </li>
                <li class="slide {{request()->is('admin/user') ? 'active': ''}}">
                    <a class="side-menu__item has-link" data-bs-toggle="slide" href="{{ url('/') }}"><i
                            class=" side-menu__icon fa fa-google"></i><span class="side-menu__label">Halaman Utama</span></a>
                </li>
                <li class="slide {{request()->is('admin/user') ? 'active': ''}}">
                    <a class="side-menu__item has-link" data-bs-toggle="slide" href="{{ url('/logout') }}"><i
                            class=" side-menu__icon fa fa-power-off"></i><span class="side-menu__label">Log Out</span></a>
                </li>
            </ul>
            <div class="slide-right" id="slide-right"><svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191"
                    width="24" height="24" viewBox="0 0 24 24">
                    <path d="M10.707 17.707 16.414 12l-5.707-5.707-1.414 1.414L13.586 12l-4.293 4.293z" />
                </svg></div>
        </div>
    </div>
</div>
