<div class="container mx-auto">
    <div class="row">
        <div class="col d-lg-flex d-block justify-content-center "
            style="margin-right: auto; margin-left:auto; text-align: center">
            @foreach ($event as $events)
                <div class="card bg-dark border mx-2 my-2 d-inline-block " style="width: 18rem; border-radius: 4%; ">
                    <div class="fugu--card-thumb">
                        @if ($events->status === 'close')
                            <div class="border"
                                style="position: absolute; top: 0; left: 0; z-index: 99; border-radius: 10px 0 10px 0;  background-color: rgb(128, 0, 0); padding: 5px;">
                                <p class="fw-bold" style="color: white">
                                    Close
                                </p>
                            </div>
                        @endif

                        <img src="{{ asset('/storage/cover/' . $events->cover) }}" class="card-img-top " loading="lazy"
                            style="border-radius: 6%; {{ $events->status === 'close' ? 'filter: grayscale(100%)' : '' }}"
                            alt="...">
                    </div>
                    <div class="card-body fugu--card-data text-start">
                        <h5 class="" style="color: white">{{ $events->event }}</h5>
                        <a style="color: white; " class="mb-2" href="{{ $events->map }}">{{ $events->alamat }}</a>
                        <p>{{ date('Y-m-d H:i', strtotime($events->tanggal)) }}</p>
                        <div class="fugu--card-footer mt-1">
                            <div class="fugu--card-footer-data">
                                <span>Start From:</span>
                                @foreach ($harga as $hargas)
                                    @if ($hargas->uid === $events->uid)
                                        <h4>Rp {{ number_format($hargas->harga, 0, ',', '.') }}</h4>
                                    @break

                                    {{-- @else
                                        <p>Ticket Belum Tersedia</p> --}}
                                @endif
                            @endforeach
                        </div>
                        <a class="fugu--btn btn-sm bg-white" href="{{ url('/ticket/' . $events->slug) }}">Beli</a>
                    </div>
                </div>
            </div>
        @endforeach


    </div>
</div>

<div class="fugu--portfolio-btn">
    <a class="fugu--outline-btn" href="{{ url('/search') }}"><span>View All Events</span></a>
</div>
</div>
