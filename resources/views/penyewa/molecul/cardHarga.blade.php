@if (count($harga) > 0)
    @foreach ($harga as $harga)
        <div class="col-sm-6 col-lg-6 col-md-12 col-xl-3">
            <div class="card">
                <div class="row">
                    <div class="col-8">
                        <div class="card-body p-4">
                            <h4 class="mb-2 fw-normal mt-2">Rp {{ number_format($harga->harga, 0, ',', '.') }}</h4>
                            <h5 class="fw-normal mb-0">{{ $harga->kategori }}</h5>
                            <p class="fw-normal mb-0">{{ $harga->qty }} Ticket</p>
                        </div>
                    </div>
                    <div class="col-4 ">

                        <button type="submit" data-bs-target="#updateHarga" data-bs-effect="effect-sign"
                            data-bs-toggle="modal" class="my-4 mx-auto d-flex btn btn-primary"
                            data-kategori="{{ $harga->kategori }}" data-qty="{{ $harga->qty }}"
                            data-harga="{{ $harga->harga }}" data-id="{{ $harga->id }}">
                            <i class="fa fa-edit fs-20 text-white "></i>

                        </button>
                        <a href="{{ url('admin/hargas/delete/' . $harga->id) }}" class="delete">
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
