@extends('frontend.index')

@section('content')
    <div class="row">
        <div class="col ">
            <div class="container pt-lg-5 mt-5">
                <h4 class=" mb-0" style="margin-top: 100px">Riwayat Transaksi</h4>
                @if (session('deleteList'))
                    <div class="alert alert-danger">
                        {{ session('deleteList') }}
                    </div>
                @endif
                @if (count($transaksi) > 0)
                    @foreach ($transaksi as $transaksi)
                        <div class="card d-none d-lg-block d-xl-block mt-5">
                            <div class="card-body table-responsive">
                                <table>
                                    <tr>
                                        <td style="width: 20%">
                                            <img src="{{ asset('storage/cover/' . $transaksi->cover) }}" alt=""
                                                class="rounded me-5" style="width: 200px">
                                        </td>
                                        <td style="width: 15%">
                                            <p class="m-0">Ivoice</p>
                                            <h6 class="m-0">{{ $transaksi->invoice }}</h6>
                                        </td>
                                        <td style="width: 15%">
                                            <p class="m-0">Tanggal Transaksi</p>
                                            <h6 class="m-0">
                                                {{ \Carbon\Carbon::parse($transaksi->created_at)->locale('id')->isoFormat('dddd, d-MMMM-Y') }}
                                            </h6>
                                        </td>
                                        <td style="width: 7%">
                                            <p class="m-0">Quatity</p>
                                            <h6 class="m-0"> {{ $transaksi->total_quantity }}</h6>
                                        </td>
                                        <td style="width: 10%">
                                            <p class="m-0">Total</p>
                                            <h6 class="m-0">Rp {{ number_format($transaksi->total_harga ?? 0) }}</h6>
                                        </td>
                                        <td style="width: 10%">
                                            <p class="m-0">Status</p>
                                            <h6 class="m-0">{{ $transaksi->status }}</h6>
                                        </td>
                                        <td style="width: 10%">
                                            <div class="d-flex  align-items-center justify-content-between">
                                                <a href="{{ url('/detail-ticket/' . $transaksi->uid . '/' . Auth::user()->uid) }}"
                                                    class="btn btn-primary ">Detail</a>
                                                @if ($transaksi->status === 'UNPAID')
                                                    <a href="{{ url('/detail-ticket/delete/' . $transaksi->uid . '/' . Auth::user()->uid) }}"
                                                        class="btn btn-danger delete">Delete</a>
                                                @endif

                                            </div>

                                        </td>

                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="card  d-md-block d-sm-block d-xl-none d-lg-none " style="margin-top: 100px">
                            <img src="{{ asset('storage/cover/' . $transaksi->cover) }}" alt="" class=" me-5"
                                style="width: 100%; border-radius:6px">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <p class="m-0">Ivoice</p>
                                    <h6 class="m-0">{{ $transaksi->invoice }}</h6>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <p class="m-0">Tanggal</p>
                                    <h6 class="m-0">
                                        {{ \Carbon\Carbon::parse($transaksi->created_at)->locale('id')->isoFormat('dddd, d-MMMM-Y') }}
                                    </h6>
                                </div>

                                <div class="d-flex justify-content-between align-items-center">
                                    <p class="m-0">Quantity</p>
                                    <h6 class="m-0">{{ $transaksi->total_quantity }}</h6>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <p class="m-0">Total</p>
                                    <h6 class="m-0">Rp {{ number_format($transaksi->total_harga ?? 0) }}</h6>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <p class="m-0">Status</p>
                                    <h6 class="m-0">{{ $transaksi->status }}</h6>
                                </div>
                                <div class="align-items-center">
                                    <a href="{{ url('/detail-ticket/' . $transaksi->uid . '/' . Auth::user()->uid) }}"
                                        class="btn btn-primary w-100 mb-2 mt-1">Detail</a>
                                    @if ($transaksi->status === 'UNPAID')
                                        <a href="{{ url('/detail-ticket/delete/' . $transaksi->uid . '/' . Auth::user()->uid) }}"
                                            class="btn btn-danger delete w-100">Delete</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <p>Tidak ada transaksi...</p>
                @endif



            </div>
        </div>
    </div>
@endsection
