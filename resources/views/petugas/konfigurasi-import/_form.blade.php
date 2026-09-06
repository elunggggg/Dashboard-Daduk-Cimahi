{{-- Field bersama form tambah & ubah konfigurasi import. Butuh: $konfigurasi (nullable), $profilList, $sheetList --}}

<div class="space-y-5">

    <div>
        <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Identitas</p>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="nama_profil" class="form-label">Nama Profil <span class="text-red-500">*</span></label>
                <input type="text" name="nama_profil" id="nama_profil" list="profil_list"
                       value="{{ old('nama_profil', $konfigurasi?->nama_profil ?? \App\Models\KonfigurasiImport::PROFIL_BAWAAN) }}" required
                       class="form-input @error('nama_profil') border-red-400 @enderror">
                <datalist id="profil_list">
                    @foreach($profilList as $p)<option value="{{ $p }}">@endforeach
                </datalist>
                @error('nama_profil') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="nama_sheet" class="form-label">Nama Sheet di Excel <span class="text-red-500">*</span></label>
                <input type="text" name="nama_sheet" id="nama_sheet" list="sheet_list"
                       value="{{ old('nama_sheet', $konfigurasi?->nama_sheet) }}" required
                       placeholder="mis. PendudukJK"
                       class="form-input font-mono @error('nama_sheet') border-red-400 @enderror">
                <datalist id="sheet_list">
                    @foreach($sheetList as $s)<option value="{{ $s }}">@endforeach
                </datalist>
                @error('nama_sheet') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="alias_sheet" class="form-label">Nama Alternatif Sheet</label>
                <input type="text" name="alias_sheet" id="alias_sheet"
                       value="{{ old('alias_sheet', $konfigurasi?->alias_sheet) }}"
                       placeholder="Kosongkan bila tidak ada"
                       class="form-input font-mono @error('alias_sheet') border-red-400 @enderror">
                <p class="mt-1 text-xs text-gray-400">Dicoba otomatis bila nama sheet utama tidak ada di berkas (mis. berganti nama antar semester).</p>
                @error('alias_sheet') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="jenis_indikator" class="form-label">Jenis Indikator <span class="text-red-500">*</span></label>
                <input type="text" name="jenis_indikator" id="jenis_indikator"
                       value="{{ old('jenis_indikator', $konfigurasi?->jenis_indikator) }}" required
                       placeholder="mis. agama"
                       class="form-input font-mono @error('jenis_indikator') border-red-400 @enderror">
                @error('jenis_indikator') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label for="label" class="form-label">Label <span class="text-red-500">*</span></label>
                <input type="text" name="label" id="label"
                       value="{{ old('label', $konfigurasi?->label) }}" required
                       placeholder="mis. Islam"
                       class="form-input @error('label') border-red-400 @enderror">
                <p class="mt-1 text-xs text-gray-400">Nama yang tampil di dashboard (lewat Kelola Indikator) — boleh beda ejaan dari teks di berkas Excel.</p>
                @error('label') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <div class="pt-4 border-t border-gray-100">
        <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Cara Membaca Kolom</p>
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="sm:col-span-2">
                <label for="teks_header" class="form-label">Teks Header yang Dicari <span class="text-red-500">*</span></label>
                <input type="text" name="teks_header" id="teks_header"
                       value="{{ old('teks_header', $konfigurasi?->teks_header) }}" required
                       placeholder="mis. Islam"
                       class="form-input @error('teks_header') border-red-400 @enderror">
                <p class="mt-1 text-xs text-gray-400">Persis seperti tertulis di berkas Excel (besar/kecil huruf tidak masalah).</p>
                @error('teks_header') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="offset_kolom" class="form-label">Offset Kolom <span class="text-red-500">*</span></label>
                <input type="number" name="offset_kolom" id="offset_kolom" min="0" max="200"
                       value="{{ old('offset_kolom', $konfigurasi?->offset_kolom ?? 0) }}" required
                       class="form-input @error('offset_kolom') border-red-400 @enderror">
                <p class="mt-1 text-xs text-gray-400">0 = tepat di kolom header. Dipakai untuk header bertingkat (L | P | Total).</p>
                @error('offset_kolom') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-3">
                <label for="orientasi" class="form-label">Orientasi Sheet <span class="text-red-500">*</span></label>
                @php $orientasi = old('orientasi', $konfigurasi?->orientasi ?? \App\Models\KonfigurasiImport::ORIENTASI_BARIS); @endphp
                <select name="orientasi" id="orientasi" required class="form-select @error('orientasi') border-red-400 @enderror">
                    <option value="{{ \App\Models\KonfigurasiImport::ORIENTASI_BARIS }}" @selected($orientasi === \App\Models\KonfigurasiImport::ORIENTASI_BARIS)>
                        Baris — satu baris per kelurahan (paling umum)
                    </option>
                    <option value="{{ \App\Models\KonfigurasiImport::ORIENTASI_KOLOM }}" @selected($orientasi === \App\Models\KonfigurasiImport::ORIENTASI_KOLOM)>
                        Kolom — bersusun terbalik, kelurahan jadi kolom (mis. KelompokUmur)
                    </option>
                    <option value="{{ \App\Models\KonfigurasiImport::ORIENTASI_BLOK }}" @selected($orientasi === \App\Models\KonfigurasiImport::ORIENTASI_BLOK)>
                        Blok — nama wilayah cuma di baris pertama tiap kelompok baris (mis. AngkatanKerja)
                    </option>
                </select>
                @error('orientasi') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <div class="pt-4 border-t border-gray-100">
        <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Deteksi Kolom Wilayah</p>
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label for="teks_header_wilayah" class="form-label">Teks Header Wilayah <span class="text-red-500">*</span></label>
                <input type="text" name="teks_header_wilayah" id="teks_header_wilayah"
                       value="{{ old('teks_header_wilayah', $konfigurasi?->teks_header_wilayah ?? 'Wilayah') }}" required
                       class="form-input @error('teks_header_wilayah') border-red-400 @enderror">
                @error('teks_header_wilayah') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="baris_mulai_pencarian_header" class="form-label">Mulai Baris Ke- <span class="text-red-500">*</span></label>
                <input type="number" name="baris_mulai_pencarian_header" id="baris_mulai_pencarian_header" min="0" max="200"
                       value="{{ old('baris_mulai_pencarian_header', $konfigurasi?->baris_mulai_pencarian_header ?? 0) }}" required
                       class="form-input @error('baris_mulai_pencarian_header') border-red-400 @enderror">
                <p class="mt-1 text-xs text-gray-400">0 = dari baris paling atas. Naikkan bila sheet punya sisa tabel rekap di baris atas yang mengganggu (lihat SHBKEL).</p>
                @error('baris_mulai_pencarian_header') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="baris_maks_pencarian_header" class="form-label">Sampai Baris Ke- <span class="text-red-500">*</span></label>
                <input type="number" name="baris_maks_pencarian_header" id="baris_maks_pencarian_header" min="1" max="200"
                       value="{{ old('baris_maks_pencarian_header', $konfigurasi?->baris_maks_pencarian_header ?? 12) }}" required
                       class="form-input @error('baris_maks_pencarian_header') border-red-400 @enderror">
                @error('baris_maks_pencarian_header') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <div class="pt-4 border-t border-gray-100">
        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="aktif" value="1" class="rounded border-gray-300"
                   @checked(old('aktif', $konfigurasi?->aktif ?? true))>
            Aktif (dipakai saat import berjalan)
        </label>
    </div>

</div>
