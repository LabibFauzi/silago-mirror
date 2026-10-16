<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - SILAGO</title>
    <!-- Google Fonts Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: '#15803d',
                        deep: '#1b4d3e',
                        soft: '#ebf5f0',
                        line: '#e8ebed',
                        ink: '#111827',
                        muted: '#4b5563',
                        faint: '#9ca3af',
                        page: '#f9fafb'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-page antialiased">
    <div class="flex flex-col lg:flex-row min-h-screen">
        <!-- Left Side Brand (Hidden on mobile, visible on lg) -->
        <div class="hidden lg:flex lg:w-1/2 bg-deep text-white flex-col justify-center px-16">
            <h1 class="text-4xl font-bold mb-4 tracking-wide text-brand">SILAGO</h1>
            <h2 class="text-3xl font-semibold mb-6">Kelola Layanan SILAGO dengan Aman</h2>
            <p class="text-lg text-gray-300 max-w-md">Masuk ke panel admin untuk mengelola pengguna, barang, transaksi, dan laporan pada platform SILAGO.</p>
        </div>

        <!-- Right Side / Main Form -->
        <div class="w-full lg:w-1/2 flex flex-col bg-white">
            <!-- Mobile Brand Header -->
            <div class="lg:hidden w-full bg-deep text-white px-4 py-6 text-center">
                <h1 class="text-2xl font-bold mb-1 tracking-wide text-brand">SILAGO</h1>
                <p class="text-xs text-gray-300">Admin Panel</p>
            </div>

            <!-- Form Container -->
            <div class="flex-1 flex items-center justify-center px-4 sm:px-6 lg:px-8 py-8">
                <div class="w-full max-w-sm">
                    <h2 class="text-xl sm:text-2xl font-bold text-ink mb-6 text-center">Masuk ke Akun Admin</h2>
                    
                    @if($errors->any())
                        <div class="mb-4 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg text-sm">
                            <ul class="space-y-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.authenticate') ?? '#' }}" class="space-y-5">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-ink mb-2">Email atau Username</label>
                            <input type="text" name="login" class="w-full px-4 py-2.5 border border-line rounded-lg focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand text-sm" placeholder="superadmin@silago.id" value="{{ old('login') }}" required autofocus>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-ink mb-2">Kata Sandi</label>
                            <input type="password" name="password" class="w-full px-4 py-2.5 border border-line rounded-lg focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand text-sm" required>
                        </div>
                        
                        <button type="submit" class="w-full bg-deep hover:bg-brand text-white font-medium py-2.5 px-4 rounded-lg transition-colors text-sm">
                            Masuk
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
