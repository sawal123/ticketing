@extends('frontend.index')

@section('content')

    <link rel="stylesheet" href="{{ asset('landing/css/bayartiket.css') }}">

    <div class="" style="height: 50px; "></div>

    <!-- BREADCRUMB -->
    <div class="breadcrumb ">
        <span>Event</span>
        <span class="breadcrumb-sep">›</span>
        <span>{{ $event->event }}</span>
        <span class="breadcrumb-sep">›</span>
        <span style="color:var(--gold);">Checkout</span>
    </div>

    @php
        $ticketDeliveryEmail = filled($cart->ticket_recipient_email) ? $cart->ticket_recipient_email : Auth::user()->email;
    @endphp

    <!-- SUCCESS BANNER (Show only when status is SUCCESS) -->
    @if ($cart->status === \App\Models\Cart::STATUS_SUCCESS)
        <div class="success-hero">
            <div class="success-hero-inner">
                <div class="success-icon-large">✅</div>
                <div class="success-content">
                    <h2 class="success-title">Pembayaran Berhasil!</h2>
                    <p class="success-desc">
                        Selamat, tiket Anda telah terkonfirmasi. E-Ticket & Barcode telah dikirim ke email:
                        <strong>{{ $ticketDeliveryEmail }}</strong>.<br>
                        <small>(Silakan periksa kotak masuk atau folder SPAM Anda)</small>
                    </p>
                </div>
                <div class="success-actions">
                    <a href="{{ url('/transaksi') }}" class="btn-success-action primary">
                        <span>🎫</span>
                        <span>Lihat Tiket Saya</span>
                    </a>
                </div>
            </div>
        </div>
    @else
        <!-- EMAIL ALERT (Show only when status is NOT SUCCESS) -->
        <div class="email-alert">
            <div class="email-alert-inner">
                <span class="alert-icon">✉️</span>
                <span class="alert-text">Pastikan email Anda aktif: <span
                        class="alert-email">{{ Auth::user()->email }}</span></span>
            </div>
        </div>
    @endif

    <!-- MAIN -->
    <div class="checkout-layout">
        @if (session('success') && !str_contains(session('success'), 'Pembayaran Berhasil'))
            <div class="alert alert-primary">
                {{ session('success') }}
            </div>
        @endif

        <!-- LEFT: EVENT CARD -->
        <div class="event-card">
            <div class="event-banner">
                <img src="{{ asset('storage/cover/' . $event->cover) }}" class="event-banner" alt="...">
            </div>
            <div class="event-info-card">
                <div class="event-name">{{ $event->event }}</div>
                <div class="event-meta-row">📅 <span>{{ $event->tanggal }}</span></div>
                <div class="event-meta-row">📍 <span>{{ $event->alamat }}</span></div>
                <div class="invoice-tag">
                    <div class="invoice-content">
                        <div class="invoice-label">No. Invoice</div>
                        <div class="invoice-value">{{ $cart->invoice }}</div>
                        <div class="invoice-meta">
                            <div class="invoice-value">
                                <span>Tanggal Checkout</span>
                                <strong>{{ $cart->created_at ? $cart->created_at->format('d M Y, H:i') : '-' }}</strong>
                            </div>
                            @if (in_array(strtoupper($cart->status ?? ''), ['SUCCESS', 'PAID']))
                                <div class="invoice-value">
                                    <span>Tanggal Update</span>
                                    <strong>{{ $cart->updated_at ? $cart->updated_at->format('d M Y, H:i') : '-' }}</strong>
                                </div>
                            @endif
                        </div>
                    </div>
                    <button class="copy-btn" onclick="copyInvoice()" title="Salin invoice">⎘</button>
                </div>
            </div>
        </div>

        <!-- RIGHT: CHECKOUT FORMS -->
        <div class="checkout-right">
            @if (in_array($cart->status, [\App\Models\Cart::STATUS_RESERVED, \App\Models\Cart::STATUS_PENDING]) && $cart->expires_at)
                <div class="card" id="reservationCard" data-expires-at="{{ $cart->expires_at->toIso8601String() }}">
                    <div class="card-header">
                        <div class="card-icon" style="background:rgba(245,200,66,0.12);">⏳</div>
                        <div class="card-title">Reservation</div>
                    </div>
                    <div class="card-body">
                        <div class="detail-row">
                            <span class="label">Sisa waktu pembayaran</span>
                            <span class="value" id="reservationCountdown">--:--</span>
                        </div>
                        <div id="reservationExpiredMsg" style="display:none;color:#e8547a;font-size:13px;margin-top:8px;">
                            Reservation sudah expired. Silakan checkout ulang bila tiket masih tersedia.
                        </div>
                    </div>
                </div>
            @endif

            @php
                $hasActivePaymentLink = $cart->hasActivePaymentLink();
                $recipientSnapshotLocked = $cart->recipientSnapshotLocked();
                $ticketHolderNameValue = old('ticket_holder_name', $cart->ticket_holder_name);
            @endphp

            <div class="card">
                <div class="card-header">
                    <div class="card-icon" style="background:rgba(61,217,196,0.12);">👤</div>
                    <div class="card-title">Informasi Pemegang Tiket</div>
                </div>
                <div class="card-body">
                    @if (!$recipientSnapshotLocked)
                        <div style="display:grid;gap:16px;">
                            <div>
                                <label for="ticketHolderName"
                                    style="display:block;font-weight:700;font-size:13px;margin-bottom:8px;color:#fff;">
                                    Nama Lengkap sesuai KTP *
                                </label>
                                <input type="text" id="ticketHolderName" name="ticket_holder_name"
                                    value="{{ $ticketHolderNameValue }}" maxlength="255" required form="paynowForm"
                                    class="voucher-input" placeholder="Masukkan nama lengkap pemegang tiket">
                                <div style="margin-top:8px;font-size:12px;color:var(--muted);line-height:1.6;">
                                    Pastikan nama sesuai dengan identitas yang akan digunakan saat menghadiri event.
                                </div>
                            </div>

                            <div>
                                <div style="display:block;font-weight:700;font-size:13px;margin-bottom:10px;color:#fff;">
                                    Email Penerima Tiket
                                </div>

                                <div
                                    style="display:grid;gap:4px;padding:12px 14px;border-radius:14px;border:1px solid rgba(255,255,255,0.08);background:rgba(255,255,255,0.03);">
                                    <span style="font-size:13px;color:#fff;">Gunakan email akun saat ini</span>
                                    <span style="font-size:12px;color:var(--muted);">{{ Auth::user()->email }}</span>
                                </div>
                                <div style="margin-top:8px;font-size:12px;color:var(--muted);line-height:1.6;">
                                    Tiket akan dikirim ke email akun yang digunakan saat mendaftar.
                                </div>
                            </div>
                        </div>
                    @else
                        <div style="display:grid;gap:12px;">
                            <div class="detail-row">
                                <span class="label">Nama Pemegang Tiket</span>
                                <span class="value">{{ $cart->ticket_holder_name ?: '-' }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Email Penerima Tiket</span>
                                <span class="value">{{ $ticketDeliveryEmail ?: '-' }}</span>
                            </div>
                            <div style="font-size:12px;color:var(--muted);line-height:1.6;">
                                Informasi pemegang tiket tidak dapat diubah setelah proses pembayaran dimulai.
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            @if ($event->registration_mode !== \App\Models\Event::REGISTRATION_MODE_TICKETING)
                @php
                    $registrationLocked = $hasActivePaymentLink;
                    $registrationAnswers = old('registration_answers', $registration?->answers ?? []);
                    $savedMembers = $registration?->members ?? collect();
                    $teamMinMembers = max(1, (int) ($event->team_min_members ?? 1));
                    $teamMaxMembers = max($teamMinMembers, (int) ($event->team_max_members ?? $teamMinMembers));
                    $memberCount = min($teamMaxMembers, max($teamMinMembers, count(old('members', $savedMembers))));
                @endphp
                <div class="card">
                    <div class="card-header">
                        <div class="card-icon" style="background:rgba(245,200,66,0.12);">&#128221;</div>
                        <div class="card-title">Form Pendaftaran</div>
                    </div>
                    <div class="card-body">
                        @if ($registrationLocked)
                            <div style="display:grid;gap:12px;">
                                @foreach ($registrationFields as $field)
                                    <div class="detail-row">
                                        <span class="label">{{ $field->label }}</span>
                                        <span class="value">{{ data_get($registration?->answers, $field->id, '-') }}</span>
                                    </div>
                                @endforeach
                                @if ($event->registration_mode === \App\Models\Event::REGISTRATION_MODE_TEAM)
                                    <div class="detail-row">
                                        <span class="label">Nama Tim</span>
                                        <span class="value">{{ $registration?->team_name ?: '-' }}</span>
                                    </div>
                                    @foreach ($savedMembers as $member)
                                        <div style="padding:12px;border:1px solid rgba(255,255,255,0.08);border-radius:12px;">
                                            <strong style="font-size:13px;color:#fff;">Anggota {{ $member->sort_order }}{{ $member->is_captain ? ' (Kapten)' : '' }}</strong>
                                            @foreach ($memberFields as $field)
                                                <div class="detail-row" style="margin-top:8px;">
                                                    <span class="label">{{ $field->label }}</span>
                                                    <span class="value">{{ data_get($member->answers, $field->id, '-') }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach
                                @endif
                                <div style="font-size:12px;color:var(--muted);">Data pendaftaran terkunci setelah link pembayaran aktif.</div>
                            </div>
                        @else
                            <div style="display:grid;gap:14px;">
                                @foreach ($registrationFields as $field)
                                    <div>
                                        <label style="display:block;font-weight:700;font-size:13px;margin-bottom:8px;color:#fff;">{{ $field->label }}{{ $field->is_required ? ' *' : '' }}</label>
                                        @if ($field->type === 'textarea')
                                            <textarea name="registration_answers[{{ $field->id }}]" form="paynowForm" class="voucher-input" rows="3">{{ data_get($registrationAnswers, $field->id) }}</textarea>
                                        @elseif ($field->type === 'select')
                                            <select name="registration_answers[{{ $field->id }}]" form="paynowForm" class="voucher-input">
                                                <option value="">Pilih {{ $field->label }}</option>
                                                @foreach ($field->options ?? [] as $option)
                                                    <option value="{{ $option }}" @selected((string) data_get($registrationAnswers, $field->id) === (string) $option)>{{ $option }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            <input type="{{ $field->type === 'number' ? 'number' : 'text' }}" name="registration_answers[{{ $field->id }}]" form="paynowForm" value="{{ data_get($registrationAnswers, $field->id) }}" class="voucher-input">
                                        @endif
                                    </div>
                                @endforeach

                                @if ($event->registration_mode === \App\Models\Event::REGISTRATION_MODE_TEAM)
                                    <div>
                                        <label style="display:block;font-weight:700;font-size:13px;margin-bottom:8px;color:#fff;">Nama Tim *</label>
                                        <input type="text" name="team_name" form="paynowForm" value="{{ old('team_name', $registration?->team_name) }}" class="voucher-input">
                                    </div>
                                    <div id="teamRosterMembers" data-min-members="{{ $teamMinMembers }}" data-max-members="{{ $teamMaxMembers }}">
                                    @for ($memberIndex = 0; $memberIndex < $memberCount; $memberIndex++)
                                        @php $memberAnswers = old("members.{$memberIndex}.answers", data_get($savedMembers->get($memberIndex), 'answers', [])); @endphp
                                        <div class="team-member-card" style="padding:12px;border:1px solid rgba(255,255,255,0.08);border-radius:12px;display:grid;gap:10px;margin-top:10px;">
                                            <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;">
                                                <label class="team-member-title" style="font-weight:700;font-size:13px;color:#fff;">Anggota {{ $memberIndex + 1 }}</label>
                                                <button type="button" class="team-member-remove" style="border:0;border-radius:8px;padding:5px 8px;background:rgba(232,84,122,0.16);color:#f6a1b7;font-size:12px;">Hapus Anggota</button>
                                            </div>
                                            <label style="font-size:12px;color:var(--muted);"><input type="checkbox" class="team-member-captain" name="members[{{ $memberIndex }}][is_captain]" form="paynowForm" value="1" @checked(old("members.{$memberIndex}.is_captain", $memberIndex === 0))> Kapten tim</label>
                                            @foreach ($memberFields as $field)
                                                <div>
                                                    <label style="display:block;font-size:12px;margin-bottom:6px;color:#fff;">{{ $field->label }}{{ $field->is_required ? ' *' : '' }}</label>
                                                    @if ($field->type === 'textarea')
                                                        <textarea name="members[{{ $memberIndex }}][answers][{{ $field->id }}]" form="paynowForm" class="voucher-input" rows="2">{{ data_get($memberAnswers, $field->id) }}</textarea>
                                                    @elseif ($field->type === 'select')
                                                        <select name="members[{{ $memberIndex }}][answers][{{ $field->id }}]" form="paynowForm" class="voucher-input">
                                                            <option value="">Pilih {{ $field->label }}</option>
                                                            @foreach ($field->options ?? [] as $option)
                                                                <option value="{{ $option }}" @selected((string) data_get($memberAnswers, $field->id) === (string) $option)>{{ $option }}</option>
                                                            @endforeach
                                                        </select>
                                                    @else
                                                        <input type="{{ $field->type === 'number' ? 'number' : 'text' }}" name="members[{{ $memberIndex }}][answers][{{ $field->id }}]" form="paynowForm" value="{{ data_get($memberAnswers, $field->id) }}" class="voucher-input">
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endfor
                                    </div>
                                    <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;margin-top:12px;">
                                        <span id="teamRosterCount" style="font-size:12px;color:var(--muted);"></span>
                                        <button type="button" id="teamMemberAdd" style="border:0;border-radius:8px;padding:7px 10px;background:rgba(61,217,196,0.16);color:#72eadb;font-size:12px;">Tambah Anggota</button>
                                    </div>
                                    <template id="teamMemberTemplate">
                                        <div class="team-member-card" style="padding:12px;border:1px solid rgba(255,255,255,0.08);border-radius:12px;display:grid;gap:10px;margin-top:10px;">
                                            <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;">
                                                <label class="team-member-title" style="font-weight:700;font-size:13px;color:#fff;">Anggota</label>
                                                <button type="button" class="team-member-remove" style="border:0;border-radius:8px;padding:5px 8px;background:rgba(232,84,122,0.16);color:#f6a1b7;font-size:12px;">Hapus Anggota</button>
                                            </div>
                                            <label style="font-size:12px;color:var(--muted);"><input type="checkbox" class="team-member-captain" name="members[__INDEX__][is_captain]" form="paynowForm" value="1"> Kapten tim</label>
                                            @foreach ($memberFields as $field)
                                                <div>
                                                    <label style="display:block;font-size:12px;margin-bottom:6px;color:#fff;">{{ $field->label }}{{ $field->is_required ? ' *' : '' }}</label>
                                                    @if ($field->type === 'textarea')
                                                        <textarea name="members[__INDEX__][answers][{{ $field->id }}]" form="paynowForm" class="voucher-input" rows="2"></textarea>
                                                    @elseif ($field->type === 'select')
                                                        <select name="members[__INDEX__][answers][{{ $field->id }}]" form="paynowForm" class="voucher-input">
                                                            <option value="">Pilih {{ $field->label }}</option>
                                                            @foreach ($field->options ?? [] as $option)
                                                                <option value="{{ $option }}">{{ $option }}</option>
                                                            @endforeach
                                                        </select>
                                                    @else
                                                        <input type="{{ $field->type === 'number' ? 'number' : 'text' }}" name="members[__INDEX__][answers][{{ $field->id }}]" form="paynowForm" class="voucher-input">
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </template>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            @if ($event->registration_mode === \App\Models\Event::REGISTRATION_MODE_TEAM && !$registrationLocked)
                <script>
                    (() => {
                        const roster = document.getElementById('teamRosterMembers');
                        const addButton = document.getElementById('teamMemberAdd');
                        const countLabel = document.getElementById('teamRosterCount');
                        const template = document.getElementById('teamMemberTemplate');

                        if (!roster || !addButton || !countLabel || !template) {
                            return;
                        }

                        const minimum = Number(roster.dataset.minMembers);
                        const maximum = Number(roster.dataset.maxMembers);
                        let nextIndex = roster.querySelectorAll('.team-member-card').length;

                        const cards = () => Array.from(roster.querySelectorAll('.team-member-card'));
                        const updateControls = () => {
                            const currentCards = cards();
                            countLabel.textContent = `Anggota ${currentCards.length} dari ${minimum}-${maximum}`;
                            addButton.disabled = currentCards.length >= maximum;
                            addButton.style.opacity = addButton.disabled ? '0.5' : '1';
                            currentCards.forEach((card, index) => {
                                card.querySelector('.team-member-title').textContent = `Anggota ${index + 1}`;
                                card.querySelector('.team-member-remove').disabled = currentCards.length <= minimum;
                                card.querySelector('.team-member-remove').style.opacity = currentCards.length <= minimum ? '0.5' : '1';
                            });
                        };

                        const ensureCaptain = () => {
                            const captains = cards().map((card) => card.querySelector('.team-member-captain'));
                            if (!captains.some((captain) => captain.checked) && captains[0]) {
                                captains[0].checked = true;
                            }
                        };

                        roster.addEventListener('click', (event) => {
                            const removeButton = event.target.closest('.team-member-remove');
                            if (!removeButton || cards().length <= minimum) {
                                return;
                            }

                            removeButton.closest('.team-member-card').remove();
                            ensureCaptain();
                            updateControls();
                        });

                        roster.addEventListener('change', (event) => {
                            if (!event.target.classList.contains('team-member-captain') || !event.target.checked) {
                                return;
                            }

                            cards().forEach((card) => {
                                const captain = card.querySelector('.team-member-captain');
                                if (captain !== event.target) {
                                    captain.checked = false;
                                }
                            });
                        });

                        addButton.addEventListener('click', () => {
                            if (cards().length >= maximum) {
                                return;
                            }

                            roster.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', nextIndex));
                            nextIndex++;
                            updateControls();
                        });

                        ensureCaptain();
                        updateControls();
                    })();
                </script>
            @endif

            <!-- TICKET DETAIL -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon" style="background:rgba(245,200,66,0.12);">🎫</div>
                    <div class="card-title">Ticket Detail</div>
                </div>
                <div class="card-body">
                    @php
                        $ticketRows = $harga;
                        $fee = 0;
                        $total1 = 0;
                    @endphp
                    <table class="ticket-detail-table">
                        <thead>
                            <tr>
                                <th>Qty</th>
                                <th style="text-align:right;">Total</th>
                            </tr>
                        </thead>
                        <tbody>


                            @foreach ($ticketRows as $hargaRow)
                                <tr class="ticket-row">

                                    <td>
                                        <div class="ticket-tier-badge">{{ $hargaRow->kategori_harga }}</div>
                                        <div class="ticket-qty-info">Rp
                                            {{ number_format($hargaRow->harga_ticket, 0, ',', '.') }} ×
                                            {{ $hargaRow->quantity }}
                                        </div>
                                        @php
                                            $fee += $hargaRow->quantity;
                                        @endphp
                                        <div class="ticket-qty-sub">{{ $fee }} tiket · Harga per tiket</div>
                                    </td>

                                    <td class="ticket-total-cell">
                                        @php
                                            $total1 = (int) $hargaRow->quantity * (int) $hargaRow->harga_ticket;
                                        @endphp
                                        Rp{{ number_format($total1 ?? 0) }}</td>


                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="ticket-summary-row">
                        <div class="ticket-count-pill">✓ Total {{ $fee }} Tiket</div>
                        <span style="font-size:12px;color:var(--muted);">Tiket akan dikirim via email</span>
                    </div>
                </div>
            </div>

            <!-- PAYMENT METHOD -->
            @if (!$hasActivePaymentLink)
                <div class="card">
                    <div class="card-header">
                        <div class="card-icon" style="background:rgba(108,92,231,0.12);">💳</div>
                        <div class="card-title">Metode Pembayaran</div>
                    </div>
                    <div class="card-body" style="padding-top:0;">
                        <div class="pay-accordion">
                            <div class="pay-accordion-header" onclick="toggleAccordion(this)">
                                <span class="pay-accordion-label">Pilih Metode</span>
                                <span class="pay-chevron open">▲</span>
                            </div>

                            @if ($hasAvailablePaymentGateways)
                                <div class="pay-options-grid" id="payOptions">
                                    @foreach ($payment as $gateway)
                                        <div class="pay-option {{ $selectedPaymentGatewayId === $gateway->id ? 'selected' : '' }}"
                                            id="card{{ $gateway->id }}" style="cursor:pointer;"
                                            data-payment-id="{{ $gateway->id }}"
                                            data-resolved-fee="{{ (int) ($gateway->resolved_internet_fee ?? 0) }}"
                                            onclick="selectPayment({{ $gateway->id }}, {{ (int) ($gateway->resolved_internet_fee ?? 0) }}, this)">

                                            <div class="pay-logo" style="padding:8px;">
                                                <img src="{{ asset('storage/' . $gateway->icon) }}"
                                                    style="width:40px; height:22px; object-fit:contain;"
                                                    alt="{{ $gateway->payment }}">
                                            </div>

                                            <div>
                                                <div class="pay-name">{{ $gateway->payment }}</div>
                                                <div class="pay-fee">
                                                    Biaya: Rp{{ number_format($gateway->resolved_internet_fee ?? 0, 0, ',', '.') }}
                                                </div>
                                            </div>

                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div style="padding:16px 0;color:var(--muted);font-size:13px;">
                                    Tidak ada metode pembayaran yang tersedia untuk event ini.
                                </div>
                            @endif

                        </div>
                    </div>
                </div>
            @endif

            <!-- VOUCHER -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon" style="background:rgba(61,217,196,0.12);">🏷️</div>
                    <div class="card-title">Voucher</div>
                </div>

                <div class="card-body">
                    @if ($cart->status !== \App\Models\Cart::STATUS_SUCCESS)
                        <form action="{{ url('/checkVoucer') }}" method="post" class="voucher-input-wrap">
                            @csrf
                            <input type="hidden" name="event" value="{{ $event->uid }}">
                            <input type="hidden" name="cartUid" value="{{ $cart->uid }}">

                            <input type="text" class="voucher-input" id="voucherInput" name="code"
                                placeholder="Masukan Code Voucher.." value="{{ $voucher->code ?? '' }}"
                                {{ $hasActivePaymentLink ? 'readonly' : '' }}>

                            <button type="submit" class="btn-voucher" {{ $hasActivePaymentLink ? 'disabled' : '' }}>
                                Gunakan
                            </button>
                        </form>
                    @endif


                    <!-- MESSAGE -->
                    <div id="voucherMsg" style="margin-top:10px;font-size:12px;">

                        @if (session('vError'))
                            <span style="color:#e8547a;">
                                {{ session('vError') }}
                            </span>
                        @endif

                        @if (session('voucher'))
                            <span style="color:#3dd9c4;">
                                {{ session('voucher') }}
                            </span>
                        @endif

                    </div>

                    <!-- REMOVE VOUCHER -->
                    @if (!$hasActivePaymentLink && $voucher && $voucher->code)
                        <div style="margin-top:10px;">
                            <form action="{{ url('/closeVoucher') }}" method="post">
                                @csrf
                                <input type="hidden" name="event" value="{{ $event->uid }}">
                                <input type="hidden" name="cartUid" value="{{ $cart->uid }}">
                                <input type="hidden" name="code" value="{{ $voucher->code }}">

                                <span style="font-size:12px;">
                                    Tidak ingin menggunakan voucher <b>{{ $voucher->code }}</b>?
                                </span>

                                <button type="submit"
                                    style="background:#e8547a;color:#fff;border:none;padding:4px 8px;border-radius:6px;">
                                    ✕
                                </button>
                            </form>
                        </div>
                    @endif

                </div>
            </div>

            <!-- PAYMENT DETAIL -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon" style="background:rgba(232,84,122,0.12);">🧾</div>
                    <div class="card-title">Payment Detail</div>
                </div>

                <div class="card-body">
                    <div class="payment-detail-rows">

                        {{-- PAYMENT METHOD --}}
                        <div class="detail-row">
                            <span class="label">Metode Pembayaran</span>
                            <span class="value" style="text-transform: uppercase;">
                                {{ $iFee->payment ?? ($cart->payment_type ? str_replace('_', ' ', $cart->payment_type) : 'Belum dipilih') }}
                            </span>
                        </div>

                        {{-- VOUCHER --}}
                        @if ($voucher && $voucher->code)
                            <div class="detail-row">
                                <span class="label">Voucher</span>
                                <span class="value">{{ $voucher->code }}</span>
                            </div>
                        @endif


                        {{-- TICKET --}}
                        <div class="detail-row">
                            <span class="label">Ticket</span>
                            <span class="value" id="subtotal-display">
                                Rp {{ number_format($total, 0, ',', '.') }}
                            </span>
                        </div>

                        {{-- DISCOUNT --}}
                        <div class="detail-row">
                            <span class="label">Discount</span>
                            <span class="value discount" id="discount-display">
                                -Rp {{ number_format($diskon, 0, ',', '.') }}
                            </span>
                        </div>

                        {{-- INTERNET FEE --}}
                        <div class="detail-row">
                            <span class="label">Internet Fee</span>
                            <span class="value" id="fee-display">
                                Rp {{ number_format($selectInternetFee ?? 0, 0, ',', '.') }}
                            </span>

                        </div>

                        {{-- PAJAK --}}
                        <div class="detail-row">
                            <span class="label">
                                Pajak / Fee
                                @if ($pajakPersen > 0)
                                    ({{ $pajakPersen }}%)
                                @endif
                            </span>
                            <span class="value tax">
                                Rp {{ number_format($nilaiPajak, 0, ',', '.') }}
                            </span>
                        </div>

                    </div>

                    {{-- TOTAL --}}
                    <div class="total-row">
                        <div class="total-label">Grand Total</div>
                        <div class="total-value" id="grand-total">
                            Rp {{ number_format($grandTotal ?? 0, 0, ',', '.') }}
                        </div>

                    </div>

                    {{-- STATUS BADGE (Jika SUCCESS) --}}
                    @if ($cart->status === \App\Models\Cart::STATUS_SUCCESS)
                        <div
                            style="margin-top: 15px; padding: 10px; background: rgba(61, 217, 196, 0.1); border-radius: 10px; border: 1px solid rgba(61, 217, 196, 0.2); display: flex; align-items: center; gap: 10px;">
                            <div
                                style="width: 8px; height: 8px; background: #3dd9c4; border-radius: 50%; box-shadow: 0 0 10px #3dd9c4;">
                            </div>
                            <span style="color: #3dd9c4; font-weight: 600; font-size: 13px; letter-spacing: 0.5px;">
                                TRANSAKSI SELESAI
                            </span>
                        </div>
                    @endif


                    {{-- BUTTON --}}
                    @if (in_array($cart->status, [\App\Models\Cart::STATUS_RESERVED, \App\Models\Cart::STATUS_PENDING]))
                        <form action="{{ url('/paynow') }}" method="post" enctype="multipart/form-data" id="paynowForm">
                            @csrf

                            <input type="hidden" id="selectedPayment" name="payment_gateway_id"
                                value="{{ $selectedPaymentGatewayId ?? '' }}">
                            <input type="hidden" name="cart_uid" value="{{ $cart->uid }}">

                            @if (!$hasActivePaymentLink)
                                <button type="button" class="btn-pay" onclick="showConfirmModal(event)"
                                    {{ $hasAvailablePaymentGateways ? '' : 'disabled' }}>
                                    <span>🔐</span>
                                    <span>{{ $cart->status === \App\Models\Cart::STATUS_PENDING ? 'Coba Pembayaran Lagi' : 'Bayar Sekarang' }}</span>
                                </button>
                            @else
                                <a href="{{ $cart->link }}" class="btn-pay"
                                    style="text-decoration:none;display:flex;justify-content:center;">
                                    <span>🔐</span>
                                    <span>Lanjutkan Pembayaran</span>
                                </a>
                            @endif
                        </form>
                    @else
                        <button type="submit" class="btn-pay" style="background:#3dd9c4;">
                            {{ $cart->status }}
                        </button>
                    @endif

                    <div class="security-note">
                        <span>🔒</span>
                        <span>Transaksi dijamin aman & terenkripsi</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    </div>


    <script>
        const checkoutPaymentOtp = {
            enabled: {{ $event->payment_otp_enabled ? 'true' : 'false' }},
            cartUid: @json($cart->uid),
            sendUrl: @json(route('checkout-payment-otp.send')),
            resendUrl: @json(route('checkout-payment-otp.resend')),
            verifyUrl: @json(route('checkout-payment-otp.verify')),
            csrfToken: @json(csrf_token()),
            resendAvailableIn: 0,
            countdownTimer: null,
        };

        function parseRupiah(text) {
            return parseInt(text.replace(/[^\d]/g, '')) || 0;
        }

        function formatRupiah(angka) {
            return 'Rp ' + angka.toLocaleString('id-ID');
        }

        function updateCheckoutTotals(resolvedInternetFee) {
            let diskon = {{ $diskon ?? 0 }};
            let nilaiPajak = {{ $nilaiPajak ?? 0 }};
            let total = parseRupiah(document.getElementById('subtotal-display').textContent);
            let totalAkhir = (total - diskon) + nilaiPajak + resolvedInternetFee;

            document.getElementById('fee-display').textContent = formatRupiah(resolvedInternetFee);
            document.getElementById('grand-total').textContent = formatRupiah(totalAkhir);
        }

        function selectPayment(paymentId, resolvedInternetFee, card) {
            document.getElementById('selectedPayment').value = paymentId;

            document.querySelectorAll('.pay-option').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');

            @if ($cart->status === \App\Models\Cart::STATUS_RESERVED && $cart->gross_amount === null)
                updateCheckoutTotals(resolvedInternetFee);
            @endif
        }

        document.addEventListener('DOMContentLoaded', function() {
            @if ($cart->status === \App\Models\Cart::STATUS_RESERVED && $cart->gross_amount === null)
                const selectedPaymentId = {{ (int) ($selectedPaymentGatewayId ?? 0) }};

                if (selectedPaymentId) {
                    const selectedCard = document.querySelector(`.pay-option[data-payment-id="${selectedPaymentId}"]`);

                    if (selectedCard) {
                        updateCheckoutTotals(parseInt(selectedCard.dataset.resolvedFee, 10) || 0);
                    }
                } else {
                    updateCheckoutTotals({{ (int) ($selectInternetFee ?? 0) }});
                }
            @endif

            const reservationCard = document.getElementById('reservationCard');
            if (reservationCard) {
                const expiresAt = new Date(reservationCard.dataset.expiresAt).getTime();
                const countdownEl = document.getElementById('reservationCountdown');
                const expiredMsg = document.getElementById('reservationExpiredMsg');

                const tickReservation = () => {
                    const diff = expiresAt - Date.now();
                    if (diff <= 0) {
                        countdownEl.textContent = 'Expired';
                        expiredMsg.style.display = 'block';
                        document.querySelectorAll('.btn-pay, .btn-voucher').forEach(btn => btn.disabled = true);
                        return;
                    }

                    const minutes = Math.floor(diff / 60000);
                    const seconds = Math.floor((diff % 60000) / 1000);
                    countdownEl.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                };

                tickReservation();
                setInterval(tickReservation, 1000);
            }
        });

        function submitPaynowOnce(form) {
            if (!form || form.dataset.submitting === '1') {
                return false;
            }

            if (typeof form.reportValidity === 'function' && !form.reportValidity()) {
                return false;
            }

            form.dataset.submitting = '1';
            form.querySelectorAll('.btn-pay').forEach(btn => {
                btn.disabled = true;
                btn.innerHTML = '<span>Memproses...</span>';
            });
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }

            return true;
        }

        async function paymentOtpRequest(url, payload) {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': checkoutPaymentOtp.csrfToken,
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(
                    data.message ||
                    data.errors?.otp?.[0] ||
                    data.errors?.cart_uid?.[0] ||
                    'Permintaan OTP gagal diproses.'
                );
            }

            return data;
        }

        if (!document.getElementById('checkoutSwalSkin')) {
            const checkoutSwalSkin = document.createElement('style');
            checkoutSwalSkin.id = 'checkoutSwalSkin';
            checkoutSwalSkin.textContent = `
                @keyframes otp-spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
                .checkout-swal-popup {
                    width: min(92vw, 430px) !important;
                    border-radius: 24px !important;
                    background:
                        radial-gradient(circle at top, rgba(168, 85, 247, 0.16), transparent 42%),
                        linear-gradient(180deg, rgba(25, 24, 36, 0.98) 0%, rgba(15, 14, 23, 0.98) 100%) !important;
                    border: 1px solid rgba(255, 255, 255, 0.08) !important;
                    box-shadow: 0 26px 70px rgba(4, 7, 20, 0.58) !important;
                    padding: 1.45rem !important;
                }
                .checkout-swal-title {
                    color: #f8f7ff !important;
                    font-size: 1.2rem !important;
                    line-height: 1.35 !important;
                    font-weight: 700 !important;
                    letter-spacing: -0.02em !important;
                    padding: 0 !important;
                    margin: 0 0 0.45rem !important;
                }
                .checkout-swal-html {
                    color: #c9cad6 !important;
                    font-size: 0.92rem !important;
                    line-height: 1.6 !important;
                    margin: 0 !important;
                    padding: 0 !important;
                }
                .checkout-swal-actions {
                    width: 100% !important;
                    gap: 0.7rem !important;
                    margin: 1.2rem 0 0 !important;
                    padding: 0 !important;
                }
                .checkout-swal-confirm,
                .checkout-swal-cancel {
                    margin: 0 !important;
                    border-radius: 14px !important;
                    padding: 0.82rem 1.15rem !important;
                    min-width: 0 !important;
                    font-size: 0.88rem !important;
                    font-weight: 700 !important;
                    box-shadow: none !important;
                    transition: transform 0.18s ease, opacity 0.18s ease, border-color 0.18s ease !important;
                }
                .checkout-swal-confirm {
                    background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%) !important;
                    color: #ffffff !important;
                }
                .checkout-swal-confirm:hover {
                    transform: translateY(-1px) !important;
                }
                .checkout-swal-cancel {
                    background: rgba(255, 255, 255, 0.04) !important;
                    color: #d8d9e6 !important;
                    border: 1px solid rgba(255, 255, 255, 0.08) !important;
                }
                .checkout-swal-confirm:disabled,
                .checkout-swal-cancel:disabled {
                    opacity: 0.48 !important;
                    cursor: not-allowed !important;
                    transform: none !important;
                }
                .checkout-swal-validation {
                    margin-top: 0.8rem !important;
                    padding: 0.75rem 0.9rem !important;
                    border-radius: 12px !important;
                    background: rgba(248, 113, 113, 0.12) !important;
                    border: 1px solid rgba(248, 113, 113, 0.2) !important;
                    color: #fecaca !important;
                    font-size: 0.8rem !important;
                    text-align: left !important;
                }
                .checkout-swal-icon {
                    border-color: rgba(255, 255, 255, 0.08) !important;
                    margin: 0.2rem auto 0.85rem !important;
                    transform: scale(0.9);
                }
                .checkout-swal-loader {
                    border-color: rgba(255,255,255,0.14) !important;
                    border-top-color: #ec4899 !important;
                }
                .checkout-otp-shell { text-align: left; }
                .checkout-otp-copy {
                    font-size: 0.82rem;
                    color: #9699ad;
                    margin-bottom: 0.95rem;
                    line-height: 1.55;
                }
                .checkout-otp-loading {
                    display: none;
                    align-items: center;
                    gap: 0.75rem;
                    margin-bottom: 0.9rem;
                    padding: 0.78rem 0.9rem;
                    border-radius: 14px;
                    background: rgba(124, 58, 237, 0.11);
                    border: 1px solid rgba(168, 85, 247, 0.18);
                    color: #f3e8ff;
                    font-size: 0.78rem;
                    font-weight: 600;
                }
                .checkout-otp-spinner {
                    width: 18px;
                    height: 18px;
                    border: 2px solid rgba(244, 114, 182, 0.18);
                    border-top-color: #f472b6;
                    border-radius: 999px;
                    display: inline-block;
                    animation: otp-spin 0.8s linear infinite;
                    flex-shrink: 0;
                }
                .checkout-otp-input {
                    width: 100%;
                    height: 58px;
                    border-radius: 18px;
                    border: 1px solid rgba(255, 255, 255, 0.08);
                    background: linear-gradient(180deg, rgba(39, 38, 56, 0.92) 0%, rgba(23, 22, 33, 0.92) 100%);
                    color: #ffffff;
                    text-align: center;
                    letter-spacing: 0.62rem;
                    font-size: 1.55rem;
                    font-weight: 700;
                    outline: none;
                    transition: border-color 0.18s ease, box-shadow 0.18s ease, opacity 0.18s ease;
                }
                .checkout-otp-input::placeholder {
                    color: rgba(255, 255, 255, 0.22);
                    letter-spacing: 0.5rem;
                }
                .checkout-otp-input:focus {
                    border-color: rgba(236, 72, 153, 0.55);
                    box-shadow: 0 0 0 4px rgba(168, 85, 247, 0.14);
                }
                .checkout-otp-input:disabled {
                    opacity: 0.58;
                    cursor: not-allowed;
                }
                .checkout-otp-meta {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    gap: 0.75rem;
                    margin-top: 0.9rem;
                }
                .checkout-otp-resend {
                    background: rgba(255, 255, 255, 0.04);
                    color: #ececff;
                    border: 1px solid rgba(255, 255, 255, 0.08);
                    padding: 0.68rem 0.92rem;
                    border-radius: 13px;
                    cursor: pointer;
                    font-size: 0.76rem;
                    font-weight: 700;
                    letter-spacing: 0.01em;
                    transition: opacity 0.18s ease, transform 0.18s ease, border-color 0.18s ease;
                }
                .checkout-otp-resend:hover:not(:disabled) {
                    transform: translateY(-1px);
                    border-color: rgba(236, 72, 153, 0.3);
                }
                .checkout-otp-resend:disabled {
                    opacity: 0.52;
                    cursor: not-allowed;
                }
                .checkout-otp-expiry {
                    font-size: 0.72rem;
                    color: #8d90a5;
                    white-space: nowrap;
                }
                .checkout-otp-status {
                    margin-top: 0.95rem;
                    min-height: 44px;
                    padding: 0.78rem 0.9rem;
                    border-radius: 14px;
                    background: rgba(34, 197, 94, 0.08);
                    border: 1px solid rgba(61, 217, 196, 0.16);
                    color: #8ff3df;
                    font-size: 0.78rem;
                    line-height: 1.5;
                }
                .checkout-otp-status.is-error {
                    background: rgba(248, 113, 113, 0.11);
                    border-color: rgba(248, 113, 113, 0.24);
                    color: #fecaca;
                }
                .checkout-summary { text-align: left; }
                .checkout-summary-copy {
                    font-size: 0.82rem;
                    color: #9ea1b5;
                    line-height: 1.55;
                    margin-bottom: 1rem;
                }
                .checkout-summary-meta {
                    display: grid;
                    gap: 0.8rem;
                    margin-bottom: 0.95rem;
                }
                .checkout-summary-card {
                    padding: 0.9rem 1rem;
                    border-radius: 16px;
                    background: rgba(255,255,255,0.04);
                    border: 1px solid rgba(255,255,255,0.07);
                }
                .checkout-summary-label {
                    display: block;
                    margin-bottom: 0.35rem;
                    font-size: 0.62rem;
                    text-transform: uppercase;
                    letter-spacing: 0.16em;
                    font-weight: 800;
                    color: #7f8295;
                }
                .checkout-summary-value {
                    color: #f3f4fb;
                    font-size: 0.88rem;
                    font-weight: 700;
                    line-height: 1.45;
                }
                .checkout-summary-note {
                    display: block;
                    margin-top: 0.35rem;
                    font-size: 0.7rem;
                    color: #f5d07a;
                }
                .checkout-ticket-box {
                    border-radius: 18px;
                    background: linear-gradient(180deg, rgba(32,31,46,0.95) 0%, rgba(20,19,31,0.95) 100%);
                    border: 1px solid rgba(255,255,255,0.07);
                    padding: 1rem;
                }
                .checkout-ticket-row {
                    display: flex;
                    justify-content: space-between;
                    gap: 0.9rem;
                    padding: 0.72rem 0;
                    border-bottom: 1px solid rgba(255,255,255,0.06);
                }
                .checkout-ticket-row:first-child { padding-top: 0.1rem; }
                .checkout-ticket-row:last-child {
                    border-bottom: 0;
                    padding-bottom: 0.1rem;
                }
                .checkout-ticket-name {
                    color: #b9bdd1;
                    font-size: 0.8rem;
                }
                .checkout-ticket-price {
                    color: #ffffff;
                    font-size: 0.82rem;
                    font-weight: 700;
                    white-space: nowrap;
                }
                .checkout-ticket-total {
                    margin-top: 0.95rem;
                    padding-top: 0.95rem;
                    border-top: 1px dashed rgba(255,255,255,0.14);
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    gap: 0.8rem;
                    color: #f5f3ff;
                    font-weight: 800;
                    font-size: 1rem;
                }
                .checkout-ticket-total strong {
                    color: #f472b6;
                    font-size: 1.12rem;
                }
            `;
            document.head.appendChild(checkoutSwalSkin);
        }

        function checkoutSwalConfig(options = {}) {
            return {
                background: '#120f1d',
                color: '#f8f7ff',
                width: '420px',
                buttonsStyling: false,
                customClass: {
                    popup: 'checkout-swal-popup',
                    title: 'checkout-swal-title',
                    htmlContainer: 'checkout-swal-html',
                    actions: 'checkout-swal-actions',
                    confirmButton: 'checkout-swal-confirm',
                    cancelButton: 'checkout-swal-cancel',
                    validationMessage: 'checkout-swal-validation',
                    loader: 'checkout-swal-loader',
                    icon: 'checkout-swal-icon',
                },
                ...options,
            };
        }

        function openCheckoutSwal(options = {}) {
            return Swal.fire(checkoutSwalConfig(options));
        }

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, (character) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            }[character]));
        }

        function updateOtpResendButton() {
            const resendButton = document.getElementById('paymentOtpResendButton');

            if (!resendButton) {
                return;
            }

            const buttonLabel = checkoutPaymentOtp.resendButtonLabel || 'Kirim Ulang';
            const forceDisabled = checkoutPaymentOtp.resendButtonDisabled === true;

            if (checkoutPaymentOtp.resendAvailableIn > 0) {
                resendButton.disabled = true;
                resendButton.textContent = `Kirim Ulang (${checkoutPaymentOtp.resendAvailableIn}s)`;
            } else {
                resendButton.disabled = forceDisabled;
                resendButton.textContent = buttonLabel;
            }
        }

        function startOtpCountdown(seconds) {
            checkoutPaymentOtp.resendAvailableIn = seconds;
            updateOtpResendButton();

            if (checkoutPaymentOtp.countdownTimer) {
                clearInterval(checkoutPaymentOtp.countdownTimer);
            }

            if (seconds <= 0) {
                return;
            }

            checkoutPaymentOtp.countdownTimer = setInterval(() => {
                checkoutPaymentOtp.resendAvailableIn = Math.max(0, checkoutPaymentOtp.resendAvailableIn - 1);
                updateOtpResendButton();

                if (checkoutPaymentOtp.resendAvailableIn === 0) {
                    clearInterval(checkoutPaymentOtp.countdownTimer);
                    checkoutPaymentOtp.countdownTimer = null;
                }
            }, 1000);
        }

        function setOtpStatus(message, isError = false) {
            const status = document.getElementById('paymentOtpStatus');

            if (!status) {
                return;
            }

            status.textContent = message || '';
            status.classList.toggle('is-error', isError);
        }

        function setOtpModalState({
            message = '',
            isError = false,
            isLoading = false,
            inputDisabled = false,
            verifyDisabled = false,
            resendDisabled = false,
            resendLabel = 'Kirim Ulang',
            focusInput = false,
        } = {}) {
            const loadingIndicator = document.getElementById('paymentOtpLoading');
            const otpInput = document.getElementById('paymentOtpInput');
            const confirmButton = Swal.getConfirmButton();

            checkoutPaymentOtp.resendButtonDisabled = resendDisabled;
            checkoutPaymentOtp.resendButtonLabel = resendLabel;

            if (loadingIndicator) {
                loadingIndicator.style.display = isLoading ? 'flex' : 'none';
            }

            if (otpInput) {
                otpInput.disabled = inputDisabled;

                if (focusInput && !inputDisabled) {
                    otpInput.focus();
                }
            }

            if (confirmButton) {
                confirmButton.disabled = verifyDisabled;
            }

            setOtpStatus(message, isError);
            updateOtpResendButton();
        }

        function handleOtpSendResponse(data) {
            if (data.verified === true) {
                if (Swal.isVisible()) {
                    Swal.close();
                }

                submitPaymentAfterOtpVerification();
                return;
            }

            checkoutPaymentOtp.resendAvailableIn = data.resend_available_in || 0;
            checkoutPaymentOtp.secondaryAction = 'resend';
            startOtpCountdown(checkoutPaymentOtp.resendAvailableIn);

            if (data.status === 'requires_resend') {
                setOtpModalState({
                    message: data.message || 'Kode OTP perlu dikirim ulang.',
                    inputDisabled: true,
                    verifyDisabled: true,
                    resendDisabled: false,
                    resendLabel: 'Kirim Ulang',
                });

                return;
            }

            setOtpModalState({
                message: data.message || 'Kode OTP pembayaran telah dikirim ke email Anda.',
                inputDisabled: false,
                verifyDisabled: false,
                resendDisabled: false,
                resendLabel: 'Kirim Ulang',
                focusInput: true,
            });
        }

        function updateOtpModalToError(error) {
            checkoutPaymentOtp.resendAvailableIn = 0;
            checkoutPaymentOtp.secondaryAction = 'send';

            if (checkoutPaymentOtp.countdownTimer) {
                clearInterval(checkoutPaymentOtp.countdownTimer);
                checkoutPaymentOtp.countdownTimer = null;
            }

            setOtpModalState({
                message: error.message || 'Permintaan OTP gagal diproses.',
                isError: true,
                inputDisabled: true,
                verifyDisabled: true,
                resendDisabled: false,
                resendLabel: 'Coba Lagi',
            });
        }

        async function sendPaymentOtpIntoModal() {
            try {
                const data = await paymentOtpRequest(checkoutPaymentOtp.sendUrl, {
                    cart_uid: checkoutPaymentOtp.cartUid,
                });

                handleOtpSendResponse(data);
            } catch (error) {
                updateOtpModalToError(error);
            }
        }

        async function handleOtpResend() {
            try {
                checkoutPaymentOtp.secondaryAction = 'resend';
                const data = await paymentOtpRequest(checkoutPaymentOtp.resendUrl, {
                    cart_uid: checkoutPaymentOtp.cartUid,
                });

                startOtpCountdown(data.resend_available_in || 0);
                setOtpStatus(data.message || 'Kode OTP baru telah dikirim.');
            } catch (error) {
                setOtpStatus(error.message, true);
            }
        }

        async function handleOtpSecondaryAction() {
            if (checkoutPaymentOtp.secondaryAction === 'send') {
                setOtpModalState({
                    message: 'Mengirim kode OTP...',
                    isLoading: true,
                    inputDisabled: true,
                    verifyDisabled: true,
                    resendDisabled: true,
                    resendLabel: 'Kirim Ulang',
                });

                await sendPaymentOtpIntoModal();
                return;
            }

            await handleOtpResend();
        }

        function submitPaymentAfterOtpVerification() {
            openCheckoutSwal({
                title: 'Memproses...',
                text: 'Harap tunggu sebentar',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            const form = document.querySelector('form[action="{{ url('/paynow') }}"]');
            submitPaynowOnce(form);
        }

        function openOtpModal({
            message = '',
            isLoading = false,
            inputDisabled = false,
            verifyDisabled = false,
            resendDisabled = false,
            resendLabel = 'Kirim Ulang',
        } = {}) {
            openCheckoutSwal({
                title: 'Verifikasi OTP Email',
                html: `
                    <div class="checkout-otp-shell">
                        <p class="checkout-otp-copy">Masukkan 6 digit kode OTP yang dikirim ke email Anda untuk melanjutkan pembayaran.</p>
                        <div id="paymentOtpLoading" class="checkout-otp-loading">
                            <span class="checkout-otp-spinner"></span>
                            <span>Mengirim kode OTP...</span>
                        </div>
                        <input id="paymentOtpInput" class="checkout-otp-input" type="text" inputmode="numeric" maxlength="6" placeholder="000000">
                        <div class="checkout-otp-meta">
                            <button type="button" id="paymentOtpResendButton" class="checkout-otp-resend">
                                Kirim Ulang
                            </button>
                            <span class="checkout-otp-expiry">Kode berlaku 5 menit</span>
                        </div>
                        <div id="paymentOtpStatus" class="checkout-otp-status">${message}</div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Verifikasi',
                cancelButtonText: 'Tutup',
                reverseButtons: true,
                allowOutsideClick: () => !Swal.isLoading(),
                didOpen: () => {
                    const resendButton = document.getElementById('paymentOtpResendButton');

                    if (resendButton) {
                        resendButton.addEventListener('click', handleOtpSecondaryAction);
                    }

                    setOtpModalState({
                        message: message,
                        isLoading: isLoading,
                        inputDisabled: inputDisabled,
                        verifyDisabled: verifyDisabled,
                        resendDisabled: resendDisabled,
                        resendLabel: resendLabel,
                        focusInput: !inputDisabled,
                    });
                },
                preConfirm: async () => {
                    const otpInput = document.getElementById('paymentOtpInput');
                    const otp = otpInput ? otpInput.value.trim() : '';

                    if (!/^\d{6}$/.test(otp)) {
                        Swal.showValidationMessage('Masukkan 6 digit kode OTP yang valid.');
                        return false;
                    }

                    try {
                        return await paymentOtpRequest(checkoutPaymentOtp.verifyUrl, {
                            cart_uid: checkoutPaymentOtp.cartUid,
                            otp: otp,
                        });
                    } catch (error) {
                        Swal.showValidationMessage(error.message);
                        return false;
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    openCheckoutSwal({
                        icon: 'success',
                        title: 'OTP Berhasil Diverifikasi',
                        text: 'Pembayaran akan dilanjutkan.',
                        timer: 1200,
                        showConfirmButton: false,
                    }).then(() => {
                        submitPaymentAfterOtpVerification();
                    });
                }
            });
        }

        async function handlePaymentOtpFlow() {
            checkoutPaymentOtp.secondaryAction = 'send';
            checkoutPaymentOtp.resendAvailableIn = 0;
            checkoutPaymentOtp.resendButtonLabel = 'Kirim Ulang';
            checkoutPaymentOtp.resendButtonDisabled = true;

            openOtpModal({
                message: 'Mengirim kode OTP...',
                isLoading: true,
                inputDisabled: true,
                verifyDisabled: true,
                resendDisabled: true,
                resendLabel: 'Kirim Ulang',
            });

            await sendPaymentOtpIntoModal();
        }

        function showConfirmModal(e) {
            e.preventDefault();
            const form = document.getElementById('paynowForm');

            if (!form || (typeof form.reportValidity === 'function' && !form.reportValidity())) {
                return;
            }

            if (document.querySelectorAll('.pay-option').length === 0) {
                openCheckoutSwal({
                    icon: 'warning',
                    title: 'Metode Pembayaran Tidak Tersedia',
                    text: 'Tidak ada metode pembayaran yang tersedia untuk event ini.',
                });
                return;
            }

            // Check if payment selected
            const paymentId = document.getElementById('selectedPayment').value;
            if (!paymentId) {
                openCheckoutSwal({
                    icon: 'warning',
                    title: 'Metode Pembayaran Belum Dipilih',
                    text: 'Silakan pilih salah satu metode pembayaran yang tersedia terlebih dahulu.',
                });
                return;
            }

            // Prepare Data for SweetAlert
            const ticketRows = document.querySelectorAll('.ticket-row');
            let ticketHtml = '<div class="checkout-ticket-box">';

            ticketRows.forEach(row => {
                const category = escapeHtml(row.querySelector('.ticket-tier-badge').textContent.trim());
                const qtyInfo = row.querySelector('.ticket-qty-info').textContent;
                const total = escapeHtml(row.querySelector('.ticket-total-cell').textContent.trim());
                const qtyParts = qtyInfo.split(/x|×/i);
                const qty = escapeHtml((qtyParts[qtyParts.length - 1] || '').trim());

                ticketHtml += `
                    <div class="checkout-ticket-row">
                        <span class="checkout-ticket-name">${category} (${qty}x)</span>
                        <span class="checkout-ticket-price">${total}</span>
                    </div>`;
            });

            const selectedPayElement = document.querySelector('.pay-option.selected .pay-name');
            const paymentName = escapeHtml(selectedPayElement ? selectedPayElement.textContent.trim() : 'N/A');
            const grandTotal = escapeHtml(document.getElementById('grand-total').textContent.trim());
            const ticketHolderNameInput = form.elements.namedItem('ticket_holder_name');
            const ticketHolderName = escapeHtml(ticketHolderNameInput ? ticketHolderNameInput.value.trim() : '');
            const ticketRecipientEmail = escapeHtml(@json(Auth::user()->email));

            ticketHtml += `
                <div class="checkout-ticket-total">
                    <span>Total Bayar</span>
                    <strong>${grandTotal}</strong>
                </div>
            </div>`;

            openCheckoutSwal({
                title: 'Konfirmasi Pesanan',
                html: `
                    <div class="checkout-summary">
                        <p class="checkout-summary-copy">Periksa kembali detail pesanan Anda sebelum melanjutkan pembayaran.</p>
                        <div class="checkout-summary-meta">
                            <div class="checkout-summary-card">
                                <label class="checkout-summary-label">Nama Pemegang Tiket</label>
                                <div class="checkout-summary-value">${ticketHolderName}</div>
                            </div>
                            <div class="checkout-summary-card">
                                <label class="checkout-summary-label">Email Penerima Tiket</label>
                                <div class="checkout-summary-value">${ticketRecipientEmail}</div>
                                <small class="checkout-summary-note">Tiket akan dikirim ke email akun Anda</small>
                            </div>
                            <div class="checkout-summary-card">
                                <label class="checkout-summary-label">Metode Pembayaran</label>
                                <div class="checkout-summary-value">${paymentName}</div>
                            </div>
                        </div>
                        <label class="checkout-summary-label">Item Tiket</label>
                        ${ticketHtml}
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Lanjutkan Pembayaran',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                width: '460px',
            }).then((result) => {
                if (result.isConfirmed) {
                    if (checkoutPaymentOtp.enabled) {
                        handlePaymentOtpFlow();
                    } else {
                        submitPaymentAfterOtpVerification();
                    }
                }
            });
        }
    </script>

@endsection
