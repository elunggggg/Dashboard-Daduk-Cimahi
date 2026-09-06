{{-- Field bersama form tambah & ubah pengguna. Butuh: $pengguna (nullable) --}}

<div class="grid gap-4 sm:grid-cols-2">

    <div>
        <label for="name" class="form-label">Nama Lengkap <span class="text-red-500">*</span></label>
        <input type="text" name="name" id="name" value="{{ old('name', $pengguna?->name) }}" required
               class="form-input @error('name') border-red-400 @enderror">
        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="email" class="form-label">Email <span class="text-red-500">*</span></label>
        <input type="email" name="email" id="email" value="{{ old('email', $pengguna?->email) }}" required
               class="form-input @error('email') border-red-400 @enderror">
        @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="password" class="form-label">
            Kata Sandi @if($pengguna) <span class="font-normal text-gray-400">(kosongkan jika tidak diubah)</span> @else <span class="text-red-500">*</span> @endif
        </label>
        <input type="password" name="password" id="password" autocomplete="new-password"
               @required(! $pengguna)
               class="form-input @error('password') border-red-400 @enderror">
        <p class="mt-1 text-xs text-gray-400">Minimal 8 karakter, mengandung huruf dan angka.</p>
        @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="password_confirmation" class="form-label">
            Konfirmasi Kata Sandi @unless($pengguna) <span class="text-red-500">*</span> @endunless
        </label>
        <input type="password" name="password_confirmation" id="password_confirmation" autocomplete="new-password"
               @required(! $pengguna)
               class="form-input">
    </div>

</div>
