<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 0;
            padding: 32px;
            text-align: center;
        }
        h2 {
            font-size: 18px;
            margin: 0 0 8px;
        }
        .qr-wrap {
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            display: inline-block;
            margin: 12px 0;
        }
        img {
            width: 320px;
            height: 320px;
        }
        p {
            font-size: 11px;
            color: #475569;
            margin: 4px 0;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <h2>{{ $event->event }}</h2>
    <div class="qr-wrap">
        <img src="{{ $image }}" alt="QR {{ $event->event }}">
    </div>
    <p>Scan QR di atas untuk membeli tiket event ini.</p>
    <p>Link: {{ $scanUrl }}</p>
</body>
</html>
