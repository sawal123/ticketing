@foreach ($paginate as $events)
    <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
        <div class="card shadow-sm border-0 d-flex flex-column h-100" style="border-radius: 12px; overflow: hidden;">
            <div class="position-relative">
                <a href="{{ url('dashboard/event/eventDetail/' . $events->uid) }}">
                    <img src="{{ asset('storage/cover/' . $events->cover) }}" alt="{{ $events->event }}" class="w-100"
                        style="height: 240px; object-fit: cover;">
                </a>
                @if ($events->konfirmasi === null)
                    <div class="position-absolute" style="right: 15px; top: 15px;">
                        <a href="{{ url('dashboard/events/delete/' . $events->uid) }}"
                            class="btn btn-danger btn-sm rounded-circle shadow-sm"
                            style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                            <i class="fe fe-trash"></i>
                        </a>
                    </div>
                @endif
            </div>
            <div class="card-body p-4 d-flex flex-column">
                <h4 class="fw-bold mb-4 text-dark text-truncate" style="font-size: 18px; letter-spacing: -0.5px;">
                    <a href="{{ url('/dashboard/event/eventDetail/' . $events->uid) }}"
                        class="text-dark">{{ $events->event }}</a>
                </h4>

                <div class="mt-auto">
                    @if ($events->konfirmasi === null)
                        <button class="btn btn-warning text-white w-100 fw-bold" disabled style="border-radius: 8px;">Menunggu
                            Persetujuan</button>
                    @else
                        <div class="d-flex gap-2">
                            <a href="{{ url('/dashboard/event/eventDetail/' . $events->uid) }}"
                                class="btn border shadow-none flex-grow-1"
                                style="border-color: #e2e8f0; color: #1a1a1a; font-weight: 600; border-radius: 6px; font-size: 13px; padding: 8px 0;">
                                Detail Event
                            </a>
                            <a href="{{ url('dashboard/transaksi/' . $events->uid) }}"
                                class="btn text-white shadow-none flex-grow-1"
                                style="background-color: #5b62e4; font-weight: 600; border-radius: 6px; font-size: 13px; padding: 8px 0;">
                                Trx Online
                            </a>
                            <a href="{{ url('dashboard/cash/' . $events->uid) }}" class="btn text-white shadow-none flex-grow-1"
                                style="background-color: #5b62e4; font-weight: 600; border-radius: 6px; font-size: 13px; padding: 8px 0;">
                                Trx Cash
                            </a>
                        </div>
                    @endif
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