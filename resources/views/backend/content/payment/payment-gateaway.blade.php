@extends('backend.app')

@section('content')
    <div class="main-container container-fluid">

        <!-- PAGE-HEADER -->
        <div class="page-header">
            <h1 class="page-title">Payment</h1>
            <div>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="javascript:void(0)">Admin</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Payment</li>
                </ol>
            </div>
        </div>
        <!-- PAGE-HEADER END -->
        @if (session('success'))
            <div class="alert alert-primary">
                {{ session('success') }}
            </div>
        @endif
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xl-12">

                <div class="card">
                    <div class="card-body">
                        <div class="d-flex mb-2 justify-content-between">
                            <div class=""></div>
                            <button type="submit" class="modal-effect btn btn-primary "
                                data-bs-target="#modalPaymentGateway" data-bs-effect="effect-sign" data-bs-toggle="modal"><i
                                    class="fa fa-plus-square me-2"></i>New
                                Contact</button>
                        </div>
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Payment</th>
                                    <th scope="col">Biaya</th>
                                    <th scope="col">Type</th>
                                    <th scope="col">Icon</th>
                                    <th scope="col">Is Active</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($payment as $index => $gateway)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $gateway->payment }}</td>
                                        <td>
                                            @if ($gateway->biaya_type == 'persen')
                                                {{ $gateway->biaya }}%
                                            @else
                                                Rp{{ number_format($gateway->biaya, 0, ',', '.') }}
                                            @endif
                                        </td>
                                        <td>{{ ucfirst($gateway->biaya_type) }}</td>
                                        <td>
                                            @if ($gateway->icon)
                                                <img src="{{ asset('storage/' . $gateway->icon) }}" alt="icon"
                                                    width="40">
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if ($gateway->is_active)
                                                <span class="badge bg-success">Aktif</span>
                                            @else
                                                <span class="badge bg-secondary">Nonaktif</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <button class="btn btn-sm btn-primary btn-edit"
                                                    data-id="{{ $gateway->id }}" data-payment="{{ $gateway->payment }}"
                                                    data-biaya="{{ $gateway->biaya }}"
                                                    data-biaya_type="{{ $gateway->biaya_type }}"
                                                    data-is_active="{{ $gateway->is_active }}">
                                                    Edit
                                                </button>

                                                <form action="{{ route('payments.destroy', $gateway->id) }}" method="POST"
                                                    class="delete-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm ">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- #modal --}}
    <div class="modal fade" id="modalPaymentGateway">
        <div class="modal-dialog modal-dialog-centered text-start" role="document">
            <div class="modal-content modal-content-demo">
                <div class="modal-header">
                    <h6 class="modal-title">Tambah Payment Gateway</h6>
                    <button aria-label="Close" class="btn-close" data-bs-dismiss="modal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="paymentGatewayForm" action="{{ url('admin/payment-gateway/store') }}" method="post"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit-id">
                        <div class="row mb-4">
                            <label class="col-md-3 form-label">Nama Payment:</label>
                            <div class="col-md-9">
                                <input type="text" class="form-control" name="payment" id="edit-payment"
                                    placeholder="Contoh: DANA, BRI, OVO" required>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label class="col-md-3 form-label">Category:</label>
                            <div class="col-md-9">
                                <select class="form-select " aria-label="Default select example" name="category">
                                    <option value="ewallet">Ewallet</option>
                                    <option value="va">Virtual Account</option>
                                    <option value="transfer">Transfer</option>
                                    <option value="credit_card">Credit Card</option>
                                </select>
                            </div>

                        </div>
                        <div class="row mb-4">
                            <label class="col-md-3 form-label">Biaya Admin:</label>
                            <div class="col-md-9">
                                <input type="number" class="form-control" name="biaya" id="edit-biaya"
                                    placeholder="Contoh: 4500 atau 1.5" step="0.01" required>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <label class="col-md-3 form-label">Tipe Biaya:</label>
                            <div class="col-md-9">
                                <select class="form-control" name="biaya_type" id="edit-biaya_type" required>
                                    <option value="rupiah">Rupiah</option>
                                    <option value="persen">Persen</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <label class="col-md-3 form-label">Icon:</label>
                            <div class="col-md-9">
                                <input type="file" class="form-control" name="icon" accept="image/*">
                            </div>
                        </div>

                        <div class="row mb-4">
                            <label class="col-md-3 form-label">Aktif?</label>
                            <div class="col-md-9">
                                <select class="form-control" name="is_active" id="edit-is_active">
                                    <option value="1" selected>Ya</option>
                                    <option value="0">Tidak</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    {{-- End Modal --}}

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = new bootstrap.Modal(document.getElementById('modalPaymentGateway'));

            document.querySelectorAll('.btn-edit').forEach(button => {
                button.addEventListener('click', function() {
                    document.getElementById('edit-id').value = this.dataset.id;
                    document.getElementById('edit-payment').value = this.dataset.payment;
                    document.getElementById('edit-biaya').value = this.dataset.biaya;
                    document.getElementById('edit-biaya_type').value = this.dataset.biaya_type;
                    document.getElementById('edit-is_active').value = this.dataset.is_active;

                    // Ubah action menjadi ke route update (jika pakai REST)
                    document.getElementById('paymentGatewayForm').action =
                        `/admin/payment-gateway/update/${this.dataset.id}`;

                    modal.show();
                });
            });
        });

        document.getElementById('modalPaymentGateway').addEventListener('hidden.bs.modal', function() {
            const form = document.getElementById('paymentGatewayForm');
            // Reset form fields
            form.reset();
            // Kosongkan input hidden ID
            document.getElementById('edit-id').value = '';
            // Reset action ke route store
            form.action = `/admin/payment-gateway/store`;
        });
    </script>
@endsection
