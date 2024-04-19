@foreach ($paginate as $events)
    <div class="col-md-6 col-xl-3 col-sm-6">
        <div class="card">
            <div class="product-grid6">
                <div class="product-image6 p-1">
                    <ul class="icons" style="right: 0; top: 10px">
                        @if ($events->konfirmasi === null)
                            <li>
                                <a href="{{ url('dashboard/events/delete/' . $events->uid) }}"
                                    class="delete btn btn-danger">
                                    <i class="fe fe-trash"> </i> </a>
                            </li>
                        @endif

                    </ul>
                    <a href="{{ url('dashboard/event/eventDetail/' . $events->uid) }}">
                        <img class="img-fluid br-7 w-100" src="{{ asset('storage/cover/' . $events->cover) }}"
                            alt="img" style="height: 150px; object-fit: cover">
                    </a>
                </div>
                <div class="card-body pt-2">
                    <div class="product-content text-center">
                        <h1 class="title fw-bold fs-20"><a
                                href="{{ url('/dashboard/event/eventDetail/' . $events->uid) }}">{{ $events->event }}</a>
                        </h1>
                        @if ($events->konfirmasi === null)
                            <button disabled="disabled " class="btn btn-success">Menunggu Persetujuan</button>
                        @else
                            <a href="{{ url('dashboard/transaksi/' . $events->uid) }}" class="btn btn-primary"> <i
                                    class="fe fe-bar-chart-2"></i>E-Commerce</a>
                                    <a href="{{ url('dashboard/cash/' . $events->uid) }}" class="btn btn-secondary"> <i
                                        class="fe fe-bar-chart-2"></i>Cash</a>
                        @endif




                    </div>
                </div>

            </div>
        </div>
    </div>
@endforeach
<div class="mb-5">
    <div class="float-end">
        @if ($paginate->total() > 0)
            <ul class="pagination ">
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
        @endif
    </div>
</div>
