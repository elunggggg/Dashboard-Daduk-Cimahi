<x-layouts.app title="Kelola Metadata" breadcrumb="Halaman Petugas / Kelola Metadata">

    {{-- ── Toolbar ── --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h2 class="text-lg font-bold text-gray-900">Metadata Indikator</h2>
            <p class="text-sm text-gray-500">{{ $metadata->total() }} indikator terdaftar</p>
        </div>
        <a href="{{ route('petugas.metadata.create') }}" class="btn-primary">
            <i class="bi bi-plus-lg"></i> Tambah Metadata
        </a>
    </div>

    {{-- ── Filter ── --}}
    <form method="GET" action="{{ route('petugas.metadata.index') }}" class="card p-4 mb-5">
        <div class="grid gap-3 sm:grid-cols-[1fr_auto_auto] sm:items-end">
            <div>
                <label for="q" class="form-label">Cari</label>
                <input type="text" name="q" id="q" value="{{ $q }}"
                       placeholder="Nama atau kode indikator…"
                       class="form-input">
            </div>
            <div>
                <label for="modul" class="form-label">Modul</label>
                <select name="modul" id="modul" class="form-select">
                    <option value="">Semua</option>
                    @foreach($modulList as $m)
                        <option value="{{ $m }}" @selected($modul === $m)>{{ $m }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn-primary"><i class="bi bi-search"></i> Filter</button>
                @if($q || $modul)
                    <a href="{{ route('petugas.metadata.index') }}" class="btn-secondary">Reset</a>
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
                        <th>Kode</th>
                        <th>Nama Indikator</th>
                        <th>Modul</th>
                        <th>Satuan</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($metadata as $m)
                        <tr>
                            <td><code class="text-xs bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded font-mono">{{ $m->jenis_indikator }}</code></td>
                            <td class="font-medium text-gray-900">{{ $m->nama }}</td>
                            <td><span class="badge-gray">{{ $m->modul }}</span></td>
                            <td>{{ $m->satuan }}</td>
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('petugas.metadata.edit', $m) }}"
                                       class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-brand-700 transition-colors"
                                       title="Ubah">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <x-konfirmasi
                                        :action="route('petugas.metadata.destroy', $m)"
                                        method="DELETE"
                                        judul="Hapus metadata {{ $m->nama }}?"
                                        pesan="Penjelasan indikator ini akan hilang dari halaman publik Metadata. Tindakan ini tidak dapat dibatalkan."
                                        tombol="Ya, Hapus"
                                        ikon="bi-trash3"
                                        judul-pemicu="Hapus"
                                        kelas="p-2 rounded-lg text-gray-500 hover:bg-red-50 hover:text-red-600 transition-colors" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-10 text-gray-400">
                                <i class="bi bi-inbox text-2xl block mb-2"></i>
                                Tidak ada metadata yang cocok dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($metadata->hasPages())
        <div class="mt-4">{{ $metadata->links() }}</div>
    @endif

</x-layouts.app>
