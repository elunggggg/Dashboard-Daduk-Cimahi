<x-layouts.app title="Kelola Wilayah" breadcrumb="Halaman Petugas / Kelola Wilayah">

    {{-- ── Toolbar ── --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h2 class="text-lg font-bold text-gray-900">Data Wilayah</h2>
            <p class="text-sm text-gray-500">{{ $wilayah->total() }} kelurahan terdaftar</p>
        </div>
        <a href="{{ route('petugas.wilayah.create') }}" class="btn-primary">
            <i class="bi bi-plus-lg"></i> Tambah Kelurahan
        </a>
    </div>

    {{-- ── Filter ── --}}
    <form method="GET" action="{{ route('petugas.wilayah.index') }}" class="card p-4 mb-5">
        <div class="grid gap-3 sm:grid-cols-[1fr_auto_auto] sm:items-end">
            <div>
                <label for="q" class="form-label">Cari</label>
                <input type="text" name="q" id="q" value="{{ $q }}"
                       placeholder="Nama kelurahan, kecamatan, atau kode…"
                       class="form-input">
            </div>
            <div>
                <label for="kecamatan" class="form-label">Kecamatan</label>
                <select name="kecamatan" id="kecamatan" class="form-select">
                    <option value="">Semua</option>
                    @foreach($kecamatanList as $kec)
                        <option value="{{ $kec }}" @selected($kecamatan === $kec)>{{ $kec }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn-primary"><i class="bi bi-search"></i> Filter</button>
                @if($q || $kecamatan)
                    <a href="{{ route('petugas.wilayah.index') }}" class="btn-secondary">Reset</a>
                @endif
            </div>
        </div>
    </form>

    {{-- ── Tabel ── --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="tw-table">
                <thead>
                    <tr>
                        <th>Kode Kemendagri</th>
                        <th>Kelurahan</th>
                        <th>Kecamatan</th>
                        <th class="text-right">Luas (km²)</th>
                        <th class="text-right">Baris Data</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($wilayah as $w)
                        <tr>
                            <td><code class="text-xs bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded font-mono">{{ $w->kode_kemendagri }}</code></td>
                            <td class="font-medium text-gray-900">{{ $w->nama_kelurahan }}</td>
                            <td>{{ $w->nama_kecamatan }}</td>
                            <td class="text-right">{{ $w->luas_km2 !== null ? number_format($w->luas_km2, 2, ',', '.') : '—' }}</td>
                            <td class="text-right">
                                <span class="{{ $w->data_agregat_count > 0 ? 'badge-blue' : 'badge-gray' }}">
                                    {{ number_format($w->data_agregat_count, 0, ',', '.') }}
                                </span>
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('petugas.wilayah.edit', $w) }}"
                                       class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-brand-700 transition-colors"
                                       title="Ubah">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <x-konfirmasi
                                        :action="route('petugas.wilayah.destroy', $w)"
                                        method="DELETE"
                                        judul="Hapus kelurahan {{ $w->nama_kelurahan }}?"
                                        pesan="Baris wilayah ini hilang dari semua filter, peta, dan laporan. Tindakan ini tidak dapat dibatalkan."
                                        tombol="Ya, Hapus"
                                        ikon="bi-trash3"
                                        judul-pemicu="Hapus"
                                        kelas="p-2 rounded-lg text-gray-500 hover:bg-red-50 hover:text-red-600 transition-colors">
                                        <dl class="rounded-lg bg-gray-50 p-3 space-y-1 text-xs">
                                            <div class="flex justify-between gap-4">
                                                <dt class="text-gray-500">Kode Kemendagri</dt>
                                                <dd class="font-mono text-gray-900">{{ $w->kode_kemendagri }}</dd>
                                            </div>
                                            <div class="flex justify-between gap-4">
                                                <dt class="text-gray-500">Kecamatan</dt>
                                                <dd class="text-gray-900">{{ $w->nama_kecamatan }}</dd>
                                            </div>
                                            <div class="flex justify-between gap-4">
                                                <dt class="text-gray-500">Baris data agregat</dt>
                                                <dd class="tabular-nums font-semibold text-gray-900">
                                                    {{ number_format($w->data_agregat_count, 0, ',', '.') }}
                                                </dd>
                                            </div>
                                        </dl>
                                        @if($w->data_agregat_count > 0)
                                            <p class="mt-2 text-xs text-red-600">
                                                <i class="bi bi-shield-exclamation mr-1"></i>
                                                Wilayah ini masih punya data agregat, jadi penghapusannya akan
                                                <strong>ditolak</strong> oleh sistem. Hapus atau pindahkan datanya lebih dulu.
                                            </p>
                                        @endif
                                    </x-konfirmasi>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-10 text-gray-400">
                                <i class="bi bi-inbox text-2xl block mb-2"></i>
                                Tidak ada wilayah yang cocok dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($wilayah->hasPages())
        <div class="mt-4">{{ $wilayah->links() }}</div>
    @endif

</x-layouts.app>
