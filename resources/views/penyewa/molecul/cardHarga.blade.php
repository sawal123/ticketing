<style>
    .custom-toggle-btn {
        appearance: none;
        -webkit-appearance: none;
        width: 44px;
        height: 24px;
        background-color: #cbd5e1;
        border-radius: 20px;
        position: relative;
        cursor: pointer;
        outline: none;
        margin: 0;
        transition: background-color 0.3s;
    }

    .custom-toggle-btn:checked {
        background-color: #5c6fff;
    }

    .custom-toggle-btn::after {
        content: '';
        position: absolute;
        top: 2px;
        left: 2px;
        width: 20px;
        height: 20px;
        background-color: white;
        border-radius: 50%;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
        transition: transform 0.3s;
    }

    .custom-toggle-btn:checked::after {
        transform: translateX(20px);
    }

    /* Tambahan CSS Khusus untuk Adaptive Card Background */
    .harga-card-bg {
        background-color: #f1f4f8;
        /* Default Light Mode */
    }

    /* Jika template Anda menggunakan atribut data-theme="dark" atau class .dark-mode di tag body/html */
    [data-theme="dark"] .harga-card-bg,
    body.dark-mode .harga-card-bg,
    .dark-theme .harga-card-bg {
        background-color: #1e293b !important;
        /* Warna gelap lembut untuk background card */
        border: 1px solid #334155 !important;
        /* Border tipis agar lebih elegan */
    }
</style>

<div class="row w-100 mx-0">
    @if ($harga->count())
        @foreach ($harga as $h)
            <div class="col-12 col-sm-6 col-md-4 col-lg-3 mb-4 px-2">

                {{-- PERBAIKAN: Hapus style="background-color:..." dan ganti dengan class khusus .harga-card-bg --}}
                <div class="card h-100 border-0 harga-card-bg" style="border-radius: 12px;">
                    <div class="card-body p-4 d-flex flex-column">

                        <div class="d-flex justify-content-between align-items-start mb-2">
                            {{-- PERBAIKAN: Hapus class text-dark, biarkan template yang mengatur warnanya --}}
                            <h6 class="mb-0" style="font-weight: 500; font-size: 1rem; padding-right: 10px;">
                                {{ strtoupper($h->kategori) }}
                            </h6>

                            @if(isset($h->status) && $h->status === 'active')
                                <span class="badge"
                                    style="background-color: #5c6fff; padding: 5px 10px; border-radius: 6px; font-weight: 500; color: white;">Active</span>
                            @else
                                {{-- PERBAIKAN: Ganti bg-secondary menjadi style abu-abu netral yang cocok di light/dark mode --}}
                                <span class="badge"
                                    style="background-color: #64748b; padding: 5px 10px; border-radius: 6px; font-weight: 500; color: white;">Inactive</span>
                            @endif
                        </div>

                        {{-- PERBAIKAN: Hapus class text-dark --}}
                        <h4 class="mb-4" style="font-weight: 700;">
                            Rp {{ number_format($h->harga, 0, ',', '.') }}
                        </h4>

                        @php
                            $terjual = $terjualPerHarga[$h->id] ?? 0;
                        @endphp

                        {{-- PERBAIKAN: Hapus class text-dark --}}
                        <div class="d-flex mb-4" style="font-size: 0.85rem;">
                            <span class="me-4 opacity-75">
                                Qty Tiket : <strong>{{ $h->qty }}</strong>
                            </span>
                            <span class="opacity-75">
                                Terjual : <strong>{{ $terjual }}</strong>
                            </span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-auto">

                            <form action="{{ url("dashboard/hargas/toggle-status/{$h->id}") }}" method="POST"
                                class="toggle-form m-0">
                                @csrf
                                <input class="custom-toggle-btn" type="checkbox" onchange="this.form.submit()"
                                    @checked(isset($h->status) && $h->status === 'active')>
                            </form>

                            <div class="d-flex" style="gap: 8px;">
                                <a href="{{ url("dashboard/hargas/delete/{$h->id}") }}"
                                    class="btn d-flex align-items-center justify-content-center p-0"
                                    style="width: 38px; height: 38px; background-color: #ff1a55; border-radius: 8px; border: none;">
                                    <i class="fa fa-trash text-white"></i>
                                </a>

                                <button type="button" data-bs-toggle="modal" data-bs-target="#updateHarga"
                                    class="btn d-flex align-items-center justify-content-center p-0"
                                    style="width: 38px; height: 38px; background-color: #5c6fff; border-radius: 8px; border: none;"
                                    data-kategori="{{ $h->kategori }}" data-qty="{{ $h->qty }}" data-harga="{{ $h->harga }}"
                                    data-id="{{ $h->id }}">
                                    <i class="fa fa-edit text-white"></i>
                                </button>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    @else
        <div class="col-12 px-2">
            <p class="text-muted mt-2">Tidak ada harga...</p>
        </div>
    @endif
</div>

{{-- SCRIPT SCROLL POSISI TETAP SAMA --}}
<script>
    document.addEventListener("DOMContentLoaded", function (event) {
        let scrollpos = sessionStorage.getItem('scrollpos');
        if (scrollpos) {
            window.scrollTo(0, scrollpos);
            sessionStorage.removeItem('scrollpos');
        }
    });

    window.addEventListener("beforeunload", function (e) {
        sessionStorage.setItem('scrollpos', window.scrollY);
    });
</script>