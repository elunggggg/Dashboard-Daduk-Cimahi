@php
    $__pengaturan   = \App\Models\PengaturanTampilan::current();
    $__logoUrl      = $__pengaturan->logo_url;
    $__namaSistem   = $__pengaturan->nama_sistem_tampil;
    $__namaInstansi = $__pengaturan->nama_instansi_tampil;
@endphp
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — {{ $__namaSistem }} {{ $__namaInstansi }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-50 flex items-center justify-center p-4 antialiased">

    <div class="w-full max-w-md">
        {{-- Card --}}
        <div class="card p-8">
            {{-- Logo --}}
            <div class="flex flex-col items-center mb-8">
                @if ($__logoUrl)
                    <img src="{{ $__logoUrl }}" alt="Logo {{ $__namaSistem }}"
                         class="w-14 h-14 object-contain mb-4">
                @else
                    <div class="w-14 h-14 bg-brand-700 rounded-2xl flex items-center justify-center mb-4 shadow-lg">
                        <span class="text-white text-xl font-black">DC</span>
                    </div>
                @endif
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Masuk ke {{ $__namaSistem }}</h1>
                <p class="text-sm text-gray-500 mt-1 text-center">{{ $__namaInstansi }}</p>
            </div>

            {{-- Session errors --}}
            @if($errors->any())
                <div class="alert-error mb-4">
                    <p class="font-medium">Login gagal:</p>
                    <ul class="mt-1 list-disc list-inside text-xs">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="form-label">Email</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="nama@instansi.go.id"
                        class="form-input @error('email') border-red-400 @enderror"
                    >
                    @error('email')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="password" class="form-label mb-0">Kata Sandi</label>
                    </div>
                    <div x-data="{ show: false }" class="relative">
                        <input
                            id="password"
                            :type="show ? 'text' : 'password'"
                            name="password"
                            required
                            autocomplete="current-password"
                            class="form-input pr-10"
                        >
                        <button type="button"
                                @click="show = !show"
                                class="absolute inset-y-0 right-0 px-3 text-gray-400 hover:text-gray-600">
                            <i class="bi" :class="show ? 'bi-eye-slash' : 'bi-eye'"></i>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-2">
                    <input id="remember" type="checkbox" name="remember" class="w-4 h-4 rounded border-gray-300 text-brand-600">
                    <label for="remember" class="text-sm text-gray-600">Ingat saya</label>
                </div>

                <button type="submit" class="btn-primary w-full justify-center py-2.5">
                    <i class="bi bi-box-arrow-in-right"></i>
                    Masuk
                </button>
            </form>
        </div>

        {{-- Note --}}
        <p class="text-center text-xs text-gray-400 mt-4">
            Akses hanya untuk petugas Disdukcapil yang berwenang.
        </p>

        <div class="text-center mt-3">
            <a href="{{ route('dashboard.publik') }}" class="text-xs text-brand-600 hover:underline">
                <i class="bi bi-arrow-left me-1"></i>Kembali ke Dashboard Publik
            </a>
        </div>
    </div>

</body>
</html>
