{{-- Field bersama form tambah & ubah indikator. Butuh: $indikator (nullable), $jenisList --}}

<div class="grid gap-4 sm:grid-cols-2">

    <div>
        <label for="jenis_indikator" class="form-label">Kelompok Indikator <span class="text-red-500">*</span></label>
        <input type="text" name="jenis_indikator" id="jenis_indikator" list="jenis_indikator_list"
               value="{{ old('jenis_indikator', $indikator?->jenis_indikator) }}"
               placeholder="mis. agama" required
               class="form-input font-mono @error('jenis_indikator') border-red-400 @enderror">
        <datalist id="jenis_indikator_list">
            @foreach($jenisList as $j)
                <option value="{{ $j }}">{{ \Illuminate\Support\Str::headline($j) }}</option>
            @endforeach
        </datalist>
        <p class="mt-1 text-xs text-gray-500">
            Kode yang menyatukan beberapa kategori ke dalam satu grafik — mis. <code class="font-mono">agama</code>
            memuat Islam, Kristen, Katolik, dst.
        </p>
        <p class="mt-1 text-xs text-gray-400">
            Pilih kode yang sudah ada supaya kategori ini ikut ke grafik yang sama. Ketik kode baru
            hanya bila memang ingin membuat grafik baru. Gunakan huruf kecil dan garis bawah, tanpa spasi
            (mis. <code class="font-mono">kepemilikan_ktp</code>).
        </p>
        @error('jenis_indikator') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="label" class="form-label">Nama Kategori <span class="text-red-500">*</span></label>
        <input type="text" name="label" id="label"
               value="{{ old('label', $indikator?->label) }}" required
               placeholder="mis. Islam"
               class="form-input @error('label') border-red-400 @enderror">
        <p class="mt-1 text-xs text-gray-500">
            Teks yang dibaca pengunjung di legenda grafik, tabel, dan tooltip dashboard.
        </p>
        <p class="mt-1 text-xs text-gray-400">
            Tulis dengan kapitalisasi wajar dan boleh pakai spasi — mis. <em>Laki-laki</em>,
            <em>Usia 0&ndash;4 tahun</em>, <em>Belum Memiliki Akta Lahir</em>.
        </p>
        @error('label') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="urutan" class="form-label">Urutan Tampil <span class="text-red-500">*</span></label>
        <input type="number" name="urutan" id="urutan" min="0" max="9999"
               value="{{ old('urutan', $indikator?->urutan ?? 0) }}" required
               class="form-input @error('urutan') border-red-400 @enderror">
        <p class="mt-1 text-xs text-gray-400">Angka lebih kecil tampil lebih dulu di antara kategori dalam kelompok yang sama.</p>
        @error('urutan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="flex items-end pb-1.5">
        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="aktif" value="1" class="rounded border-gray-300"
                   @checked(old('aktif', $indikator?->aktif ?? true))>
            Aktif (tampil di dashboard publik)
        </label>
    </div>

</div>
