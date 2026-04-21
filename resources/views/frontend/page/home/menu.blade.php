<div class="container mx-auto">
    <div class="row">
        @foreach ($events as $event)
            <div class="col-12 col-md-6 col-lg-3 d-flex justify-content-center mb-4">

                <div class="card bg-dark border mx-2 my-2 d-inline-block" style="width: 18rem; border-radius: 4%;">
                    <div class="fugu--card-thumb">
                        @if ($event->status === 'close')
                            <div class="border"
                                style="position: absolute; top: 0; left: 0; z-index: 99; border-radius: 10px 0 10px 0; background-color: rgb(128, 0, 0); padding: 5px;">
                                <p class="fw-bold mb-0" style="color: white">Close</p>
                            </div>
                        @endif
                        <img src="{{ asset('/storage/cover/' . $event->cover) }}" class="card-img-top" loading="lazy"
                            style="border-radius: 4%; object-fit: cover; height: 150px; {{ $event->status === 'close' ? 'filter: grayscale(100%)' : '' }}"
                            alt="...">
                    </div>
                    <div class="card-body fugu--card-data text-start">
                        <h6 style="color: white">
                            <a href="{{ url('/ticket/' . $event->slug) }}" class="text-white">{{ $event->event }}</a>
                        </h6>
                        <a style="color: white;" class="mb-2" href="{{ $event->map }}">
                            <p>{{ implode(' ', array_slice(explode(' ', $event->alamat), 0, 3)) . '...' }}</p>
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
                        <p class="mb-0">By: {{ $event->user->name ?? 'Unknown' }}</p>
                    </div>
                </div>

            </div>
        @endforeach
    </div>

    <div class="fugu--portfolio-btn text-center mt-4">
        <a class="fugu--outline-btn" href="{{ url('/search') }}"><span>View All Events</span></a>
    </div>
</div>