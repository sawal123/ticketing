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
            {{-- <a href="{{ url('/transaksi') }}" class="nav-transaksi-pill">🛒 Transaksi</a> --}}
            <div class="nav-avatar-wrap">
                <div class="nav-avatar-btn" id="avatarBtn" onclick="toggleDropdown()">
                    <div class="nav-avatar"> {{ strtoupper(substr($user->name, 0, 1)) }}</div>
                    <span class="nav-username">{{ $user->name }}</span>
                    <span class="nav-chevron">▾</span>
                </div>

                <div class="user-dropdown " id="userDropdown">
                    <div class="dropdown-user-info">
                        <div class="dropdown-name">{{ $user->name }}</div>
                        <div class="dropdown-email">{{ $user->email }}</div>
                    </div>
                    @if (Auth::user()->role === 'penyewa')
                        <a class="dropdown-item  text-white" href="{{ url('/dashboard') }}">
                            <div class="di-icon " style="background:rgba(108,92,231,0.12);">👤</div>Dashboard
                        </a>
                    @endif
                    @if (Auth::user()->role === 'admin')
                        <a class="dropdown-item text-white" href="{{ url('/admin') }}">
                            <div class="di-icon " style="background:rgba(108,92,231,0.12);">👤</div>Dashboard
                        </a>
                    @endif
                    <a href="{{ url('/profile') }}" class="dropdown-item text-white ">
                        <div class="di-icon " style="background:rgba(108,92,231,0.12);">👤</div>Profil Saya
                    </a>
                    <a href="{{ url('/transaksi') }}" class="dropdown-item text-white ">
                        <div class="di-icon " style="background:rgba(61,217,196,0.12);">🛒</div>Transaksi
                    </a>
                    {{-- <a href="#"  class="dropdown-item text-white ">
                        <div class="di-icon " style="background:rgba(245,200,66,0.12);">🎫</div>Tiket Saya
                    </a> --}}
                    {{-- <a href=""  class="dropdown-item  text-white">
                        <div class="di-icon " style="background:rgba(139,132,168,0.12);">⚙️</div>Pengaturan
                    </a> --}}

                    <div class="dropdown-sep"></div>
                    <a href="{{ url('/logout') }}" class="dropdown-item logout">
                        <span class="di-icon" style="background:rgba(232,84,122,0.12);">🚪</span>
                        Keluar
                    </a>
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
        <div class="gap-2">
            <a href="{{ url('/login') }}" class="btn-masuk ">
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
