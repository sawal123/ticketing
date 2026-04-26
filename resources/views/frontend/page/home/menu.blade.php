<!-- ══ EVENTS ══ -->
<section class="events-wrap">
    <div class="section-top reveal">
        <div>
            <div class="section-label">Event Terbaru</div>
            <h2>Temukan Acaramu</h2>
            <p>Temukan acara favorit Anda, dan mari bersenang-senang</p>
        </div>
        <a href="/search" class="btn-viewall">
            View All Events
            <svg viewBox="0 0 16 16" fill="none">
                <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>
        </a>
    </div>

    <div class="event-grid">

        @foreach ($events as $key => $event)
            <a href="{{ url('/ticket/' . $event->slug) }}" class="event-card {{ $key == 0 ? 'featured' : '' }} reveal"
                style="transition-delay:{{ 0.05 + $key * 0.05 }}s">

                <div class="card-img">
                    {{-- Background / Image --}}
                    @if ($event->cover)
                        <img src="{{ asset('storage/cover/' . $event->cover) }}" class="img-bg" alt="cover"
                            style="object-fit:cover; height: 100% !important; ">
                    @else
                        <div class="img-bg bg-{{ ($key % 3) + 1 }}"></div>
                    @endif

                    <div class="img-glow"></div>

                    {{-- STATUS --}}
                    <span class="card-status {{ $event->status === 'close' ? 'status-close' : 'status-open' }}">
                        {{ $event->status === 'close' ? 'Close' : 'On Sale' }}
                    </span>

                    {{-- LOCATION --}}
                    <div class="card-loc">
                        <svg viewBox="0 0 16 16" fill="none">
                            <path
                                d="M8 1.5C5.5 1.5 3.5 3.5 3.5 6c0 3.5 4.5 8.5 4.5 8.5S12.5 9.5 12.5 6c0-2.5-2-4.5-4.5-4.5z"
                                stroke="currentColor" stroke-width="1.4" />
                            <circle cx="8" cy="6" r="1.5" stroke="currentColor" stroke-width="1.4" />
                        </svg>
                        {{ implode(' ', array_slice(explode(' ', $event->alamat), 0, 3)) . '...' }}
                    </div>
                </div>

                <div class="card-body">
                    <div class="card-title">{{ $event->event }}</div>

                    <div class="card-meta">
                        <div class="meta-row">
                            <svg viewBox="0 0 16 16" fill="none">
                                <path
                                    d="M8 1.5C5.5 1.5 3.5 3.5 3.5 6c0 3.5 4.5 8.5 4.5 8.5S12.5 9.5 12.5 6c0-2.5-2-4.5-4.5-4.5z"
                                    stroke="currentColor" stroke-width="1.4" />
                                <circle cx="8" cy="6" r="1.5" stroke="currentColor"
                                    stroke-width="1.4" />
                            </svg>
                            {{ implode(' ', array_slice(explode(' ', $event->alamat), 0, 3)) . '...' }}
                        </div>

                        <div class="meta-row">
                            <svg viewBox="0 0 16 16" fill="none">
                                <rect x="2" y="3" width="12" height="11" rx="2" stroke="currentColor"
                                    stroke-width="1.3" />
                                <path d="M5 1.5v3M11 1.5v3M2 7h12" stroke="currentColor" stroke-width="1.3"
                                    stroke-linecap="round" />
                            </svg>
                            {{ date('Y-m-d H:i', strtotime($event->tanggal)) }}
                        </div>
                    </div>

                    <div class="card-footer">
                        <div>
                            <div class="price-from">Start From</div>

                            @if ($event->harga)
                                <div class="price-val">
                                    Rp {{ number_format($event->harga->harga, 0, ',', '.') }}
                                </div>
                            @else
                                <div class="price-na">Ticket Belum Tersedia</div>
                            @endif

                            <div class="card-by">By: {{ $event->user->name ?? 'Unknown' }}</div>
                        </div>

                        <span class="btn-beli {{ $event->status === 'close' ? 'disabled' : '' }}">
                            {{ $event->status === 'close' ? 'Close' : 'Beli' }}
                            <svg viewBox="0 0 16 16" fill="none">
                                <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                    </div>
                </div>

            </a>
        @endforeach

    </div>

    <div class="events-footer reveal">
        <a href="/search" class="btn-view-all-big">
            View All Events
            <svg viewBox="0 0 16 16" fill="none">
                <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>
        </a>
    </div>
</section>
