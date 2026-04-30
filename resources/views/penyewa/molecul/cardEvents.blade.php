<style>
    .modern-event-card {
        border-radius: 20px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        transition: all 0.3s ease;
        background: #fff;
        height: 100%;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    .modern-event-card:hover {
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        transform: translateY(-5px);
    }
    .modern-card-img-wrapper {
        position: relative;
        height: 220px;
        overflow: hidden;
    }
    .modern-card-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }
    .modern-event-card:hover .modern-card-img {
        transform: scale(1.05);
    }
    .modern-status-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(4px);
        padding: 6px 12px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        z-index: 10;
    }
    .modern-status-text {
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .modern-switch {
        position: relative;
        display: inline-block;
        width: 40px;
        height: 22px;
        margin: 0;
    }
    .modern-switch input { 
        opacity: 0;
        width: 0;
        height: 0;
    }
    .modern-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #cbd5e1;
        transition: .4s;
        border-radius: 34px;
    }
    .modern-slider:before {
        position: absolute;
        content: "";
        height: 16px;
        width: 16px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }
    input:checked + .modern-slider {
        background-color: #10b981;
    }
    input:checked + .modern-slider:before {
        transform: translateX(18px);
    }
    .modern-card-title {
        font-size: 18px;
        font-weight: 900;
        color: #1e293b;
        text-transform: uppercase;
        margin-bottom: 20px;
        line-height: 1.3;
    }
    .modern-btn-group {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
    .modern-btn {
        border-radius: 10px;
        font-weight: 800;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 10px 5px;
    }
    .modern-btn-outline {
        border: 2px solid #e2e8f0;
        color: #475569;
        background: transparent;
    }
    .modern-btn-outline:hover {
        border-color: #5b62e4;
        color: #5b62e4;
        background: #f8fafc;
    }
    .modern-btn-primary {
        background: #5b62e4;
        color: white;
        border: 2px solid #5b62e4;
    }
    .modern-btn-primary:hover {
        background: #4a51c9;
        border-color: #4a51c9;
        color: white;
    }
</style>

<div class="row">
    @forelse ($paginate as $events)
        <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
            <div class="modern-event-card">
                <!-- Cover Image & Badge -->
                <div class="modern-card-img-wrapper">
                    <a href="{{ url('dashboard/event/eventDetail/' . $events->uid) }}">
                        <img src="{{ asset('storage/cover/' . $events->cover) }}" alt="{{ $events->event }}" class="modern-card-img" onerror="this.src='https://placehold.co/800x600?text=No+Cover'">
                    </a>
                    
                    @if ($events->konfirmasi !== null)
                    <div class="modern-status-badge">
                        <span class="modern-status-text" id="status-text-{{ $events->uid }}" style="color: {{ $events->status === 'active' ? '#10b981' : '#94a3b8' }}">
                            {{ $events->status === 'active' ? 'Active' : 'Closed' }}
                        </span>
                        <label class="modern-switch">
                            <input type="checkbox" onchange="toggleEventStatus('{{ $events->uid }}')" id="status-toggle-{{ $events->uid }}" {{ $events->status === 'active' ? 'checked' : '' }}>
                            <span class="modern-slider"></span>
                        </label>
                    </div>
                    @endif

                    @if ($events->konfirmasi === null)
                        <div class="position-absolute" style="left: 15px; top: 15px;">
                            <a href="{{ url('dashboard/events/delete/' . $events->uid) }}" class="btn btn-danger btn-sm rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="fe fe-trash"></i>
                            </a>
                        </div>
                    @endif
                </div>

                <!-- Content -->
                <div class="card-body p-4 d-flex flex-column" style="background: white;">
                    <h3 class="modern-card-title text-truncate" title="{{ $events->event }}">
                        {{ $events->event }}
                    </h3>

                    <div class="mt-auto">
                        @if ($events->konfirmasi === null)
                            <button class="btn btn-warning text-white w-100 fw-bold" disabled style="border-radius: 10px; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; padding: 12px;">Menunggu Persetujuan</button>
                        @else
                            <a href="{{ url('/dashboard/event/eventDetail/' . $events->uid) }}" class="btn modern-btn modern-btn-outline w-100 mb-2">
                                Detail Event
                            </a>
                            <div class="modern-btn-group">
                                <a href="{{ url('dashboard/transaksi/' . $events->uid) }}" class="btn modern-btn modern-btn-primary">
                                    Trx Online
                                </a>
                                <a href="{{ url('dashboard/cash/' . $events->uid) }}" class="btn modern-btn modern-btn-primary">
                                    Trx Cash
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="text-center p-5 bg-white rounded" style="border: 2px dashed #e2e8f0; border-radius: 20px !important;">
                <h3 class="text-muted mb-3">Belum ada event</h3>
                <a href="{{url('/dashboard/event/addEvent')}}" class="btn btn-primary"><i class="fa fa-plus-square me-2"></i>Buat Event Baru</a>
            </div>
        </div>
    @endforelse
</div>

<div class="mb-5 mt-4">
    <div class="float-end">
        @if ($paginate->total() > 0)
            <ul class="pagination">
                @if ($paginate->currentPage() === 1)
                    <li class="page-item disabled"><a class="page-link" href="#" tabindex="-1">Prev</a></li>
                @else
                    <li class="page-item"><a class="page-link" href="{{ $paginate->previousPageUrl() }}" tabindex="-1">Prev</a></li>
                @endif

                @foreach ($paginate->getUrlRange(1, $paginate->lastPage()) as $page => $url)
                    <li class="page-item {{ $page == $paginate->currentPage() ? 'active' : '' }}">
                        <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                    </li>
                @endforeach

                <li class="page-item {{ $paginate->hasMorePages() ? '' : 'disabled' }}">
                    <a class="page-link" href="{{ $paginate->nextPageUrl() }}">Next</a>
                </li>
            </ul>
        @endif
    </div>
</div>

<script>
    function toggleEventStatus(uid) {
        // Set CSRF token from meta tag if available
        let token = document.querySelector('meta[name="csrf-token"]');
        let csrfToken = token ? token.getAttribute('content') : '';
        
        // Disable toggle temporarily
        let toggle = document.getElementById('status-toggle-' + uid);
        let textSpan = document.getElementById('status-text-' + uid);
        toggle.disabled = true;

        fetch(`/dashboard/event/toggle-status/${uid}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            toggle.disabled = false;
            if(data.success) {
                if(data.status === 'active') {
                    toggle.checked = true;
                    textSpan.textContent = 'ACTIVE';
                    textSpan.style.color = '#10b981';
                } else {
                    toggle.checked = false;
                    textSpan.textContent = 'CLOSED';
                    textSpan.style.color = '#94a3b8';
                }
            } else {
                alert('Gagal mengubah status: ' + data.message);
                toggle.checked = !toggle.checked; // Revert
            }
        })
        .catch(error => {
            toggle.disabled = false;
            toggle.checked = !toggle.checked; // Revert
            console.error('Error:', error);
            alert('Terjadi kesalahan saat menghubungi server.');
        });
    }
</script>