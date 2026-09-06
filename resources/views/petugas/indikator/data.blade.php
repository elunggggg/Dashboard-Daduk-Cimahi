<x-layouts.app title="Data {{ $indikator->label }}" breadcrumb="Halaman Petugas / Kelola Indikator / Data">

    <div class="max-w-5xl space-y-5">
        <a href="{{ route('petugas.indikator.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-900">
            <i class="bi bi-arrow-left"></i> Kembali ke daftar indikator
        </a>

        <div>
            <h2 class="text-lg font-bold text-gray-900">{{ $indikator->label }}</h2>
            <p class="text-sm text-gray-500">
                <code class="text-xs bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded font-mono">{{ $indikator->jenis_indikator }}</code>
                · {{ $data->count() }} baris data
            </p>
        </div>

        <div class="alert-info text-xs">
            <i class="bi bi-info-circle mr-1"></i>
            Mengisi kombinasi wilayah + periode yang <strong>sudah ada datanya</strong> (mis. hasil import Excel)
            akan MENGGANTI nilai lama — perubahan ini tercatat di Audit Log lengkap dengan nilai sebelum & sesudahnya.
        </div>

        {{-- ── Form tambah/timpa ── --}}
        <form method="POST" action="{{ route('petugas.indikator.data.store', $indikator) }}" class="card p-5">
            @csrf
            <h3 class="text-sm font-bold text-gray-900 mb-3">Tambah / Timpa Nilai</h3>
            <div class="grid gap-3 sm:grid-cols-4 sm:items-end">
                <div>
                    <label for="wilayah_id" class="form-label">Wilayah <span class="text-red-500">*</span></label>
                    <select name="wilayah_id" id="wilayah_id" required class="form-select @error('wilayah_id') border-red-400 @enderror">
                        @foreach($wilayahList as $w)
                            <option value="{{ $w->id }}" @selected(old('wilayah_id') == $w->id)>
                                {{ $w->is_kota || $w->is_kecamatan ? '— ' : '' }}{{ $w->nama_kelurahan }}
                            </option>
                        @endforeach
                    </select>
                    @error('wilayah_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="waktu_id" class="form-label">Periode <span class="text-red-500">*</span></label>
                    <select name="waktu_id" id="waktu_id" required class="form-select @error('waktu_id') border-red-400 @enderror">
                        @forelse($waktuList as $w)
                            <option value="{{ $w->id }}" @selected(old('waktu_id') == $w->id)>{{ $w->label }}</option>
                        @empty
                            <option value="" disabled>Belum ada periode — import data dulu</option>
                        @endforelse
                    </select>
                    @error('waktu_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="jumlah" class="form-label">Jumlah <span class="text-red-500">*</span></label>
                    <input type="number" name="jumlah" id="jumlah" min="0" step="1" value="{{ old('jumlah') }}" required
                           class="form-input @error('jumlah') border-red-400 @enderror">
                    @error('jumlah') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <button type="submit" class="btn-primary w-full justify-center"><i class="bi bi-check-lg"></i> Simpan</button>
                </div>
            </div>
        </form>

        {{-- ── Tabel data yang sudah ada ── --}}
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="tw-table">
                    <thead>
                        <tr>
                            <th>Wilayah</th>
                            <th>Periode</th>
                            <th class="text-right">Jumlah</th>
                            <th>Sumber</th>
                            <th>Terakhir Diubah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $d)
                            <tr>
                                <td class="font-medium text-gray-900">
                                    {{ ($d->wilayah?->is_kota || $d->wilayah?->is_kecamatan) ? '— ' : '' }}{{ $d->wilayah?->nama_kelurahan ?? '—' }}
                                </td>
                                <td>{{ $d->waktu?->label ?? '—' }}</td>
                                <td class="text-right tabular-nums font-semibold">{{ number_format($d->jumlah, 0, ',', '.') }}</td>
                                <td>
                                    @if($d->import_id)
                                        <span class="badge-blue">Import #{{ $d->import_id }}</span>
                                    @else
                                        <span class="badge-gray">Manual</span>
                                    @endif
                                </td>
                                <td class="text-xs text-gray-400">
                                    {{ $d->pengubah?->name ?? $d->pembuat?->name ?? '—' }}
                                    · {{ $d->updated_at?->translatedFormat('d M Y H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-10 text-gray-400">
                                    <i class="bi bi-inbox text-2xl block mb-2"></i>
                                    Belum ada data untuk indikator ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</x-layouts.app>
