@extends('frontend.index')

@section('content')
    <style>
        .text .icol p,
        .text .icol h4 {
            color: white;
        }

        p {
            margin: 0px;
        }

        /* 👇 KODE PERBAIKAN LIST / BULLET (FINAL) 👇 */

        /* Mengembalikan padding dan bullet HANYA untuk di dalam #teks-deskripsi */
        #teks-deskripsi ul {
            list-style-type: disc !important;
            padding-left: 30px !important;
            /* INI KUNCI AGAR BULLET MASUK LAYAR */
            margin-bottom: 15px !important;
        }

        #teks-deskripsi ol {
            list-style-type: decimal !important;
            padding-left: 30px !important;
            /* INI KUNCI AGAR ANGKA MASUK LAYAR */
            margin-bottom: 15px !important;
        }

        #teks-deskripsi li {
            display: list-item !important;
            list-style-position: outside !important;
            margin-bottom: 5px !important;
        }

        /* Mengatasi paragraf dari WYSIWYG Editor */
        #teks-deskripsi li p {
            display: inline !important;
            margin: 0 !important;
        }

        /* CSS Read More */
        .deskripsi-wrapper {
            max-height: 6em;
            overflow: hidden;
        }

        .deskripsi-wrapper.expanded {
            max-height: none;
        }
    </style>
    <div class="row m-0">
        <div class="col mt-5">
            <div class="container">

                <div class="row " style="margin-top: 80px">
                    <div class="col-12 col-lg-8 ">
                        <img src="{{ asset('/storage/cover/' . $ticket->cover) }}" alt="" class="img-thumbnail ">
                        <div class="mt-5"></div>
                        <div class="row">
                            <div class="col">
                                <div class="d-flex justify-content-between pe-lg-4">
                                    <h3>{{ $ticket->event }}</h3>
                                    <div class="btn btn-secondary">
                                        {{ $ticket->status === 'active' ? 'Active' : 'Close' }}
                                    </div>
                                </div>
                                <p>{{ date('Y-m-d H:i', strtotime($ticket->tanggal)) }}</p>
                                <p>{{ $ticket->alamat }}</p>
                                <a class="btn btn-primary" href="{{ $ticket->map }}">View Location</a>
                            </div>

                        </div>
                        <div class="mt-5"></div>
                        <h4>Description</h4>
                        <div id="teks-deskripsi" class="deskripsi-wrapper">
                            {!! $ticket->deskripsi !!}
                        </div>
                        <button id="readMoreBtn" class="btn btn-primary py-0 mt-2">Baca Selengkapnya</button>

                        <div class="row my-5">
                            <h4>Talent</h4>
                            {{-- PERBAIKAN: Ubah nama variabel looping agar tidak bentrok --}}
                            @foreach ($tickets as $talent)
                                <div class="col-12 col-lg-6">
                                    <div class="card mb-3 me-2" style="max-width: 350px">
                                        <div class="row g-0">
                                            <div class="col d-flex justify-content-start">
                                                <img src="{{ asset('/storage/talent/' . $talent->gambar) }}"
                                                    class="rounded-start" alt="..."
                                                    style="width: 100px; height:100px; object-fit: cover">
                                                <div class="card-body d-flex align-items-center">
                                                    <h5 class="card-title te">{{ $talent->talent }}</h5>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- ====================== DESKTOP FORM ============================= --}}
                    <div class="col-lg-4 d-none d-lg-block">
                        <div class="card ">
                            <div class="card-header">
                                <h5 class="card-title">Ticket Kategori</h5>
                                @if (session('error'))
                                    <div class="alert alert-danger">
                                        {{ session('error') }}
                                    </div>
                                @endif
                            </div>
                            <form action="{{ url('/checkout') }}" method="post">
                                @csrf
                                <input type="hidden" name="eventUid" value="{{ $ticket->uid }}">
                                <div class="card-body" style="overflow-y: scroll ;max-height: 300px;">
                                    @if ($list->count() > 0)
                                        {{-- PERBAIKAN: Pakai nama variabel $hargaItem --}}
                                        @foreach ($list as $key => $hargaItem)
                                            <div class="card ps-3 my-2">
                                                <div class="row d-flex align-items-center">
                                                    <div class="col-4 py-2" style="float: right">
                                                        <p style="font-size: 12px; font-weight: 800; line-height: 15px"
                                                            class="m-0">
                                                            {{ $hargaItem->kategori }} </p>
                                                        <p style="font-weight: bold" class="harga">
                                                            Rp {{ number_format($hargaItem->harga, 0, ',', '.') }}</p>
                                                    </div>
                                                    @php
                                                        $kategori = $hargaItem->kategori;
                                                        $qty = $hargaItem->qty;
                                                        // PERBAIKAN PENTING: Tambahkan ?? 0 agar tidak error jika array kosong
                                                        $sold = $jmlhQty[$kategori] ?? 0;
                                                    @endphp
                                                    <div class="col-8 d-flex justify-content-end align-content-end">
                                                        <div class="input-wrapper container d-flex ">
                                                            @if ($sold < $qty && $ticket->status === 'active')
                                                                <input type="hidden" class="price-input"
                                                                    placeholder="Price" name="harga{{ $loop->index }}"
                                                                    value="{{ $hargaItem->harga }}">

                                                                <input type="hidden" class="price-input"
                                                                    placeholder="Price" name="kategori{{ $loop->index }}"
                                                                    value="{{ $hargaItem->kategori }}">

                                                                <button type="button" class="btn btn-minus btn-primary"
                                                                    style="min-width: 40px; height: 40px; border-radius:20px 0px 0px 20px"
                                                                    data-target="quantity{{ $loop->index }}"> <i
                                                                        class="fa fa-minus"
                                                                        style="color: #fff !important;"></i></button>

                                                                <input type="text"
                                                                    class="form-control p-0 input quantity{{ $loop->index }}"
                                                                    min="0" max="5" step="1"
                                                                    value="0" name="ticket{{ $loop->index }}"
                                                                    id="" readonly>

                                                                <input type="hidden" name="orderBy{{ $loop->index }}"
                                                                    value="{{ $loop->index + 1 }}">

                                                                <button type="button" class="btn btn-plus btn-primary"
                                                                    style="min-width: 40px; height: 40px; border-radius:0px 20px 20px 0px"
                                                                    data-target="quantity{{ $loop->index }}">
                                                                    <i class="fa fa-plus"
                                                                        style="color: #fff !important;"></i>
                                                                </button>
                                                            @else
                                                                <button disabled="disabled" class="btn btn-success w-100">
                                                                    Sold Out</button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach

                                </div>
                                <div class="card-footer text-muted">
                                    <div class="row">
                                        <div class="col-6 ">
                                            <p>Total</p>
                                            <h5 class="total">Rp 0</h5>
                                        </div>
                                        <div class="col-6 d-flex justify-content-end align-items-center">
                                            <button type="submit" class="btn btn-primary checkButton" disabled>Check
                                                Out</button>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <p>Ticket Belum Tersedia...</p>
                                @endif
                            </form>
                        </div>
                    </div>
                    <div class="mt-2"></div>

                    {{-- ====================== MOBILE FORM ============================= --}}
                    <div class="row mt-5 d-lg-none">
                        <div class="col fixed-bottom text-center shadow-lg" style="background-color: white; height: 80px;">
                            <button class="btn btn-primary mt-4" type="button" data-bs-toggle="offcanvas"
                                data-bs-target="#offcanvasBottom" aria-controls="offcanvasBottom">Beli Tiket</button>

                            <div class="offcanvas offcanvas-bottom" tabindex="-1" id="offcanvasBottom"
                                aria-labelledby="offcanvasBottomLabel" style="height: auto">
                                <div class="offcanvas-header">
                                    <h5 class="offcanvas-title" id="offcanvasBottomLabel">Beli Tiket</h5>
                                    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"
                                        aria-label="Close"></button>
                                </div>

                                <div class="offcanvas-body small text-start">
                                    <form action="{{ url('/checkout') }}" method="post">
                                        <input type="hidden" name="eventUid" value="{{ $ticket->uid }}">
                                        @if ($lists->count() > 0)
                                            @csrf
                                            {{-- PERBAIKAN: Pakai nama variabel $hargaItemMobile --}}
                                            @foreach ($lists as $key => $hargaItemMobile)
                                                <div class="card ps-3 my-2">
                                                    <div class="row d-flex align-items-center">
                                                        <div class="col-4 py-2" style="float: right">
                                                            <p style="font-size: 12px; font-weight: 800; line-height: 15px"
                                                                class="m-0">
                                                                {{ $hargaItemMobile->kategori }} </p>
                                                            <p style="font-weight: bold" class="harga">
                                                                Rp
                                                                {{ number_format($hargaItemMobile->harga, 0, ',', '.') }}
                                                            </p>
                                                        </div>

                                                        @php
                                                            $kategoriMob = $hargaItemMobile->kategori;
                                                            $qtyMob = $hargaItemMobile->qty;
                                                            $soldMob = $jmlhQty[$kategoriMob] ?? 0;
                                                        @endphp

                                                        <div class="col-8 d-flex justify-content-end align-content-end">
                                                            <div class="input-wrapper container d-flex ">
                                                                @if ($soldMob < $qtyMob && $ticket->status === 'active')
                                                                    <input type="hidden" class="price-input"
                                                                        placeholder="Price"
                                                                        name="harga{{ $loop->index }}"
                                                                        value="{{ $hargaItemMobile->harga }}">

                                                                    <input type="hidden" class="price-input"
                                                                        placeholder="Price"
                                                                        name="kategori{{ $loop->index }}"
                                                                        value="{{ $hargaItemMobile->kategori }}">

                                                                    <button type="button"
                                                                        class="btn btn-minus btn-primary"
                                                                        style="min-width: 40px; height: 40px; border-radius:20px 0px 0px 20px"
                                                                        data-target="quantity{{ $loop->index }}"> <i
                                                                            class="fa fa-minus"
                                                                            style="color: #fff !important;"></i></button>

                                                                    <input type="text"
                                                                        class="form-control input quantity{{ $loop->index }}"
                                                                        min="0" max="5" step="1"
                                                                        value="0" name="ticket{{ $loop->index }}"
                                                                        data-target="quantity{{ $loop->index }}"
                                                                        readonly>

                                                                    <input type="hidden"
                                                                        name="orderBy{{ $loop->index }}"
                                                                        value="{{ $loop->index + 1 }}">

                                                                    <button type="button"
                                                                        class="btn btn-plus btn-primary"
                                                                        style="min-width: 40px; height: 40px; border-radius:0px 20px 20px 0px"
                                                                        data-target="quantity{{ $loop->index }}">
                                                                        <i class="fa fa-plus"
                                                                            style="color: #fff !important;"></i>
                                                                    </button>
                                                                @else
                                                                    <button disabled="disabled"
                                                                        class="btn btn-success w-100">
                                                                        Sold Out</button>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                            <div class="card-footer text-muted text-start">
                                                <div class="row">
                                                    <div class="col-6 ">
                                                        <p>Total</p>
                                                        <h5 class="total">Rp 0</h5>
                                                    </div>
                                                    <div class="col-6 d-flex justify-content-end align-items-center">
                                                        <button type="submit" class="btn btn-primary checkButton"
                                                            disabled>Check Out</button>
                                                    </div>
                                                </div>
                                            </div>
                                    </form>
                                @else
                                    <p>Tidak Ada Ticket...</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
