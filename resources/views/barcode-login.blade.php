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
                    <div class="flex items-center justify-between mb-2">
                        <label for="password" class="block text-xs font-bold text-slate-500 uppercase">Password</label>
                        <a href="{{ route('forgot') }}" class="text-xs font-bold text-indigo-400 hover:text-indigo-300 transition-colors">Lupa Password?</a>
                    </div>
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

            <div class="mt-8">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-white/5"></div>
                    </div>
                    <div class="relative flex justify-center text-xs">
                        <span class="px-3 bg-[#16152a] text-slate-500 uppercase tracking-widest font-bold">Atau masuk dengan</span>
                    </div>
                </div>

                <div class="mt-6">
                    <a href="{{ route('auth.google', ['redirect' => route('barcode.generate', ['data' => $data], false)]) }}" class="w-full inline-flex justify-center py-4 px-4 rounded-2xl bg-white/5 border border-white/5 text-white text-sm font-bold hover:bg-white/10 transition-all duration-200 gap-3 items-center">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                        </svg>
                        <span>Google</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
