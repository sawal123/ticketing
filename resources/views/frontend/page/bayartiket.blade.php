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
                                                    $total1 = (int)$harga->quantity * (int)$harga->harga_ticket;
                                                @endphp
                                                Rp{{ number_format($total1 ?? 0) }}
                                            </h6>
                                            
                                        </div>
                                    </div>
                                    <hr>
                                @endforeach


                            </div>
                        </div>

                        {{-- <div class="card mb-2">
                            <h5 class="card-header">Payment Gateway</h5>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <div class="card">
                                            <nav>
                                                <div class="nav nav-tabs" id="nav-tab" role="tablist">
                                                    <button class="nav-link active" id="nav-home-tab" data-bs-toggle="tab"
                                                        data-bs-target="#nav-home" type="button" role="tab"
                                                        aria-controls="nav-home" aria-selected="true">E-wallet</button>

                                                    <button class="nav-link" id="nav-profile-tab" data-bs-toggle="tab"
                                                        data-bs-target="#nav-profile" type="button" role="tab"
                                                        aria-controls="nav-profile" aria-selected="false">Q-RIS</button>

                                                    <button class="nav-link" id="nav-contact-tab" data-bs-toggle="tab"
                                                        data-bs-target="#nav-contact" type="button" role="tab"
                                                        aria-controls="nav-contact" aria-selected="false">Bank
                                                        Transfer</button>
                                                </div>
                                            </nav>
                                            <div class="tab-content" id="nav-tabContent">
                                                <div class="tab-pane fade show active" id="nav-home" role="tabpanel"
                                                    aria-labelledby="nav-home-tab">
                                                    <div class="row p-2">
                                                        <div class="col-12 col-lg-6">
                                                            <div class="card mb-2">
                                                                <label for="radioOption1">
                                                                    <div
                                                                        class="card-body d-flex align-items-center justify-content-evenly p-2">
                                                                        <input type="radio" name="radioOption"
                                                                            id="radioOption1" value="option1"
                                                                            style="width: 30px">
                                                                        <img src="https://karirlab-prod-bucket.s3.ap-southeast-1.amazonaws.com/files/privates/O5RgLOT0Wj9jzmf8stYL8Vg4IhFBHceJgWc8YwdW.png"
                                                                            class=" d-block rounded-circle "
                                                                            style="width: 50px; " alt="Image">
                                                                        <div
                                                                            class="para d-flex align-items-start flex-column">
                                                                            <h6 class="text-end m-0">DANA</h6>
                                                                            <p class="text-end m-0" style="font-size: 12px">
                                                                                Only accept payment from Dana</p>
                                                                        </div>
                                                                    </div>
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <div class="col-12 col-lg-6">
                                                            <div class="card">
                                                                <label for="radioOption2">
                                                                    <div
                                                                        class="card-body d-flex align-items-center justify-content-evenly p-2">
                                                                        <input type="radio" name="radioOption"
                                                                            id="radioOption2" value="option1"
                                                                            style="width: 30px">
                                                                        <img src="https://karirlab-prod-bucket.s3.ap-southeast-1.amazonaws.com/files/privates/O5RgLOT0Wj9jzmf8stYL8Vg4IhFBHceJgWc8YwdW.png"
                                                                            class=" d-block rounded-circle "
                                                                            style="width: 50px; " alt="Image">
                                                                        <div
                                                                            class="para d-flex align-items-start flex-column">
                                                                            <h6 class="text-end m-0">DANA</h6>
                                                                            <p class="text-end m-0" style="font-size: 12px">
                                                                                Only accept payment from Dana</p>
                                                                        </div>
                                                                    </div>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="tab-pane fade" id="nav-profile" role="tabpanel"
                                                    aria-labelledby="nav-profile-tab">...</div>
                                                <div class="tab-pane fade" id="nav-contact" role="tabpanel"
                                                    aria-labelledby="nav-contact-tab">...</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> --}}

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
                                                Rp {{ number_format($fee, 0, ',', '.') }}
                                            </h6>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <p class="text-start m-0" style="font-size: 16px; font-weight: bold">Total</p>
                                            <h6 class="text-end m-0" style="font-size: 18px; font-weight: 900">Rp
                                                {{ number_format($total += $fee, 0, ',', '.') }}
                                            </h6>
                                        </div>
                                        @if($cart->status === 'Belum Bayar')
                                        <form action="{{url('/paynow')}}" method="post" enctype="multipart/form-data">
                                            @csrf
                                            <input type="hidden" name="invoice" value="{{$cart->invoice}}">
                                            <input type="hidden" name="person" value="{{Auth::user()->uid}}">
                                            <input type="hidden" name="event" value="{{$uid}}">
                                            <input type="hidden" value="{{$total}}" name="amount">
                                            <button type="submit" class="btn btn-primary w-100 mt-3">Bayar Sekarang</button>
                                        </form>
                                        @else
                                        <button type="submit" class="btn btn-success w-100 mt-3">{{$cart->status}}</button>
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
