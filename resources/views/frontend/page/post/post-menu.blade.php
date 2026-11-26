<section class="events-wrap">

    @if (count($event))
        <div class="search-header-bar">
            <h1>Hasil Pencarian Event</h1>
            <p>
                Menampilkan {{ count($event) }} event
                @if (isset($search) && $search)
                    untuk kata kunci <strong>"{{ $search }}"</strong>
                @endif
            </p>

            @if (isset($categories) && $categories->isNotEmpty())
                <div class="category-chips-list" style="justify-content: flex-start; margin-top: 16px;">
                    <a href="{{ url('/search') }}" class="category-chip-pill {{ !isset($search) || !$search ? 'active' : '' }}">
                        <span class="chip-dot"></span>
                        Semua Kategori
                    </a>
                    @foreach ($categories as $cat)
                        <a href="{{ url('/search/' . ($cat->slug ?: $cat->name)) }}"
                           class="category-chip-pill {{ isset($search) && strtolower($search) === strtolower($cat->name) ? 'active' : '' }}">
                            <span class="chip-dot"></span>
                            {{ $cat->name }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="event-grid">
            @foreach ($event as $events)
                <a href="{{ url('/ticket/' . $events->slug) }}" class="event-card" style="transition-delay:.1s">

                    <!-- IMAGE -->
                    <div class="card-img">
                        <div class="img-bg"
                            style="background-image: url('{{ asset('/storage/cover/' . $events->cover) }}'); 
                      filter: {{ $events->status === 'close' ? 'grayscale(100%)' : 'none' }};">
                        </div>

                        <div class="img-glow"></div>

                        <!-- STATUS -->
                        <span
                            class="card-status 
                {{ $events->status === 'close' ? 'status-close' : 'status-open' }}">
                            {{ $events->status === 'close' ? 'Close' : 'On Sale' }}
                        </span>

                        <!-- LOKASI -->
                        <div class="card-loc">
                            <svg viewBox="0 0 16 16" fill="none">
                                <path
                                    d="M8 1.5C5.5 1.5 3.5 3.5 3.5 6c0 3.5 4.5 8.5 4.5 8.5S12.5 9.5 12.5 6c0-2.5-2-4.5-4.5-4.5z"
                                    stroke="currentColor" stroke-width="1.4" />
                                <circle cx="8" cy="6" r="1.5" stroke="currentColor"
                                    stroke-width="1.4" />
                            </svg>
                            {{ $events->alamat }}
                        </div>
                    </div>

                    <!-- BODY -->
                    <div class="card-body">
                        <div class="card-title">{{ $events->event }}</div>

                        <div class="card-meta">
                            <div class="meta-row">
                                <svg viewBox="0 0 16 16" fill="none">
                                    <path
                                        d="M8 1.5C5.5 1.5 3.5 3.5 3.5 6c0 3.5 4.5 8.5 4.5 8.5S12.5 9.5 12.5 6c0-2.5-2-4.5-4.5-4.5z"
                                        stroke="currentColor" stroke-width="1.4" />
                                    <circle cx="8" cy="6" r="1.5" stroke="currentColor"
                                        stroke-width="1.4" />
                                </svg>
                                {{ $events->alamat }}
                            </div>

                            <div class="meta-row">
                                <svg viewBox="0 0 16 16" fill="none">
                                    <rect x="2" y="3" width="12" height="11" rx="2" stroke="currentColor"
                                        stroke-width="1.3" />
                                    <path d="M5 1.5v3M11 1.5v3M2 7h12" stroke="currentColor" stroke-width="1.3"
                                        stroke-linecap="round" />
                                </svg>
                                {{ date('Y-m-d H:i', strtotime($events->tanggal)) }}
                            </div>
                        </div>

                        <!-- FOOTER -->
                        <div class="card-footer">
                            @php
                                $displayHarga = ($events->relationLoaded('hargas') && $events->hargas->isNotEmpty())
                                    ? $events->hargas->sortBy(fn ($h) => $h->effectivePrice())->first()
                                    : $events->harga;
                            @endphp
                            <div>
                                <div class="price-from">Start From</div>

                                @if ($displayHarga)
                                    @if ($displayHarga->hasActivePromo())
                                        <div style="display:flex;align-items:center;gap:6px;margin-top:2px;">
                                            <span style="font-size:12px;text-decoration:line-through;opacity:0.6;">
                                                Rp {{ number_format($displayHarga->harga, 0, ',', '.') }}
                                            </span>
                                            <span style="display:inline-flex;align-items:center;padding:2px 7px;border-radius:999px;background:#ef4444;color:#ffffff;font-size:10px;font-weight:800;line-height:1.2;">
                                                -{{ $displayHarga->promoDiscountPercent() }}%
                                            </span>
                                        </div>
                                    @endif
                                    <div class="price-val">
                                        Rp {{ number_format($displayHarga->effectivePrice(), 0, ',', '.') }}
                                    </div>
                                @elseif (isset($harga))
                                    @php $found = false; @endphp
                                    @foreach ($harga as $hargas)
                                        @if ($hargas->uid === $events->uid)
                                            @if ($hargas->hasActivePromo())
                                                <div style="display:flex;align-items:center;gap:6px;margin-top:2px;">
                                                    <span style="font-size:12px;text-decoration:line-through;opacity:0.6;">
                                                        Rp {{ number_format($hargas->harga, 0, ',', '.') }}
                                                    </span>
                                                    <span style="display:inline-flex;align-items:center;padding:2px 7px;border-radius:999px;background:#ef4444;color:#ffffff;font-size:10px;font-weight:800;line-height:1.2;">
                                                        -{{ $hargas->promoDiscountPercent() }}%
                                                    </span>
                                                </div>
                                            @endif
                                            <div class="price-val">
                                                Rp {{ number_format($hargas->effectivePrice(), 0, ',', '.') }}
                                            </div>
                                            @php $found = true; @endphp
                                            @break
                                        @endif
                                    @endforeach

                                    @if (!$found)
                                        <div class="price-na">Ticket Belum Tersedia</div>
                                    @endif
                                @else
                                    <div class="price-na">Ticket Belum Tersedia</div>
                                @endif

                                <div class="card-by">By: {{ $events->user?->name ?? $events->name ?? 'Organizer' }}</div>
                            </div>

                            <span
                                class="btn-beli 
                  {{ $events->status === 'close' ? 'disabled' : '' }}">
                                {{ $events->status === 'close' ? 'Close' : 'Beli' }}
                            </span>
                        </div>
                    </div>

                </a>
            @endforeach
        </div>
    @else
        <!-- REDESIGNED EMPTY STATE -->
        <div class="empty-state-card">
            @php
                $emptyImageUrl = ($setting && filled($setting->nodata_image))
                    ? asset('storage/setting/' . $setting->nodata_image)
                    : asset('/storage/setting/nodata.svg');
                $emptyTitle = ($setting && filled($setting->nodata_title))
                    ? $setting->nodata_title
                    : 'Tidak Ada Event yang Ditemukan';
                $emptySubtitle = ($setting && filled($setting->nodata_subtitle))
                    ? $setting->nodata_subtitle
                    : (isset($search) && $search
                        ? 'Kami tidak dapat menemukan event dengan kata kunci "' . e($search) . '". Coba cari dengan kata kunci lain atau pilih kategori di bawah.'
                        : 'Saat ini belum ada event yang tersedia untuk kriteria pencarian Anda.');
            @endphp

            <div class="empty-state-img-wrap">
                <img src="{{ $emptyImageUrl }}" class="empty-state-img" alt="Event Tidak Ditemukan">
            </div>

            <h2 class="empty-state-title">{{ $emptyTitle }}</h2>
            <p class="empty-state-desc">{{ $emptySubtitle }}</p>

            <!-- In-Page Search Bar -->
            <form action="{{ url('/cari') }}" method="GET" class="empty-state-search-form">
                <div class="empty-search-input-group">
                    <svg class="search-icon" viewBox="0 0 20 20" fill="none">
                        <path d="M9 17A8 8 0 1 0 9 1a8 8 0 0 0 0 16zM19 19l-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <input type="text" name="cari" value="{{ $search ?? '' }}" placeholder="Cari event, konser, festival, atau kota..." autocomplete="off" class="empty-search-input">
                    @if (isset($search) && $search)
                        <a href="{{ url('/search') }}" class="clear-search-btn" title="Hapus pencarian">
                            <svg viewBox="0 0 16 16" fill="currentColor">
                                <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                            </svg>
                        </a>
                    @endif
                    <button type="submit" class="empty-search-submit">Cari</button>
                </div>
            </form>

            <!-- Action Buttons -->
            <div class="empty-state-actions">
                <a href="{{ url('/') }}" class="btn-empty-primary">
                    <svg viewBox="0 0 20 20" fill="none" class="btn-icon">
                        <path d="M3 9.5L10 3l7 6.5V17a1 1 0 0 1-1 1h-4v-5H8v5H4a1 1 0 0 1-1-1V9.5z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                    </svg>
                    Kembali ke Beranda
                </a>
                @if (isset($search) && $search)
                    <a href="{{ url('/search') }}" class="btn-empty-secondary">
                        <svg viewBox="0 0 20 20" fill="none" class="btn-icon">
                            <rect x="3" y="3" width="14" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/>
                            <path d="M3 8h14M8 3v14" stroke="currentColor" stroke-width="1.8"/>
                        </svg>
                        Lihat Semua Event
                    </a>
                @endif
            </div>

            <!-- Master Category Chips -->
            @if (isset($categories) && $categories->isNotEmpty())
                <div class="empty-category-section">
                    <span class="category-heading">Jelajahi Berdasarkan Kategori Master</span>
                    <div class="category-chips-list">
                        @foreach ($categories as $cat)
                            <a href="{{ url('/search/' . ($cat->slug ?: $cat->name)) }}" class="category-chip-pill">
                                <span class="chip-dot"></span>
                                {{ $cat->name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- RECOMMENDED ACTIVE EVENTS -->
        @if (isset($recommendedEvents) && $recommendedEvents->isNotEmpty())
            <div class="recommended-events-section mt-5 pt-4">
                <div class="section-top">
                    <div>
                        <div class="section-label">Rekomendasi Pilihan</div>
                        <h2>Event Populer Lainnya</h2>
                        <p>Temukan event menarik lainnya yang sedang aktif dan siap kamu tonton</p>
                    </div>
                    <a href="{{ url('/') }}" class="btn-viewall">
                        Ke Beranda
                        <svg viewBox="0 0 16 16" fill="none">
                            <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                </div>

                <div class="event-grid">
                    @foreach ($recommendedEvents as $key => $recEvent)
                        <a href="{{ url('/ticket/' . $recEvent->slug) }}" class="event-card" style="transition-delay:{{ 0.05 + $key * 0.05 }}s">
                            <!-- IMAGE -->
                            <div class="card-img">
                                <div class="img-bg"
                                    style="background-image: url('{{ asset('/storage/cover/' . $recEvent->cover) }}');">
                                </div>
                                <div class="img-glow"></div>
                                <span class="card-status status-open">On Sale</span>
                                <div class="card-loc">
                                    <svg viewBox="0 0 16 16" fill="none">
                                        <path
                                            d="M8 1.5C5.5 1.5 3.5 3.5 3.5 6c0 3.5 4.5 8.5 4.5 8.5S12.5 9.5 12.5 6c0-2.5-2-4.5-4.5-4.5z"
                                            stroke="currentColor" stroke-width="1.4" />
                                        <circle cx="8" cy="6" r="1.5" stroke="currentColor" stroke-width="1.4" />
                                    </svg>
                                    {{ $recEvent->alamat }}
                                </div>
                            </div>

                            <!-- BODY -->
                            <div class="card-body">
                                <div class="card-title">{{ $recEvent->event }}</div>
                                <div class="card-meta">
                                    <div class="meta-row">
                                        <svg viewBox="0 0 16 16" fill="none">
                                            <path
                                                d="M8 1.5C5.5 1.5 3.5 3.5 3.5 6c0 3.5 4.5 8.5 4.5 8.5S12.5 9.5 12.5 6c0-2.5-2-4.5-4.5-4.5z"
                                                stroke="currentColor" stroke-width="1.4" />
                                            <circle cx="8" cy="6" r="1.5" stroke="currentColor" stroke-width="1.4" />
                                        </svg>
                                        {{ $recEvent->alamat }}
                                    </div>
                                    <div class="meta-row">
                                        <svg viewBox="0 0 16 16" fill="none">
                                            <rect x="2" y="3" width="12" height="11" rx="2" stroke="currentColor" stroke-width="1.3" />
                                            <path d="M5 1.5v3M11 1.5v3M2 7h12" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" />
                                        </svg>
                                        {{ date('Y-m-d H:i', strtotime($recEvent->tanggal)) }}
                                    </div>
                                </div>

                                <!-- FOOTER -->
                                <div class="card-footer">
                                    @php
                                        $displayHarga = ($recEvent->relationLoaded('hargas') && $recEvent->hargas->isNotEmpty())
                                            ? $recEvent->hargas->sortBy(fn ($h) => $h->effectivePrice())->first()
                                            : $recEvent->harga;
                                    @endphp
                                    <div>
                                        <div class="price-from">Start From</div>
                                        @if ($displayHarga)
                                            @if ($displayHarga->hasActivePromo())
                                                <div style="display:flex;align-items:center;gap:6px;margin-top:2px;">
                                                    <span style="font-size:12px;text-decoration:line-through;opacity:0.6;">
                                                        Rp {{ number_format($displayHarga->harga, 0, ',', '.') }}
                                                    </span>
                                                    <span style="display:inline-flex;align-items:center;padding:2px 7px;border-radius:999px;background:#ef4444;color:#ffffff;font-size:10px;font-weight:800;line-height:1.2;">
                                                        -{{ $displayHarga->promoDiscountPercent() }}%
                                                    </span>
                                                </div>
                                            @endif
                                            <div class="price-val">
                                                Rp {{ number_format($displayHarga->effectivePrice(), 0, ',', '.') }}
                                            </div>
                                        @else
                                            <div class="price-na">Ticket Belum Tersedia</div>
                                        @endif
                                        <div class="card-by">By: {{ $recEvent->user?->name ?? $recEvent->name ?? 'Organizer' }}</div>
                                    </div>
                                    <span class="btn-beli">Beli</span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    @endif
</section>
