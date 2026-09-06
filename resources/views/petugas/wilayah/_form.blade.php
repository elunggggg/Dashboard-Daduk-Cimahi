{{-- Field bersama form tambah & ubah wilayah. Butuh: $wilayah (nullable), $kecamatanList --}}

<div class="grid gap-4 sm:grid-cols-2">

    <div class="sm:col-span-2">
        <label for="kode_kemendagri" class="form-label">Kode Kemendagri <span class="text-red-500">*</span></label>
        <input type="text" name="kode_kemendagri" id="kode_kemendagri"
               value="{{ old('kode_kemendagri', $wilayah?->kode_kemendagri) }}"
               placeholder="32.77.01.1001" required
               class="form-input font-mono @error('kode_kemendagri') border-red-400 @enderror">
        <p class="mt-1 text-xs text-gray-400">Format: provinsi.kabkota.kecamatan.kelurahan (contoh: 32.77.01.1001).</p>
        @error('kode_kemendagri') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="nama_kelurahan" class="form-label">Nama Kelurahan <span class="text-red-500">*</span></label>
        <input type="text" name="nama_kelurahan" id="nama_kelurahan"
               value="{{ old('nama_kelurahan', $wilayah?->nama_kelurahan) }}" required
               class="form-input @error('nama_kelurahan') border-red-400 @enderror">
        @error('nama_kelurahan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="nama_kecamatan" class="form-label">Nama Kecamatan <span class="text-red-500">*</span></label>
        <input type="text" name="nama_kecamatan" id="nama_kecamatan" list="daftar-kecamatan"
               value="{{ old('nama_kecamatan', $wilayah?->nama_kecamatan) }}" required
               class="form-input @error('nama_kecamatan') border-red-400 @enderror">
        <datalist id="daftar-kecamatan">
            @foreach($kecamatanList as $kec)
                <option value="{{ $kec }}"></option>
            @endforeach
        </datalist>
        @error('nama_kecamatan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="luas_km2" class="form-label">Luas Wilayah (km²)</label>
        <input type="number" step="0.01" min="0" name="luas_km2" id="luas_km2"
               value="{{ old('luas_km2', $wilayah?->luas_km2) }}"
               class="form-input @error('luas_km2') border-red-400 @enderror">
        <p class="mt-1 text-xs text-gray-400">Opsional — dipakai untuk menghitung kepadatan penduduk.</p>
        @error('luas_km2') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

</div>
