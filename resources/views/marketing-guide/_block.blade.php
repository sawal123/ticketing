@php
    $type = $block->type;
    $data = $block->data ?? [];
@endphp

@if ($type === 'text')
    @if (! empty($data['badge']))
        <div class="badge">{{ $data['badge'] }}</div>
        <h1>{{ $data['title'] ?? '' }}</h1>
        @if (! empty($data['subtitle']))
            <p class="subtitle">{{ $data['subtitle'] }}</p>
        @endif
        @if (! empty($data['show_recipient']) && ! empty($recipientName))
            <p style="color: var(--text-secondary); margin-bottom: 12px;">
                Panduan ini disiapkan untuk {{ $recipientName }}
            </p>
        @endif
        @if (! empty($data['show_expiry']) && ! empty($expiresAt))
            <p style="color: var(--text-secondary); margin-bottom: 32px;">
                <i class="fas fa-calendar-alt"></i> Akses tersedia hingga {{ $expiresAt->locale('id')->translatedFormat('j F Y') }}
            </p>
        @endif
        @if (! empty($data['cta']))
            <a href="{{ $data['cta']['href'] ?? '#' }}" class="btn btn-primary">
                @if (! empty($data['cta']['icon']))
                    <i class="fas fa-{{ $data['cta']['icon'] }}"></i>
                @endif
                {{ $data['cta']['label'] ?? '' }}
            </a>
        @endif
    @elseif (! empty($data['intro']))
        <p style="{{ ! empty($data['intro_spacing']) ? 'margin-bottom: ' . $data['intro_spacing'] . ';' : '' }}">{{ $data['intro'] }}</p>
    @endif
@elseif ($type === 'workflow')
    <div class="workflow">
        @foreach ($data['steps'] ?? [] as $step)
            <div class="workflow-step">
                <div class="workflow-icon"><i class="fas fa-{{ $step['icon'] ?? 'arrow-right' }}"></i></div>
                <div class="workflow-content">
                    <h4>{{ $step['title'] ?? '' }}</h4>
                    <p>{{ $step['description'] ?? '' }}</p>
                </div>
            </div>
        @endforeach
    </div>
@elseif ($type === 'flow')
    <div class="flow-diagram">
        @foreach ($data['boxes'] ?? [] as $index => $box)
            @if ($index > 0)
                <div class="flow-arrow">→</div>
            @endif
            <div class="flow-box">
                @if (! empty($box['icon']))
                    <i class="fas fa-{{ $box['icon'] }}"></i>
                @endif
                {{ $box['label'] ?? '' }}
            </div>
        @endforeach
    </div>
@elseif ($type === 'cards')
    <div class="grid grid-{{ $data['columns'] ?? 3 }}">
        @foreach ($data['cards'] ?? [] as $card)
            <div class="card">
                <h3 style="margin-bottom: 16px;">
                    @if (! empty($card['icon']))
                        <i class="fas fa-{{ $card['icon'] }}" style="color: var(--primary); margin-right: 8px;"></i>
                    @endif
                    {{ $card['title'] ?? '' }}
                </h3>
                <p style="margin-bottom: 0;">{{ $card['body'] ?? '' }}</p>
            </div>
        @endforeach
    </div>
@elseif ($type === 'placeholder')
    <div class="placeholder">
        <div class="placeholder-icon"><i class="fas fa-{{ $data['icon'] ?? 'cog' }}"></i></div>
        @if (! empty($data['title']))
            <p><strong>{{ $data['title'] }}</strong></p>
        @endif
        @if (! empty($data['caption']))
            <p style="margin-bottom: 0; font-size: 13px;">{{ $data['caption'] }}</p>
        @endif
    </div>
@elseif ($type === 'tickets')
    <div class="grid grid-3">
        @foreach ($data['tickets'] ?? [] as $ticket)
            <div class="ticket-card">
                <div class="ticket-type">{{ $ticket['type'] ?? '' }}</div>
                <div class="ticket-price">{{ $ticket['price'] ?? '' }}</div>
                <div class="ticket-quantity">{{ $ticket['quantity'] ?? '' }}</div>
                <p style="font-size: 13px; margin-bottom: 0;">{{ $ticket['description'] ?? '' }}</p>
            </div>
        @endforeach
    </div>
    @if (! empty($data['tip']))
        <p style="margin-top: 32px; text-align: center; color: var(--text-secondary);">
            💡 {{ $data['tip'] }}
        </p>
    @endif
@elseif ($type === 'stats')
    <div class="stats-grid">
        @foreach ($data['stats'] ?? [] as $stat)
            <div class="stat-card">
                <div class="stat-value">{{ $stat['value'] ?? '' }}</div>
                <div class="stat-label">{{ $stat['label'] ?? '' }}</div>
            </div>
        @endforeach
    </div>
@elseif ($type === 'qr')
    <div class="qr-mockup">
        <div class="qr-header">
            <div class="qr-event-name">{{ $data['event_name'] ?? '' }}</div>
            <div class="qr-event-date">{{ $data['event_date'] ?? '' }}</div>
        </div>
        <div class="qr-code"><i class="fas fa-{{ $data['icon'] ?? 'qrcode' }}"></i></div>
        <div class="qr-footer">
            @if (! empty($data['ticket_holder']))
                <p style="margin-bottom: 4px;">{{ $data['ticket_holder'] }} • {{ $data['ticket_type'] ?? '' }}</p>
            @endif
            @if (! empty($data['ticket_number']))
                <p style="margin-bottom: 4px;">{{ $data['ticket_number'] }}</p>
            @endif
            @if (! empty($data['entry_window']))
                <p style="margin-bottom: 4px;">{{ $data['entry_window'] }}</p>
            @endif
            @if (! empty($data['footer']))
                <p style="margin-bottom: 0;">{{ $data['footer'] }}</p>
            @endif
        </div>
    </div>
@elseif ($type === 'faq')
    <div class="faq-container">
        @foreach ($data['items'] ?? [] as $item)
            <div class="faq-item">
                <button class="faq-question">
                    <span>{{ $item['question'] ?? '' }}</span>
                    <span class="faq-icon"><i class="fas fa-chevron-down"></i></span>
                </button>
                <div class="faq-answer">
                    <p>{{ $item['answer'] ?? '' }}</p>
                </div>
            </div>
        @endforeach
    </div>
@elseif ($type === 'cta')
    <div class="cta-content">
        <h2>{{ $data['title'] ?? '' }}</h2>
        <p>{{ $data['subtitle'] ?? '' }}</p>
        @if (! empty($data['cta']))
            <a href="{{ $data['cta']['href'] ?? '#' }}" class="btn btn-large btn-cta">
                @if (! empty($data['cta']['icon']))
                    <i class="fas fa-{{ $data['cta']['icon'] }}"></i>
                @endif
                {{ $data['cta']['label'] ?? '' }}
            </a>
        @endif
    </div>
@endif