{{-- Field bersama form tambah & ubah indikator. Butuh: $indikator (nullable), $jenisList --}}

<div class="grid gap-4 sm:grid-cols-2">

    <div>
        <label for="jenis_indikator" class="form-label">Jenis Indikator <span class="text-red-500">*</span></label>
        <input type="text" name="jenis_indikator" id="jenis_indikator" list="jenis_indikator_list"
               value="{{ old('jenis_indikator', $indikator?->jenis_indikator) }}"
               placeholder="mis. agama" required
               class="form-input font-mono @error('jenis_indikator') border-red-400 @enderror">
        <datalist id="jenis_indikator_list">
            @foreach($jenisList as $j)
                <option value="{{ $j }}">
            @endforeach
        </datalist>
        <p class="mt-1 text-xs text-gray-400">
            Pilih jenis yang sudah ada supaya indikator ini muncul sekelompok, atau ketik baru untuk membuat kelompok baru.
        </p>
        @error('jenis_indikator') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="label" class="form-label">Label <span class="text-red-500">*</span></label>
        <input type="text" name="label" id="label"
               value="{{ old('label', $indikator?->label) }}" required
               class="form-input @error('label') border-red-400 @enderror">
        <p class="mt-1 text-xs text-gray-400">Teks yang tampil di legenda grafik dan tabel dashboard.</p>
        @error('label') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="urutan" class="form-label">Urutan Tampil <span class="text-red-500">*</span></label>
        <input type="number" name="urutan" id="urutan" min="0" max="9999"
               value="{{ old('urutan', $indikator?->urutan ?? 0) }}" required
               class="form-input @error('urutan') border-red-400 @enderror">
        <p class="mt-1 text-xs text-gray-400">Angka lebih kecil tampil lebih dulu di antara indikator sejenis.</p>
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
