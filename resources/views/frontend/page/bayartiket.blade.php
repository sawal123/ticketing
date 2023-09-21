@extends('frontend.index')

@section('content')
    <div class="row mt-5">
        <div class="col">

            <div class="container pt-lg-5">
                <div class="row mt-5 ">
                    <div class="col-12 col-lg-4">
                        <div class="card mb-2" style="">
                            <img src="{{ asset('storage/cover/' . $event->cover) }}" class="card-img-top" alt="...">
                            <div class="card-body">
                                <h6 class="m-0">{{ $event->event }}</h6>
                                <p class="card-text m-0">{{ $event->tanggal }}</p>
                                <p class="card-text m-0">{{ $event->alamat }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-8">
                        @if (session('success'))
                            <div class="alert alert-primary">
                                {{ session('success') }}
                            </div>
                        @endif
                        <div class="card mb-2">
                            <h5 class="card-header">Ticket Detail </h5>

                            <div class="card-body">
                                <h6>Invoice : {{ $cart->invoice }}</h6>
                                <div class="d-flex justify-content-between">
                                    <p class="m-0">Qty</p>
                                    <p class="m-0">Total</p>
                                </div>
                                @foreach ($harga as $harga)
                                    <div class="row">
                                        <div class="col d-flex justify-content-between align-items-center">
                                            <div class="d-block">
                                                <p class="m-0" style="font-size: 11px">{{ $harga->kategori_harga }}</p>
                                                <h6>Rp {{ number_format($harga->harga_ticket, 0, ',', '.') }} x
                                                    {{ $harga->quantity }}</h6>
                                            </div>
                                            <h6>
                                                @php
                                                    $total1 = (int) $harga->quantity * (int) $harga->harga_ticket;
                                                @endphp
                                                Rp{{ number_format($total1 ?? 0) }}
                                            </h6>

                                        </div>
                                    </div>
                                    <hr>
                                @endforeach
                            </div>
                        </div>



                        <div class="card mb-2">
                            <h5 class="card-header">Payment Detail</h5>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <p class="text-start m-0" style="font-size: 14px; font-weight: bold">Total
                                                Ticket</p>
                                            <h6 class="text-end m-0" style="font-size: 16px; font-weight: bold">
                                                Rp {{ number_format($total, 0, ',', '.') }}
                                            </h6>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <p class="text-start m-0" style="font-size: 14px; font-weight: bold">Layanan
                                                Fee</p>
                                            <h6 class="text-end m-0" style="font-size: 16px; font-weight: bold">
                                                Rp {{ number_format($event->fee, 0, ',', '.') }}
                                            </h6>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <p class="text-start m-0" style="font-size: 16px; font-weight: bold">Total</p>
                                            <h6 class="text-end m-0" style="font-size: 18px; font-weight: 900">Rp
                                                {{ number_format($total += $event->fee, 0, ',', '.') }}
                                            </h6>
                                        </div>
                                        @if ($cart->status === 'UNPAID' || $cart->status === 'PENDING')
                                            <form action="{{ url('/paynow') }}" method="post"
                                                enctype="multipart/form-data">
                                                @csrf
                                                <input type="hidden" name="invoice" value="{{ $cart->invoice }}">
                                                <input type="hidden" name="person" value="{{ Auth::user()->uid }}">
                                                <input type="hidden" name="event" value="{{ $uid }}">
                                                <input type="hidden" value="{{ $total }}" name="amount">

                                                @if ($cart->status === 'UNPAID')
                                                    <button type="submit" class="btn btn-primary w-100 mt-3">Bayar
                                                        Sekarang</button>
                                                @else
                                                    <button type="submit" class="btn btn-primary w-100 mt-3">Lanjutkan
                                                        Pembayaran</button>
                                                @endif
                                            </form>
                                        @else
                                            <button type="submit"
                                                class="btn btn-success w-100 mt-3">{{ $cart->status }}</button>
                                        @endif


                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
@endsection
