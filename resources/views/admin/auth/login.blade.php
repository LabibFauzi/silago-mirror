<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk ke Akun Admin - SILAGO</title>

    <!-- Google Fonts: Plus Jakarta Sans (font di Figma) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
                    },
                },
            },
        }
    </script>

    <style>
        /* Pola kotak halus di background area utama (Figma: garis 1px tiap 28px) */
        .bg-grid {
            background-image:
                linear-gradient(90deg, rgba(22, 101, 52, 0.04) 1px, transparent 1px),
                linear-gradient(180deg, rgba(22, 101, 52, 0.04) 1px, transparent 1px);
            background-size: 28px 28px;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between bg-slate-50 font-sans antialiased text-slate-900">

    <!-- Header -->
    <header class="relative z-30 bg-white/95 backdrop-blur-[2px] border-b border-slate-200/80">
        <div class="mx-auto flex h-16 max-w-[1280px] items-center px-6">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5" aria-label="SILAGO Admin">
                <span class="text-2xl font-extrabold leading-8 tracking-[-0.6px] text-green-800">SILAGO</span>
                <span class="rounded-full border border-green-100 bg-[#ebf4ee] px-[9px] py-[3px] text-[11px] font-bold uppercase leading-[16.5px] tracking-[0.55px] text-green-800">Admin</span>
            </a>
        </div>
    </header>

    <!-- Konten utama -->
    <main class="relative z-20 flex items-center justify-center overflow-hidden px-6 py-12">
        <div class="bg-grid pointer-events-none absolute inset-0 opacity-80"></div>
        <div class="pointer-events-none absolute -left-32 -top-32 h-96 w-96 rounded-full bg-emerald-200/30 blur-[32px]"></div>
        <div class="pointer-events-none absolute -bottom-32 -right-32 h-96 w-96 rounded-full bg-emerald-100/40 blur-[32px]"></div>

        <div class="relative grid min-h-[640px] w-full max-w-[1040px] grid-cols-12 overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-[0_8px_30px_rgba(0,0,0,0.04)]">

            <!-- Panel kiri (hanya tampil di layar lebar) -->
            <section class="relative hidden flex-col gap-[45px] overflow-hidden bg-[linear-gradient(124.15deg,#166534_0%,#14532d_100%)] p-10 lg:col-span-5 lg:flex" aria-hidden="true">
                <div class="relative z-10 flex flex-col gap-[11px]">
                    <div class="flex w-fit items-center gap-2 rounded-full border border-white/15 bg-white/10 px-[13px] py-[5px] backdrop-blur-[2px]">
                        <svg class="h-3.5 w-3.5 text-emerald-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>
                        <span class="whitespace-nowrap text-xs font-semibold leading-4 text-emerald-100">Portal Verifikasi Terpadu</span>
                    </div>

                    <h2 class="pt-5 text-[30px] font-extrabold leading-9 tracking-[-0.75px] text-white">
                        Kelola Layanan SILAGO<br>dengan Aman
                    </h2>

                    <p class="text-sm leading-[22.75px] text-emerald-100/80">
                        Pusat kendali dan tindak lanjut laporan pengguna, manajemen stok barang, serta pengawasan transaksi realtime.
                    </p>

                    <div class="flex flex-col gap-3.5 pt-5">
                        <div class="flex items-center gap-3 rounded-lg border border-white/10 bg-white/10 p-[11px]">
                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded bg-white/15 text-emerald-100">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" x2="4" y1="22" y2="15"/></svg>
                            </div>
                            <div>
                                <div class="text-xs font-semibold leading-4 text-white">Tinjauan Laporan</div>
                                <div class="text-[11px] leading-4 text-emerald-200/70">Verifikasi sengketa transaksi &amp; konten ilegal</div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 rounded-lg border border-white/10 bg-white/10 p-[11px]">
                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded bg-white/15 text-emerald-100">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"/><path d="M12 22V12"/><polyline points="3.29 7 12 12 20.71 7"/></svg>
                            </div>
                            <div>
                                <div class="text-xs font-semibold leading-4 text-white">Katalog Barang</div>
                                <div class="text-[11px] leading-4 text-emerald-200/70">Monitor barang dan kategori aktif</div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 rounded-lg border border-white/10 bg-white/10 p-[11px]">
                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded bg-white/15 text-emerald-100">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
                            </div>
                            <div>
                                <div class="text-xs font-semibold leading-4 text-white">Audit Log Realtime</div>
                                <div class="text-[11px] leading-4 text-emerald-200/70">Catatan aktivitas tindakan staf administrasi</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Garis pemisah di dasar panel -->
                <div class="relative z-10 pt-8">
                    <div class="h-[49px] border-t border-white/10"></div>
                </div>

                <!-- Bentuk dekoratif -->
                <div class="pointer-events-none absolute -bottom-12 -right-12 h-56 w-56 rounded-full bg-white/5"></div>
                <div class="pointer-events-none absolute right-12 top-6 h-36 w-36 rounded-full bg-emerald-400/10 blur-[12px]"></div>
            </section>

            <!-- Panel kanan: form -->
            <section class="col-span-12 flex flex-col justify-center bg-white px-5 py-8 sm:px-8 sm:py-10 lg:col-span-7 lg:px-[78px] lg:py-12">
                <div class="flex w-full max-w-[448px] flex-col gap-[23.5px]">

                    <div class="flex flex-col gap-2">
                        <h1 class="text-2xl font-bold leading-8 tracking-[-0.6px] text-slate-900">Masuk ke Akun Admin</h1>
                        <p class="text-sm leading-5 text-slate-500">
                            Silakan masukkan email atau username dan kata sandi Anda untuk melanjutkan.
                        </p>
                    </div>

                    @if ($errors->any())
                        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600">
                            <ul class="space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ Route::has('admin.authenticate') ? route('admin.authenticate') : '#' }}" class="flex flex-col gap-5 pt-1" novalidate>
                        @csrf

                        <!-- Email atau username -->
                        <div class="flex flex-col gap-2">
                            <label for="login" class="text-xs font-semibold uppercase leading-4 tracking-[0.6px] text-slate-700">Email atau Username</label>
                            <div class="relative rounded-lg shadow-sm">
                                <span class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center text-slate-400">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-4 8"/></svg>
                                </span>
                                <input
                                    id="login"
                                    type="text"
                                    name="login"
                                    value="{{ old('login') }}"
                                    placeholder="superadmin@silago.id"
                                    autocomplete="username"
                                    autofocus
                                    required
                                    class="block h-[42px] w-full rounded-lg border bg-white pl-[41px] pr-[17px] text-sm leading-5 text-slate-900 outline-none transition placeholder:text-slate-900 focus:border-green-800 focus:ring-[3px] focus:ring-green-800/10 {{ $errors->has('login') ? 'border-red-500' : 'border-slate-300' }}">
                            </div>
                        </div>

                        <!-- Kata sandi -->
                        <div class="flex flex-col gap-2">
                            <div class="flex items-center justify-between">
                                <label for="password" class="text-xs font-semibold uppercase leading-4 tracking-[0.6px] text-slate-700">Kata Sandi</label>
                                <a href="{{ Route::has('admin.password.request') ? route('admin.password.request') : '#' }}" class="text-xs font-medium leading-4 text-green-800 hover:underline">Lupa kata sandi?</a>
                            </div>
                            <div class="relative rounded-lg shadow-sm">
                                <span class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center text-slate-400">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                </span>
                                <input
                                    id="password"
                                    type="password"
                                    name="password"
                                    placeholder="••••••••••••"
                                    autocomplete="current-password"
                                    required
                                    class="block h-[42px] w-full rounded-lg border bg-white pl-[41px] pr-[45px] text-sm leading-5 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-green-800 focus:ring-[3px] focus:ring-green-800/10 {{ $errors->has('password') ? 'border-red-500' : 'border-slate-300' }}">
                                <button type="button" id="togglePassword" aria-label="Tampilkan atau sembunyikan kata sandi" aria-pressed="false" class="absolute inset-y-0 right-0 flex items-center pl-2 pr-3.5 text-slate-400 hover:text-slate-600">
                                    <svg id="iconEye" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
                                    <svg id="iconEyeOff" class="hidden h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"/><path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"/><path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"/><path d="m2 2 20 20"/></svg>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-green-800 text-sm font-semibold leading-5 text-white drop-shadow-[0_1px_1px_rgba(6,78,59,0.2)] transition-colors hover:bg-green-900">
                            <span>Masuk ke Dashboard</span>
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        </button>
                    </form>

                    <!-- Pemberitahuan keamanan -->
                    <div class="flex items-start gap-3 rounded-lg border border-slate-100 bg-slate-50/70 px-[15px] pb-[15px] pt-4 sm:pt-[33.5px]">
                        <div class="pt-0.5 text-green-800">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/></svg>
                        </div>
                        <p class="text-xs leading-[19.5px] text-slate-600 sm:pr-[38px]">
                            <strong class="font-semibold text-slate-700">Pemberitahuan Keamanan:</strong>
                            Halaman ini ditujukan khusus untuk personel berwenang SILAGO. Setiap aktivitas masuk dipantau melalui Audit Log sistem.
                        </p>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- Footer -->
    <footer class="relative z-10 border-t border-slate-200/70 bg-white pb-5 pt-[21px]">
        <div class="mx-auto flex max-w-[1280px] flex-col gap-3 px-6 text-xs leading-4 text-slate-500 md:flex-row md:items-center md:justify-between">
            <div class="flex flex-wrap items-center gap-2">
                <span class="font-bold text-green-800">SILAGO</span>
                <span>&bull;</span>
                <span>Hak Cipta &copy; 2026 PT Silago Nusantara. Seluruh hak cipta dilindungi.</span>
            </div>
            <div class="flex flex-wrap items-center gap-x-5 gap-y-2">
                <a href="#" class="hover:text-slate-700">Kebijakan Privasi Internal</a>
                <a href="#" class="hover:text-slate-700">Ketentuan Layanan</a>
                <a href="#" class="flex items-center gap-1.5 hover:text-slate-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    <span>Status Server</span>
                </a>
            </div>
        </div>
    </footer>

    <script>
        (function () {
            var btn = document.getElementById('togglePassword');
            var input = document.getElementById('password');
            var eye = document.getElementById('iconEye');
            var eyeOff = document.getElementById('iconEyeOff');

            btn.addEventListener('click', function () {
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                eye.classList.toggle('hidden', show);
                eyeOff.classList.toggle('hidden', !show);
                btn.setAttribute('aria-pressed', show ? 'true' : 'false');
            });
        })();
    </script>
</body>
</html>
