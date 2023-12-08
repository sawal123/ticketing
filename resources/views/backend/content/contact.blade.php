<div class="card">
    <div class="card-header d-flex justify-content-between">

        <h3 class="card-title">Data Penyewa</h3>
        <button type="submit" class="modal-effect btn btn-primary " data-bs-target="#modalAdmin"
            data-bs-effect="effect-sign" data-bs-toggle="modal"><i class="fa fa-plus-square me-2"></i>New Contact</button>


    </div>
    <div class="container mt-2">
        @if (session('delete'))
        <div class="alert alert-danger">
            {{ session('delete') }}
        </div>
    @endif
    @if (session('success'))
        <div class="alert alert-primary">
            {{ session('success') }}
        </div>
    @endif
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="myTable" class=" table table-bordered text-nowrap key-buttons border-bottom">
                <thead class="border-top">
                    <tr>
                        <th class="bg-transparent border-bottom-0" style="width: 5%;">No</th>
                        <th class="bg-transparent border-bottom-0">
                            Sosmed</th>
                        <th class="bg-transparent border-bottom-0">
                            Nama</th>
                        <th class="bg-transparent border-bottom-0">
                            Link</th>
                        <th class="bg-transparent border-bottom-0" style="width: 5%;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($contact as $key => $contacts)
                        <tr class="border-bottom">
                            <td class="text-center">
                                <div class="mt-0 mt-sm-2 d-block">
                                    <h6 class="mb-0 fs-14 fw-semibold">{{ $key + 1 }}</h6>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex">
                                    <span class="avatar bradius"
                                        style="background-image: url({{ asset('storage/icon/' . $contacts->icon) }})"></span>
                                    <div class="ms-3 mt-0 mt-sm-2 d-block">
                                        <h6 class="mb-0 fs-14 fw-semibold">
                                            {{ $contacts->sosmed }}</h6>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex">

                                    <div class="ms-3 mt-0 mt-sm-2 d-block">
                                        <h6 class="mb-0 fs-14 fw-semibold">
                                            {{ $contacts->name }}</h6>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex">
                                    <div class="mt-0 mt-sm-3 d-block">
                                        <h6 class="mb-0 fs-14 fw-semibold">
                                            {{ $contacts->link }}</h6>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="g-2">
                                    <button type="submit" class="btn text-primary btn-sm" data-id="{{ $contacts->id }}"
                                        data-sosmed="{{ $contacts->sosmed }}" data-nama="{{ $contacts->name }}"
                                        data-link="{{ $contacts->link }}" data-bs-target="#upContact"
                                        data-bs-effect="effect-sign" data-bs-toggle="modal"><span
                                            class="fe fe-edit fs-14"></span></button>

                                    <a href="{{ url('admin/delete/contact/' . $contacts->id) }}"
                                        class="btn text-danger btn-sm delete" data-bs-toggle="tooltip"
                                        data-bs-original-title="Delete"><span class="fe fe-trash-2 fs-14"></span></a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    @include('backend.molecul.modalContact')

                </tbody>
            </table>
        </div>
    </div>
</div>
