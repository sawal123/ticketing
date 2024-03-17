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
                                    <div class="btn btn-secondary">@php
                                        echo $ticket->status === 'active' ? 'Active' : 'Close';
                                    @endphp</div>
                                </div>
                                <p>{{ date('Y-m-d H:i', strtotime($ticket->tanggal)) }}</p>
                                <p>{{ $ticket->alamat }}</p>
                                <a class="btn btn-primary" href="{{ $ticket->map }}">View Location</a>
                            </div>

                        </div>
                        <div class="mt-5"></div>
                        <h4>Description</h4>
                        <div class="con">
                            <p class="content">{!! $ticket->deskripsi !!}</p>
                        </div>
                        <button id="readMore" class="btn btn-primary py-0">Baca Selengkapnya</button>
                        {{-- <p>{!! $ticket->deskripsi !!}</p> --}}
                        <div class="row my-5">
                            <h4>Talent</h4>
                            @foreach ($tickets as $tickets)
                                <div class="col-12 col-lg-6">
                                    <div class="card mb-3 me-2" style="max-width: 350px">
                                        <div class="row g-0">
                                            <div class="col d-flex justify-content-start">
                                                <img src="{{ asset('/storage/talent/' . $tickets->gambar) }}"
                                                    class="rounded-start" alt="..."
                                                    style="width: 100px; height:100px; object-fit: cover">
                                                <div class="card-body d-flex align-items-center">
                                                    <h5 class="card-title te">{{ $tickets->talent }}</h5>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                        </div>
                    </div>

                    <div class="col-lg-4  d-none d-lg-block">
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
                                    @if (count($list) > 0)
                                        @foreach ($list as $key => $list)
                                            <div class="card ps-3 my-2">
                                                <div class="row d-flex align-items-center">
                                                    <div class="col-4 py-2" style="float: right">
                                                        <p style="font-size: 12px; font-weight: 800; line-height: 15px" class="m-0">
                                                            {{ $list['kategori'] }} </p>
                                                        <p style="font-weight: bold" class="harga">

                                                            Rp {{ number_format($list['harga'], 0, ',', '.') }}</p>
                                                    </div>
                                                    @php
                                                        $kategori = $list['kategori'];
                                                        $qty = $list['qty'];
                                                      
                                                    @endphp
                                                    <div class="col-8 d-flex justify-content-end align-content-end">
                                                        <div class="input-wrapper container d-flex ">
                                                            @if ($jmlhQty[$kategori] < $list['qty'] && $ticket->status === 'active')
                                                                <input type="hidden" class="price-input"
                                                                    placeholder="Price" name="harga{{ $loop->index }}"
                                                                    value="{{ $list['harga'] }}">

                                                                <input type="hidden" class="price-input"
                                                                    placeholder="Price" name="kategori{{ $loop->index }}"
                                                                    value="{{ $list['kategori'] }}">

                                                                <button type="button" class="btn btn-minus btn-primary"
                                                                    style="min-width: 40px; height: 40px;"
                                                                    data-target="quantity{{ $loop->index }}">-</button>

                                                                <input type="text"  
                                                                    class="form-control p-0 input quantity{{ $loop->index }}"
                                                                    min="0" max="5" step="1"
                                                                    value="0" name="ticket{{ $loop->index }}"
                                                                    id="" readonly>

                                                                <input type="hidden" name="orderBy{{ $loop->index }}"
                                                                    value="{{ $loop->index + 1 }}">

                                                                <button type="button" class="btn btn-plus btn btn-primary"
                                                                    style="min-width: 40px; height: 40px;"
                                                                    data-target="quantity{{ $loop->index }}">+</button>
                                                            @else
                                                                <button disabled="disabled"class="btn btn-success w-100">
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
                                            <button type="submit" class="btn btn-primary">Check Out</button>
                                        </div>
                                    </div>
                                </div>
                                @csrf
                            </form>
                        @else
                            <p>Ticket Belum Tersedia...</p>
                            @endif


                        </div>
                    </div>
                    <div class="mt-2"></div>

                    {{-- ======================MOBILE============================= --}}
                    <div class="row  mt-5 d-lg-none">
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
                                        @if (count($lists) > 0)
                                            @csrf
                                            @foreach ($lists as $lists)
                                                <div class="card ps-3 my-2">
                                                    <div class="row d-flex align-items-center">
                                                        <div class="col-4 py-2" style="float: right">
                                                            <p style="font-size: 12px; font-weight: 800; line-height: 15px" class="m-0">
                                                                {{ $lists['kategori'] }} </p>
                                                            <p style="font-weight: bold" class="harga">
    
                                                                Rp {{ number_format($lists['harga'], 0, ',', '.') }}</p>
                                                        </div>
                                                      
                                                        <div class="col-8 d-flex justify-content-end align-content-end">
                                                            <div class="input-wrapper container d-flex ">
                                                                <div class="input-wrapper container d-flex ">
                                                                    @if ($jmlhQty[$kategori] < $list['qty'] && $ticket->status === 'active')
                                                                        <input type="hidden" class="price-input"
                                                                            placeholder="Price"
                                                                            name="harga{{ $loop->index }}"
                                                                            value="{{ $lists['harga'] }}">

                                                                        <input type="hidden" class="price-input"
                                                                            placeholder="Price"
                                                                            name="kategori{{ $loop->index }}"
                                                                            value="{{ $lists['kategori'] }}">


                                                                        <button type="button"
                                                                            class="btn btn-minus btn-primary"
                                                                            style="min-width: 40px; height: 40px;"
                                                                            data-target="quantity{{ $loop->index }}">-</button>

                                                                        <input type="text"
                                                                            class="form-control input quantity{{ $loop->index }}"
                                                                            min="0" max="5" step="1"
                                                                            value="0"
                                                                            name="ticket{{ $loop->index }}"
                                                                            id="" readonly>

                                                                        <input type="hidden"
                                                                            name="orderBy{{ $loop->index }}"
                                                                            value="{{ $loop->index + 1 }}">

                                                                        <button type="button"
                                                                            class="btn btn-plus btn btn-primary"
                                                                            style="min-width: 40px; height: 40px;"
                                                                            data-target="quantity{{ $loop->index }}">+</button>
                                                                    @else
                                                                        <button
                                                                            disabled="disabled"class="btn btn-success w-100">
                                                                            Sold Out</button>
                                                                    @endif
                                                                </div>
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
                                                        <button type="submit" class="btn btn-primary">Check Out</a>
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
