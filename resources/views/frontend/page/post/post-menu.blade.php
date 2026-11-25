<section class="events-wrap">

    @if (count($event))
        <div class="event-grid">
            @foreach ($event as $events)
                <a href="{{ url('/ticket/' . $events->slug) }}" class="event-card " style="transition-delay:.1s">

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
        <div style="text-align:center; padding: 60px 20px;">
            <img src="{{ asset('/storage/setting/nodata.svg') }}" style="max-width:420px; width: 100%; opacity: 0.85;" alt="Tidak ada data">
            <p style="margin-top: 24px; font-size: 15px; color: var(--muted); font-weight: 500;">Tidak ada event yang ditemukan</p>
        </div>
    @endif
</section>