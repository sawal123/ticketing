<div class="container mx-auto">
    <div class="row">
        <div class="col d-lg-flex d-block justify-content-center "
            style="margin-right: auto; margin-left:auto; text-align: center">
            @foreach ($event as $events)
                <div class="card bg-dark border mx-2 my-2 d-inline-block " style="width: 18rem; border-radius: 4%; ">
                    <div class="fugu--card-thumb">
                        <img src="{{ asset('/storage/cover/' . $events->cover) }}" class="card-img-top " loading="lazy"
                            style="border-radius: 6%" alt="...">
                    </div>
                    <div class="card-body fugu--card-data text-start">
                        <h5 class="" style="color: white">{{ $events->events }}</h5>
                        <p>Mulai Events : {{ date('Y-m-d H:i', strtotime($events->tanggal)) }}</p>
                        <div class="fugu--card-footer">
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
    <a class="fugu--outline-btn" href=""><span>View All NFTs</span></a>
</div>
</div>
