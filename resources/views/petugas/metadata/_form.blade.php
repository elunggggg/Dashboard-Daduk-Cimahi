{{-- Field bersama form tambah & ubah metadata. Butuh: $metadata (nullable), $modulList --}}

<div class="grid gap-4 sm:grid-cols-2">

    <div>
        <label for="jenis_indikator" class="form-label">Kode Indikator <span class="text-red-500">*</span></label>
        <input type="text" name="jenis_indikator" id="jenis_indikator"
               value="{{ old('jenis_indikator', $metadata?->jenis_indikator) }}"
               placeholder="mis. kepemilikan_ktp" required
               {{ $metadata ? 'readonly' : '' }}
               class="form-input font-mono @error('jenis_indikator') border-red-400 @enderror {{ $metadata ? 'bg-gray-50 text-gray-500' : '' }}">
        <p class="mt-1 text-xs text-gray-400">
            @if($metadata)
                Kode tidak dapat diubah karena dipakai sebagai kunci di seluruh modul.
            @else
                Huruf kecil, angka, dan garis bawah saja — dipakai sistem sebagai kunci internal.
            @endif
        </p>
        @error('jenis_indikator') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="nama" class="form-label">Nama Indikator <span class="text-red-500">*</span></label>
        <input type="text" name="nama" id="nama"
               value="{{ old('nama', $metadata?->nama) }}" required
               class="form-input @error('nama') border-red-400 @enderror">
        @error('nama') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label for="definisi" class="form-label">Definisi <span class="text-red-500">*</span></label>
        <textarea name="definisi" id="definisi" rows="3" required
                  class="form-input @error('definisi') border-red-400 @enderror">{{ old('definisi', $metadata?->definisi) }}</textarea>
        @error('definisi') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="modul" class="form-label">Modul <span class="text-red-500">*</span></label>
        <select name="modul" id="modul" required class="form-select @error('modul') border-red-400 @enderror">
            @foreach($modulList as $m)
                <option value="{{ $m }}" @selected(old('modul', $metadata?->modul) === $m)>{{ $m }}</option>
            @endforeach
        </select>
        @error('modul') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="satuan" class="form-label">Satuan <span class="text-red-500">*</span></label>
        <input type="text" name="satuan" id="satuan"
               value="{{ old('satuan', $metadata?->satuan ?? 'jiwa') }}" required
               class="form-input @error('satuan') border-red-400 @enderror">
        @error('satuan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="sumber" class="form-label">Sumber Data <span class="text-red-500">*</span></label>
        <input type="text" name="sumber" id="sumber"
               value="{{ old('sumber', $metadata?->sumber ?? 'Disdukcapil Kota Cimahi') }}" required
               class="form-input @error('sumber') border-red-400 @enderror">
        @error('sumber') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="periode_update" class="form-label">Periode Pembaruan <span class="text-red-500">*</span></label>
        <input type="text" name="periode_update" id="periode_update"
               value="{{ old('periode_update', $metadata?->periode_update ?? 'Per Semester') }}" required
               class="form-input @error('periode_update') border-red-400 @enderror">
        @error('periode_update') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

</div>
