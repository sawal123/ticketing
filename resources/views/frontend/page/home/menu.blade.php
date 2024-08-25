<div class="container mx-auto">
    <div class="row">
        <div class="col d-lg-flex d-block justify-content-center "
            style="margin-right: auto; margin-left:auto; text-align: center">
            @foreach ($events as $event)
                <div class="card bg-dark border mx-2 my-2 d-inline-block" style="width: 18rem; border-radius: 4%;">
                    <div class="fugu--card-thumb">
                        @if ($event->status === 'close')
                            <div class="border"
                                style="position: absolute; top: 0; left: 0; z-index: 99; border-radius: 10px 0 10px 0; background-color: rgb(128, 0, 0); padding: 5px;">
                                <p class="fw-bold" style="color: white">Close</p>
                            </div>
                        @endif
                        <img src="{{ asset('/storage/cover/' . $event->cover) }}" class="card-img-top" loading="lazy"
                            style="border-radius: 4%; object-fit: cover; height: 150px; {{ $event->status === 'close' ? 'filter: grayscale(100%)' : '' }}"
                            alt="...">
                    </div>
                    <div class="card-body fugu--card-data text-start">
                        <h6 style="color: white">{{ $event->event }}</h6>
                        <a style="color: white;" class="mb-2" href="{{ $event->map }}">
                            <p>{{ $event->alamat }}</p>
                        </a>
                        <p>{{ date('Y-m-d H:i', strtotime($event->tanggal)) }}</p>
                        <div class="fugu--card-footer mt-1">
                            <div class="fugu--card-footer-data">
                                <span>Start From:</span>
                                @if ($event->harga)
                                    <h4>Rp {{ number_format($event->harga->harga, 0, ',', '.') }}</h4>
                                @else
                                    <p>Ticket Belum Tersedia</p>
                                @endif
                            </div>
                            <a class="fugu--btn btn-sm bg-white" href="{{ url('/ticket/' . $event->slug) }}">Beli</a>
                        </div>
                        <hr class="my-2">
                        <p>By: {{ $event->name }}</p>
                    </div>
                </div>
            @endforeach


        </div>
    </div>

    <div class="fugu--portfolio-btn">
        <a class="fugu--outline-btn" href="{{ url('/search') }}"><span>View All Events</span></a>
    </div>
</div>
