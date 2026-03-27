@extends('penyewa.app')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Dashboard Staff</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Staff</li>
            </ol>
        </div>
    </div>
    <!-- PAGE-HEADER END -->

    <!-- ROW-1 OPEN -->
    <div class="row row-sm">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header " style="display: inline">

                    <div class="row">
                        <div class="col">
                            <div class="d-flex justify-content-between align-items-center w-full">
                                <h3 class="card-title">File Export</h3>
                                <button type="submit" class="btn btn-primary  my-2" data-bs-target="#modalStaff"
                                    data-bs-effect="effect-sign" data-bs-toggle="modal"><i
                                        class="fa fa-plus-square me-2"></i>Tambah Staff</button>
                                @include('penyewa.page.staff.modal-new')
                            </div>
                            @if ($errors->any())
                                <script>
                                    document.addEventListener("DOMContentLoaded", function() {
                                        // Asumsi kamu menggunakan Bootstrap 5
                                        var myModal = new bootstrap.Modal(document.getElementById('modalStaff'));
                                        myModal.show();
                                    });
                                </script>
                            @endif
                        </div>

                    </div>

                </div>



                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-primary">
                            {{ session('success') }}
                        </div>
                    @endif
                    <div class="table-responsive">
                        <table id="tableStaff" class="table table-bordered text-nowrap key-buttons border-bottom">
                            <thead>
                                <tr>
                                    <th class="border-bottom-0" style="width: 5%">No</th>
                                    <th class="border-bottom-0">Nama Staff</th>
                                    <th class="border-bottom-0">Email</th>
                                    <th class="border-bottom-0">No. HP</th>
                                    <th class="border-bottom-0 text-center">Status</th>
                                    <th class="border-bottom-0 text-center" style="width: 15%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Asumsi variabel dari StaffController adalah $staffs --}}
                                @forelse ($staffs as $key => $staff)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="{{ asset('storage/' . $staff->gambar) }}" alt="img"
                                                    class="avatar avatar-sm me-2 rounded-circle"
                                                    onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($staff->name) }}&background=random'">
                                                <span class="fw-bold">{{ $staff->name }}</span>
                                            </div>
                                        </td>
                                        <td>{{ $staff->email }}</td>
                                        <td>{{ $staff->nomor !== '-' ? $staff->nomor : 'Belum diisi' }}</td>
                                        <td class="text-center">
                                            @if ($staff->email_verified_at)
                                                <span class="badge bg-success">Aktif</span>
                                            @else
                                                <span class="badge bg-warning text-dark">Pending (Belum Verifikasi)</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-primary btn-edit-staff"
                                                data-bs-toggle="modal" data-bs-target="#modalEditStaff"
                                                data-uid="{{ $staff->uid }}" data-name="{{ $staff->name }}"
                                                data-email="{{ $staff->email }}"
                                                data-url="{{ route('staff.update', $staff->uid) }}"> {{-- Titip URL di sini --}}
                                                <i class="fa fa-edit"></i> Edit
                                            </button>

                                            <a href="{{ url('dashboard/staff/delete/' . $staff->uid) }}" class="delete">
                                                <button type="button" class="btn btn-sm btn-danger" title="Hapus Staff">
                                                    <i class="fa fa-trash text-white"></i> Hapus
                                                </button>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            Belum ada data staff. Silakan tambahkan staff baru.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        @include('penyewa.page.staff.modal-update')

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
