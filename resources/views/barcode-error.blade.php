<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Akses Ditolak</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-[#0b0a1a] text-white flex items-center justify-center px-4">
    <div class="max-w-md w-full text-center bg-[#16152a] border border-white/5 rounded-2xl p-8 shadow-2xl">
        <h1 class="text-2xl font-bold mb-3">Akses Ditolak</h1>
        <p class="text-slate-400 mb-6">{{ $message }}</p>
        <a href="{{ url('/') }}" class="inline-flex justify-center px-5 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-sm font-bold transition">
            Kembali ke Beranda
        </a>
    </div>
</body>
</html>
