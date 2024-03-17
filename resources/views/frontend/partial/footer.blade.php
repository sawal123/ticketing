<footer class="fugu--footer-section" style="margin-top: 150px">
    {{-- <div class="tinggi" style="height: 150px"></div> --}}
    <div class="container">
        <div class="fugu--footer-top">
            <div class="row">
                <div class="col-lg-3">
                    <div class="fugu--textarea">
                        <div class="fugu--footer-logo">
                            {{-- <img src="assets/images/logo/logo-white.svg" alt=""
                            class="light-version-logo"> --}}
                        </div>
                        <p>Follow Gotik dan ikuti perkembangan tentang event disini.</p>
                        <div class="fugu--social-icon">
                            <ul>
                                @foreach ($contact as $item)
                                    <li><a href="{{ $item->link }}"><img width="16" height="16" src="{{ asset('storage/sosmed/'.$item->icon) }}" alt=""></a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 offset-lg-1 col-md-4 col-sm-4">
                    <div class="fugu--footer-menu">
                        <span>Service</span>
                        <ul>
                            <li><a href="#">Event Management</a></li>
                            <li><a href="#">Online Ticketing</a></li>
                            <li><a href="#">Point Of Sale</a></li>

                        </ul>
                    </div>
                </div>
                <div class="col-lg-2 offset-lg-1 col-md-4 col-sm-4">
                    <div class="fugu--footer-menu">
                        <span>Partner</span>
                        <ul>
                            <li><a href="#">Rme Management</a></li>
                            {{-- <li><a href="#">Arum Media</a></li> --}}

                        </ul>
                    </div>
                </div>
                <div class="col-lg-2 offset-lg-1 col-md-4 col-sm-4">
                    <div class="fugu--footer-menu">
                        <span>Support</span>
                        <ul>
                            <li><a href="{{url('/contact')}}">Hubungi Kami</a></li>
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
</footer>
