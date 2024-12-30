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
                        <div class="row mb-2">
                            <div class="col">
                                @if ($cart->status === 'SUCCESS')
                                    <div class="alert alert-info" role="alert">
                                        <small>Barcode telah dikirim ke email Anda (periksa juga folder SPAM)!</small>
                                    </div>
                                @endif
                    
                                <div class="alert alert-danger my-2" role="alert">
                                    <small>Pastikan email Anda aktif: <strong>{{ Auth::user()->email }}</strong></small>
                                </div>
                            </div>
                        </div>
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
                                @php
                                    $fee = 0;
                                    $total1 = 0;
                                @endphp
                                @foreach ($harga as $harga)
                                    <div class="row">
                                        <div class="col d-flex justify-content-between align-items-center">
                                            <div class="d-block">
                                                <p class="m-0" style="font-size: 11px">{{ $harga->kategori_harga }}</p>
                                                <h6>Rp {{ number_format($harga->harga_ticket, 0, ',', '.') }} x
                                                    {{ $harga->quantity }}</h6>
                                                @php
                                                    $fee += $harga->quantity;
                                                @endphp
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
                                @php
                                    echo $fee;
                                @endphp
                            </div>
                        </div>

                        <div class="form-group">
                            <form class="input-group d-flex align-items-center" action="{{ url('/checkVoucer') }}"
                                method="post">
                                @csrf
                                <input type="hidden" name="total" value="{{ $total }}">
                                <input type="hidden" name="event" value="{{ $event->uid }}">
                                <input type="hidden" name="cartUid" value="{{ $cart->uid }}">
                                @if ($cart->status !== 'SUCCESS')
                                    <input type="text" class="form-control my-2" name="code"
                                        placeholder="Masukan Code Voucher.." value="{{ $cartV->code }}"
                                        aria-label="Example text with button addon" aria-describedby="button-addon1">
                                    <button type="submit"class="btn btn-primary" style="height: auto"
                                        id="button-addon1">Submit</button>
                                @endif

                            </form>
                            @if (session('vError'))
                                <span class="badge rounded-pill text-danger mb-3" style="margin-top: -10px">
                                    {{ session('vError') }}
                                </span>
                            @endif
                            @if (session('voucher'))
                                <span class="badge rounded-pill text-primary mb-3" style="margin-top: -10px">
                                    {{ session('voucher') }}
                                </span>
                            @endif
                        </div>

                        <div class="card mb-2">
                            <h5 class="card-header">Payment Detail</h5>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        @if ($cart->status === 'SUCCESS')
                                            <div class="d-flex justify-content-between align-items-center">
                                                <p class="text-start m-0" style="font-size: 14px; font-weight: bold">Voucher
                                                </p>
                                                <h6 class="text-end m-0" style="font-size: 16px; font-weight: bold">
                                                    {{ $cartV->code }}
                                                </h6>
                                            </div>
                                            <hr>
                                        @endif
                                        @php
                                            $persen = 0;
                                            // $total += $event->fee * $fee;
                                            if ($cartV->unit === 'rupiah') {
                                                $total += $event->fee * $fee - $cartV->nominal;
                                            } elseif ($cartV->unit === 'persen') {
                                                $total += $event->fee * $fee;
                                                $persen = ($cartV->nominal / 100) * $total;
                                                $total = $total - $persen;
                                            } else {
                                                $total += $event->fee * $fee;
                                            }
                                        @endphp
                                        <div class="d-flex justify-content-between align-items-center">
                                            <p class="text-start m-0" style="font-size: 14px; font-weight: bold">Total
                                                Ticket</p>
                                            <h6 class="text-end m-0" style="font-size: 16px; font-weight: bold">
                                                Rp {{ number_format($total1, 0, ',', '.') }}
                                            </h6>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <p class="text-start m-0" style="font-size: 14px; font-weight: bold">Discount
                                            </p>
                                            <h6 class="text-end m-0" style="font-size: 16px; font-weight: bold">
                                                {{-- {{$cartV->unit}} --}}
                                                @if ($cartV->unit === 'rupiah')
                                                    - Rp {{ number_format($cartV->nominal, 0, ',', '.') }}
                                                @else
                                                    - Rp {{ number_format($persen, 0, ',', '.') }}
                                                @endif
                                            </h6>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <p class="text-start m-0" style="font-size: 14px; font-weight: bold">Layanan
                                                Fee</p>
                                            <h6 class="text-end m-0" style="font-size: 16px; font-weight: bold">
                                                Rp {{ number_format($event->fee * $fee, 0, ',', '.') }}
                                            </h6>
                                        </div>



                                        <div class="d-flex justify-content-between align-items-center">
                                            <p class="text-start m-0" style="font-size: 16px; font-weight: bold">Total</p>
                                            <h6 class="text-end m-0" style="font-size: 18px; font-weight: 900">Rp
                                                {{ number_format($total, 0, ',', '.') }}
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
                                                    <a href="{{ $cart->link }}"
                                                        class="btn btn-primary w-100 mt-3">Lanjutkan
                                                        Pembayaran</a>
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
