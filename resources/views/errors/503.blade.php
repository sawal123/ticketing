<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Mode - TiketKonser</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --accent: #0ea5e9;
            --bg: #0f172a;
            --text: #f8fafc;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* Animated Background Blobs */
        .blob {
            position: absolute;
            width: 500px;
            height: 500px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            filter: blur(80px);
            border-radius: 50%;
            z-index: -1;
            opacity: 0.15;
            animation: move 20s infinite alternate;
        }

        .blob-1 { top: -100px; left: -100px; }
        .blob-2 { bottom: -100px; right: -100px; animation-delay: -5s; }

        @keyframes move {
            from { transform: translate(0, 0) scale(1); }
            to { transform: translate(100px, 50px) scale(1.1); }
        }

        .container {
            text-align: center;
            padding: 2rem;
            max-width: 800px;
            width: 100%;
            z-index: 1;
        }

        .illustration {
            width: 100%;
            max-width: 450px;
            margin-bottom: 2rem;
            filter: drop-shadow(0 20px 50px rgba(99, 102, 241, 0.3));
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            background: linear-gradient(to right, #fff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.02em;
        }

        p {
            font-size: 1.25rem;
            color: #94a3b8;
            margin-bottom: 2.5rem;
            line-height: 1.6;
            font-weight: 300;
        }

        .badge {
            display: inline-block;
            padding: 0.5rem 1.25rem;
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.2);
            color: var(--primary);
            border-radius: 100px;
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 1.5rem;
        }

        .timer-box {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .timer-item {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 1.5rem;
            border-radius: 24px;
            min-width: 100px;
            backdrop-filter: blur(10px);
        }

        .timer-val {
            display: block;
            font-size: 2rem;
            font-weight: 800;
            color: #fff;
        }

        .timer-label {
            font-size: 0.75rem;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
            margin-top: 0.25rem;
        }

        .footer {
            margin-top: 2rem;
            font-size: 0.875rem;
            color: #475569;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 2rem;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 16px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.4);
        }

        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 20px 35px -5px rgba(99, 102, 241, 0.5);
        }

        @media (max-width: 640px) {
            h1 { font-size: 2.5rem; }
            p { font-size: 1rem; }
            .illustration { max-width: 300px; }
        }
    </style>
</head>
<body>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <div class="container">
        <div class="badge">Sistem Sedang Diperbarui</div>
        
        <!-- <img src="{{ asset('assets/images/maintenance.png') }}" alt="Maintenance" class="illustration"> -->

        <h1>Kami Akan Segera Kembali</h1>
        <p>Mohon maaf atas ketidaknyamanannya. Kami sedang melakukan pembaruan rutin untuk meningkatkan kualitas layanan Gotik bagi Anda.</p>

        @php
            $downFile = storage_path('framework/down');
            $wentDownAt = file_exists($downFile) ? filemtime($downFile) : time();
            $targetTime = $wentDownAt + (2 * 3600); // 2 hours later
        @endphp

        <div class="timer-box" x-data="countdown({{ $targetTime }})">
            <div class="timer-item">
                <span class="timer-val" id="hours">00</span>
                <span class="timer-label">Jam</span>
            </div>
            <div class="timer-item">
                <span class="timer-val" id="minutes">00</span>
                <span class="timer-label">Menit</span>
            </div>
            <div class="timer-item">
                <span class="timer-val" id="seconds">00</span>
                <span class="timer-label">Detik</span>
            </div>
        </div>

        <div>
            <a href="mailto:gotikevent@gmail.com" class="btn">
                Hubungi Support
            </a>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} TiketKonser. All rights reserved.
        </div>
    </div>

    <script>
        function startCountdown(targetTimestamp) {
            const targetDate = new Date(targetTimestamp * 1000).getTime();

            const timer = setInterval(function() {
                const now = new Date().getTime();
                const distance = targetDate - now;

                if (distance < 0) {
                    clearInterval(timer);
                    document.getElementById("hours").innerHTML = "00";
                    document.getElementById("minutes").innerHTML = "00";
                    document.getElementById("seconds").innerHTML = "00";
                    return;
                }

                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                document.getElementById("hours").innerHTML = hours.toString().padStart(2, '0');
                document.getElementById("minutes").innerHTML = minutes.toString().padStart(2, '0');
                document.getElementById("seconds").innerHTML = seconds.toString().padStart(2, '0');
            }, 1000);
        }

        startCountdown({{ $targetTime }});
    </script>
</body>
</html>
