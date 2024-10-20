<table id="tablePenyewa" class="table table-bordered text-nowrap key-buttons border-bottom">
    <thead>
        <tr>
            <th class="border-bottom-0">No</th>
            <th class="border-bottom-0">Invoice</th>
            <th class="border-bottom-0" style="width: 10%">Event</th>
            <th class="border-bottom-0">Tanggal</th>
            <th class="border-bottom-0">Name</th>
            <th class="border-bottom-0">Email</th>
            <th class="border-bottom-0">Qty</th>
            <th class="border-bottom-0">Jmlh</th>
            <th class="border-bottom-0">Action</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($cart as $index => $ca)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $ca->invoice }}</td>
                <td>{{ strlen($ca->event > 10) ? substr($ca->event, 0, 15) . '...' : $ca->event }}
                <td>{{ $ca->created_at }}</td>
                <td>{{ $ca->name }}</td>
                <td>{{ $ca->email }}</td>
                <td>
                    <a class="modal-effect btn btn-primary-light d-grid mb-3"
                        data-bs-effect="effect-scale" data-bs-toggle="modal"
                        href="#modalCashQty{{ $index }}">{{ $ca->total_quantity }}
                        Ticket</a>

                    <div class="modal fade" id="modalCashQty{{ $index }}">

                        <div class="modal-dialog modal-dialog-centered text-center"
                            role="document">
                            <div class="modal-content modal-content-demo">
                                <div class="modal-header">
                                    <h6 class="modal-title">Detail Ticket</h6><button
                                        aria-label="Close" class="btn-close"
                                        data-bs-dismiss="modal"><span
                                            aria-hidden="true">&times;</span></button>
                                </div>
                                <div class="modal-body">
                                    @php
                                        $KHarga = '';
                                        $Qty = '';
                                    @endphp
                                    @foreach ($qtyTiket as $qt)
                                        @if ($qt->uid === $ca->uid)
                                            <div class="d-flex justify-content-between">
                                                <p>{{ $KHarga = $qt->kategori_harga }} </p>
                                                <p>{{ $Qty = $qt->quantity }} </p>
                                            </div>
                                        @endif
                                    @endforeach

                                </div>
                                <div class="modal-footer">
                                    <button class="btn btn-light"
                                        data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </td>
                <td>Rp {{ number_format($ca->total_harga, 0, ',', '.') }}</td>

                <td>
                    <div class="g-2">


                        <button type="button" class="btn text-primary btn-sm "
                            data-bs-toggle="modal" data-bs-effect="effect-sign"
                            data-bs-target="#modalEditCash" data-bs-original-title="Edit"
                            data-uid="{{ $ca->uid }}"
                            data-invoice="{{ $ca->invoice }}"
                            data-name="{{ $ca->name }}"
                            data-kharga="{{ $KHarga }}" data-qty="{{ $Qty }}"
                            data-email="{{ $ca->email }}"><span
                                class="fa fa-pencil fs-14"></span></button>

                        <a href="{{ url('admin/deleteTransksi/' . $ca->uid) }}"
                            class="btn text-danger btn-sm delete" data-bs-toggle="tooltip"
                            data-bs-original-title="Delete"><span
                                class="fe fe-trash-2 fs-14"></span></a>
                    </div>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>


<div class="modal fade" id="modalEditCash">
    <div class="modal-dialog modal-dialog-centered text-center" role="document">
        <div class="modal-content modal-content-demo">
            <div class="modal-header">
                <h6 class="modal-title">Edit Cash</h6><button aria-label="Close" class="btn-close"
                    data-bs-dismiss="modal"><span aria-hidden="true">&times;</span></button>
            </div>
            <form action="{{ url('admin/addSlide') }}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <input type="hidden" readonly class="form-control" id="uidCash" name="uidCash" required>
                    <div class="row mb-4">
                        <label class="col-md-3 form-label">INVOICE :</label>
                        <div class="col-md-9">
                            <input type="text" readonly class="form-control" id="invoice" name="invoice"
                                required>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-md-3 form-label">Name :</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" id="name" name="name"
                                placeholder="Masukan Name.." required>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-md-3 form-label">Email :</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" id="email" name="email"
                                placeholder="Masukan Email.." required>
                        </div>
                    </div>
                    <div class="row mb-4 p-3">
                        <div class="card  p-0">

                            <div class="card-body text-start">
                                <p class="fw-bold ">Tiket Sekarang</p>
                                <div class="d-flex gap-4">
                                    <p id="KHarga"></p>
                                    <div class="d-flex  gap-2">Qty : <p id="qty"></p>
                                    </div>
                                </div>
                                <div class="">
                                    <input type="checkbox" class="form-check-input" name="tukar" id="tukar">
                                    <label class="form-check-label" for="tukar">
                                        Tukar Tiket?
                                    </label>
                                </div>
                            </div>
                           <div class="tes d-none">
                            <p>Tes Tukar</p>
                           </div>

                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <button class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>
