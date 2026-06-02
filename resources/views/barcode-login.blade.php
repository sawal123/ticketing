<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#0b0a1a]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login E-Ticket</title>
    <link rel="shortcut icon" href="{{ asset('storage/icon/' . ($logo[0]->icon ?? '')) }}" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="font-sans antialiased min-h-screen bg-[#0b0a1a] text-white flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-md">
        <div class="flex justify-center mb-8">
            <a href="{{ url('/') }}">
                <img src="{{ asset('storage/logo/' . ($logo[0]->logo ?? '')) }}" alt="Logo" class="h-16 w-auto object-contain">
            </a>
        </div>

        <div class="bg-[#16152a] border border-white/5 py-10 px-6 shadow-2xl rounded-[2rem] sm:px-10">
            <h1 class="text-2xl font-extrabold text-center mb-2">Login E-Ticket</h1>
            <p class="text-slate-400 text-center text-sm mb-8">Masuk dengan akun pemilik invoice untuk melihat barcode.</p>

            @if (session('error'))
                <div class="mb-6 p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs font-medium">
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('barcode.login.submit', ['data' => $data]) }}" method="post" class="space-y-6">
                @csrf
                <div>
                    <label for="email" class="block text-xs font-bold text-slate-500 uppercase mb-2">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="block w-full px-4 py-4 bg-[#1e1d35] border-0 ring-1 ring-white/5 focus:ring-2 focus:ring-indigo-500 rounded-2xl text-white text-sm" placeholder="nama@email.com">
                    @error('email')
                        <p class="mt-2 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold text-slate-500 uppercase mb-2">Password</label>
                    <input id="password" name="password" type="password" required class="block w-full px-4 py-4 bg-[#1e1d35] border-0 ring-1 ring-white/5 focus:ring-2 focus:ring-indigo-500 rounded-2xl text-white text-sm" placeholder="Password">
                    @error('password')
                        <p class="mt-2 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center">
                    <input id="remember" name="remember" type="checkbox" value="1" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-white/10 rounded bg-[#1e1d35]">
                    <label for="remember" class="ml-2 block text-sm text-slate-400">Ingat saya</label>
                </div>

                <button type="submit" class="w-full py-4 px-4 rounded-2xl text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                    Login dan Lihat Barcode
                </button>
            </form>
        </div>
    </div>
</body>
</html>
