@if (count($harga) > 0)
    {{-- Ubah variabel menjadi $h agar tidak bentrok dengan koleksi $harga --}}
    @foreach ($harga as $h)
        <div class="col-sm-6 col-lg-6 col-md-12 col-xl-3">
            <div class="card">
                <div class="row">
                    <div class="col-8">
                        <div class="card-body p-4">
                            <h4 class="mb-2 fw-normal mt-2">Rp {{ number_format($h->harga, 0, ',', '.') }}</h4>
                            <h5 class="fw-normal mb-0">{{ $h->kategori }}</h5>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <p class="fw-normal mb-0">{{ $h->qty }} Ticket</p>
                                {{-- Ambil data terjual langsung dari Array, jika kosong maka 0 --}}
                                @php
                                    $terjual = $terjualPerHarga[$h->id] ?? 0;
                                @endphp
                                <p class="fw-normal mb-0">Terjual : {{ $terjual }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-4 ">
                        <button type="submit" data-bs-target="#updateHarga" data-bs-effect="effect-sign" data-bs-toggle="modal"
                            class="my-4 mx-auto d-flex btn btn-primary" data-kategori="{{ $h->kategori }}"
                            data-qty="{{ $h->qty }}" data-harga="{{ $h->harga }}" data-id="{{ $h->id }}">
                            <i class="fa fa-edit fs-20 text-white "></i>
                        </button>
                        <a href="{{ url('dashboard/hargas/delete/' . $h->id) }}" class="delete">
                            <button type="button" class=" my-4 mx-auto d-flex btn btn-danger">
                                <i class="fa fa-trash fs-20 text-white "></i>
                            </button>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@else
    <p>Tidak ada harga...</p>
@endif