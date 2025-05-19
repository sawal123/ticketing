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
                                <p>Total Ticket : {{ $fee }}</p>

                            </div>
                        </div>
                        @if (!$cart->link)
                            <div class="col-sm-12 col-md-12 col-lg-12 col-xl-12">
                                <div class="card">
                                    <h5 class="card-header">Payment</h5>
                                    <div class="card-body">
                                        <div class="accordion" id="accordionExample">
                                            <div class="accordion-item">
                                                <h2 class="accordion-header" id="headingOne">
                                                    <button class="accordion-button" type="button"
                                                        data-bs-toggle="collapse" data-bs-target="#collapseOne"
                                                        aria-expanded="true" aria-controls="collapseOne">
                                                        Pay
                                                    </button>
                                                </h2>
                                                <div id="collapseOne" class="accordion-collapse collapse show"
                                                    aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                                                    <div class="accordion-body">
                                                        <div class="row">
                                                            @foreach ($payment as $gateway)
                                                                <div class="col-md-4">
                                                                    {{-- <div class="card mb-4 shadow-sm p-1 {{ $iFee->slug === $gateway->slug ? 'border-success' : '' }}" --}}
                                                                    <div class="card mb-4 shadow-sm p-1 "
                                                                        id="card{{ $gateway->id }}"
                                                                        style="cursor: pointer;"
                                                                        onclick="selectPayment({{ $gateway->id }},{{ $gateway->biaya }}, '{{ $gateway->biaya_type }}', this)">
                                                                        <div class="d-flex gap-2 align-items-center">
                                                                            <!-- Gambar di Samping Kecil -->
                                                                            <img src="{{ asset('storage/' . $gateway->icon) }}"
                                                                                class="img-fluid"
                                                                                style="width: 50px; height: 50px; object-fit: cover;"
                                                                                alt="{{ $gateway->payment }}">

                                                                            <div class="ml-3">
                                                                                <h5 class="card-title"
                                                                                    style="margin-bottom: 0px !important;">
                                                                                    {{ $gateway->payment }}</h5>
                                                                                <p class="card-text"
                                                                                    style="font-size: 14px; font-weight: bold; margin-top: 0px !important;">
                                                                                    Biaya:
                                                                                    @if ($gateway->biaya_type == 'rupiah')
                                                                                        Rp{{ number_format($gateway->biaya, 0, ',', '.') }}
                                                                                    @else
                                                                                        {{ $gateway->biaya }}%
                                                                                    @endif
                                                                                </p>
                                                                            </div>

                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>


                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                        <div class="col-sm-12 col-md-12 col-lg-12 col-xl-12">
                            <div class="card mt-3">
                                <h5 class="card-header">Voucher</h5>
                                <div class="card-body">
                                    <div class="form-group">
                                        <form class="input-group d-flex align-items-center"
                                            action="{{ url('/checkVoucer') }}" method="post">
                                            @csrf
                                            {{-- <input type="hidden" name="total" value="{{ $total }}"> --}}
                                            @if ($cart->status !== 'SUCCESS')
                                                <input type="hidden" name="event" value="{{ $event->uid }}">
                                                <input type="hidden" name="cartUid" value="{{ $cart->uid }}">
                                                <input type="text" class="form-control my-2" name="code"
                                                    {{ $cart->link ? 'readonly' : '' }}
                                                    placeholder="Masukan Code Voucher.." value="{{ $voucher->code }}"
                                                    aria-label="Example text with button addon"
                                                    aria-describedby="button-addon1">
                                                <button type="submit"class="btn btn-primary"
                                                    {{ $cart->link ? 'disabled' : '' }} style="height: auto"
                                                    id="button-addon1">Gunakan</button>
                                            @endif
                                        </form>
                                        @if (session('vError'))
                                            <span class="text-danger  " style="font-size: 11px">
                                                {{ session('vError') }}
                                            </span>
                                        @endif
                                        @if (session('voucher'))
                                            <span class="text-primary " style="font-size: 11px">
                                                {{ session('voucher') }}
                                            </span>
                                        @endif
                                        @if (!$cart->link)
                                            @if ($voucher->code)
                                                <div class="mb-3">
                                                    <form action="{{ url('/closeVoucher') }}" method="post">
                                                        @csrf
                                                        <input type="hidden" name="event"
                                                            value="{{ $event->uid }}">
                                                        <input type="hidden" name="cartUid"
                                                            value="{{ $cart->uid }}">
                                                        <input type="hidden" class="form-control my-2" name="code"
                                                            value="{{ $voucher->code }}">
                                                        <span class="fw-bold fs-6">Tidak ingin menggunakan voucher
                                                            {{ $voucher->code }}?</span>
                                                        <button type="submit" class="btn-danger px-2 rounded">
                                                            <i class="fa fa-close" style="color: #fff !important;"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            @endif
                                        @endif



                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="card mb-2 mt-3">
                            <h5 class="card-header">Payment Detail</h5>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        @if ($cart->status === 'SUCCESS')
                                            <div class="d-flex justify-content-between align-items-center">
                                                <p class="text-start m-0" style="font-size: 14px; font-weight: bold">
                                                    Voucher
                                                </p>
                                                <h6 class="text-end m-0" style="font-size: 16px; font-weight: bold">
                                                    {{ $voucher->code }}
                                                </h6>
                                            </div>
                                            <hr>
                                        @endif

                                        <div class="d-flex justify-content-between align-items-center">
                                            <p class="text-start m-0" style="font-size: 14px; font-weight: bold">Ticket
                                            </p>
                                            <h6 class="text-end m-0" style="font-size: 16px; font-weight: bold">
                                                Rp <span id="totalAmount">{{ number_format($total, 0, ',', '.') }}</span>
                                            </h6>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <p class="text-start m-0" style="font-size: 14px; font-weight: bold">Discount
                                            </p>
                                            <h6 class="text-end m-0" style="font-size: 16px; font-weight: bold">
                                                -Rp {{ number_format($diskon, 0, ',', '.') }}
                                            </h6>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <p class="text-start m-0" style="font-size: 14px; font-weight: bold">Internet
                                                Fee</p>
                                            <h6 class="text-end m-0" style="font-size: 16px; font-weight: bold">
                                                Rp <span id="biaya">0</span>
                                            </h6>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <p class="text-start m-0" style="font-size: 16px; font-weight: bold">Total</p>
                                            <h6 class="text-end m-0" style="font-size: 18px; font-weight: 900">Rp
                                                <span id="finalTotal">{{ number_format($total, 0, ',', '.') }}</span>
                                            </h6>
                                        </div>
                                        @if ($cart->status === 'UNPAID' || $cart->status === 'PENDING')
                                            <form action="{{ url('/paynow') }}" method="post"
                                                enctype="multipart/form-data">
                                                @csrf
                                                <input type="hidden" id="selectedPayment" name="payment_id"
                                                    value="">
                                                <input type="hidden" name="invoice" value="{{ $cart->invoice }}">
                                                <input type="hidden" name="person" value="{{ Auth::user()->uid }}">
                                                <input type="hidden" name="event" value="{{ $uid }}">
                                                <input type="hidden" name="cartUid" value="{{ $cart->uid }}">
                                                {{-- <input type="hidden" value="{{ $total }}" name="amount"> --}}

                                                @if ($cart->status === 'UNPAID')
                                                    <button type="submit"  id="payButton" class="btn btn-primary w-100 mt-3">Bayar
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

    <script>
        // Definisikan fungsi selectPayment terlebih dahulu
        function selectPayment(paymentId, biaya, biayaType, card) {
            // Setel input hidden dengan ID payment yang dipilih
            document.getElementById('selectedPayment').value = paymentId;
            // console.log(paymentId);
            let diskon = {{ $diskon ?? 0 }};
            // Menandai card yang dipilih dengan memberikan border highlight
            let cards = document.querySelectorAll('.card');
            cards.forEach(function(c) {
                c.classList.remove('border-success');
            });
            card.classList.add('border-success'); // Menambahkan border saat card dipilih

            // Ambil total yang sudah ada
            let total = parseInt(document.getElementById('totalAmount').textContent.replace(/[^\d]/g, ''));

            let totalWithFee = total;
            let fee = 0;

            // Jika biaya menggunakan rupiah
            if (biayaType === 'rupiah') {
                fee = biaya;
                totalWithFee = (total + fee) - diskon;
            }
            // Jika biaya menggunakan persen
            else if (biayaType === 'persen') {
                fee = (biaya / 100) * total;
                totalWithFee = (total + fee) - diskon;
            }

            // Update tampilan biaya dan total
            document.getElementById('biaya').textContent = fee.toLocaleString();
            document.getElementById('finalTotal').textContent = totalWithFee.toLocaleString();

            document.getElementById('payButton').disabled = false;
        }

        document.addEventListener('DOMContentLoaded', function() {
            let firstPaymentCard = document.querySelector('.card');
            console.log(firstPaymentCard);

            // Pastikan diskon dan variabel lainnya sudah di-render dengan benar
            let diskon = {{ $diskon ?? 0 }};
            let paymentId = {{ $iFee->id ?? 0 }};
            let biaya = {{ $iFee->biaya ?? 0 }};
            let biayaType = '{{ $iFee->biaya_type ?? 'rupiah' }}';

            // Ambil total yang sudah ada
            let total = parseInt(document.getElementById('totalAmount').textContent.replace(/[^\d]/g, ''));

            let totalWithFee = total;
            let fee = 0;

            if (biayaType === 'rupiah') {
                fee = biaya;
                totalWithFee = (total + fee) - diskon;
            } else if (biayaType === 'persen') {
                fee = (biaya / 100) * total;
                totalWithFee = (total + fee) - diskon;
            }

            // Update tampilan biaya dan total
            document.getElementById('biaya').textContent = fee.toLocaleString();
            document.getElementById('finalTotal').textContent = totalWithFee.toLocaleString();

            selectPayment(paymentId, biaya, biayaType, firstPaymentCard);
              document.getElementById('payButton').disabled = true;
        });
    </script>
@endsection
