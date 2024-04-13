
@foreach ($event as $events)
    <div class="col-md-6 col-xl-2 col-sm-6">
        <div class="card">
            <div class="product-grid6">
                <div class="product-image6 p-1">
                    <ul class="icons" style="right: 0; top: 10px">
                        <li>
                            <a href="{{ url('admin/transaksi/' . $events->uid) }}" class="btn btn-primary"> <i
                                    class="fe fe-eye"> </i> </a>
                        </li>
                        <li>
                            <a href="{{ url('admin/events/delete/' . $events->uid) }}" class="delete btn btn-danger">
                                <i class="fe fe-trash"> </i> </a>
                        </li>
                    </ul>
                    <a >
                        <img class="img-fluid br-7 w-100" src="{{ asset('storage/cover/' . $events->cover) }}"
                            alt="img">
                    </a>
                </div>
                <div class="card-body py-0">
                    <div class="product-content text-center">
                        <h1 class="title fw-bold fs-20"><a
                                href="{{ url('/admin/event/eventDetail/' . $events->uid) }}">{{ $events->event }}</a>
                        </h1>
                        <div class=" mb-2 btn-sm {{ $events->konfirmasi != null ? 'btn-primary' : 'btn-danger' }}">
                            {{ $events->konfirmasi != null ? 'Disetujui' : 'Belum disetujui' }}
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endforeach

<div class="mb-5">
    <div class="float-end">
        <ul class="pagination {{ $paginate->total() === 0 ? 'd-none' : '' }} ">
            @if ($paginate->currentPage() === 1)
                <li class="page-item disabled">
                    <a class="page-link" href="#" tabindex="-1">Prev</a>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginate->previousPageUrl() }}" tabindex="-1">Prev</a>
                </li>
            @endif

            @if ($paginate)
                <!-- Loop untuk menampilkan tautan ke setiap halaman -->
                @foreach ($paginate->getUrlRange(1, $paginate->lastPage()) as $page => $url)
                    <li class="page-item {{ $page == $paginate->currentPage() ? 'active' : '' }}">
                        <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                    </li>
                @endforeach
            @else
            @endif
            <li class="page-item {{ $paginate->hasMorePages() ? '' : 'disabled' }}">
                <a class="page-link" href="{{ $paginate->nextPageUrl() }}">Next</a>
            </li>
        </ul>
    </div>
</div>
