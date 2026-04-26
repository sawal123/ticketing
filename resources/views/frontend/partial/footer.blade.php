{{-- <footer class="fugu--footer-section">
    <div class="container">
        <div class="fugu--footer-top">
            <div class="row">
                <div class="col-lg-3">
                    <div class="fugu--textarea">
                        <div class="fugu--footer-logo">
                        </div>
                        <p>Follow Gotik dan ikuti perkembangan tentang event disini.</p>
                        <div class="fugu--social-icon">
                            <ul>
                                @foreach ($contact as $item)
                                    <li><a href="{{ $item->link }}"><img width="16" height="16"
                                                src="{{ asset('storage/sosmed/' . $item->icon) }}" alt=""></a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 offset-lg-1 col-md-4 col-sm-4">
                    <div class="fugu--footer-menu">
                        <span>Service</span>
                        <ul style="color: white">
                            <li><a>Event Management</a></li>
                            <li><a>Online Ticketing</a></li>
                            <li><a>Point Of Sale</a></li>

                        </ul>
                    </div>
                </div>
                <div class="col-lg-2 offset-lg-1 col-md-4 col-sm-4">
                    <div class="fugu--footer-menu">
                        <span>Support</span>
                        <ul>
                            <li><a href="{{ url('/contact') }}">Hubungi Kami</a></li>
                            <li><a href="{{ url('/term') }}">Term and Condition</a></li>

                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="fugu--footer-bottom">
            <p>&copy; Copyright 2023, Gotik</p>
        </div>
    </div>
</footer> --}}

{{-- <a href="{{ url('/') }}" class="logo pr-5">
        <img src="{{ asset('storage/logo/' . $logo[0]->logo) }}" style="width: 100px" alt="" class="">
    </a> --}}

<footer  style="display: flex; justify-content: space-evenly; gap: 32px; flex-wrap: wrap;">
    <div class="f-brand">
        <a href="{{ url('/') }}" class="logo">
            <span class="t">
                <img
                    src="{{ asset('storage/logo/' . $logo[0]->logo) }}"
                    style="width: 100px"
                    alt="Logo GoTik"
                >
            </span>
        </a>

        <p>Platform tiket konser dan event terpercaya di Indonesia. Pembayaran online lebih gampang.</p>

        <div style="display: flex; gap: 8px; align-items: center;">
            @foreach ($contact as $item)
                <a href="{{ $item->link }}" target="_blank" rel="noopener noreferrer">
                    <img
                        width="16"
                        height="16"
                        src="{{ asset('storage/sosmed/' . $item->icon) }}"
                        alt="Sosial media perusahaan"
                    >
                </a>
            @endforeach
        </div>
    </div>

    <div style="display: flex; gap: 40px; flex-wrap: wrap; ">
        <div class="f-col">
            <h5>Platform</h5>
            <a href="{{ url('/') }}">Beranda</a>
            <a href="#">Event Terbaru</a>
            <a href="#">Semua Event</a>
            <a href="#">Kategori</a>
        </div>

        <div class="f-col">
            <h5>Bantuan</h5>
            <a href="#">Coming Soon</a>
            {{-- <a href="#">Privasi</a> --}}
            <a href="{{ url('/term') }}">Syarat & Ketentuan</a>
            {{-- <a href="{{ url('/contact') }}">Refund Policy</a> --}}
        </div>
    </div>
</footer>

<div class="footer-bottom">
    <span>&copy; 2026 GoTik. All rights reserved.</span>
    <span>Made with love in Indonesia</span>
</div>
