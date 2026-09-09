<x-layouts.app title="Kelola Indikator" breadcrumb="Halaman Petugas / Kelola Indikator">

    {{-- ── Toolbar ── --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
        <div>
            <h2 class="text-lg font-bold text-gray-900">Daftar Indikator</h2>
            <p class="text-sm text-gray-500">{{ $indikator->total() }} indikator terdaftar</p>
        </div>
        <a href="{{ route('petugas.indikator.create') }}" class="btn-primary">
            <i class="bi bi-plus-lg"></i> Tambah Indikator
        </a>
    </div>

    {{-- ── Penjelasan singkat istilah ── --}}
    <div class="card p-4 mb-5 bg-brand-50/40 border-brand-100 text-sm text-gray-600">
        <p class="flex items-start gap-2">
            <i class="bi bi-info-circle-fill text-brand-600 mt-0.5"></i>
            <span>
                <strong class="text-gray-800">Kelompok Indikator</strong> menyatukan beberapa kategori sejenis ke dalam
                satu grafik — misalnya kelompok <em>Agama</em> berisi kategori Islam, Kristen, Katolik, dan seterusnya.
                <strong class="text-gray-800">Nama Kategori</strong> adalah teks yang dibaca pengunjung di legenda grafik
                dan tabel dashboard.
            </span>
        </p>
    </div>

    {{-- ── Filter ── --}}
    <form method="GET" action="{{ route('petugas.indikator.index') }}" class="card p-4 mb-5">
        <div class="grid gap-3 sm:grid-cols-[1fr_auto_auto] sm:items-end">
            <div>
                <label for="q" class="form-label">Cari</label>
                <input type="text" name="q" id="q" value="{{ $q }}"
                       placeholder="Ketik nama kategori atau nama kelompok…"
                       class="form-input">
            </div>
            <div>
                <label for="jenis" class="form-label">Kelompok Indikator</label>
                <select name="jenis" id="jenis" class="form-select">
                    <option value="">Semua kelompok</option>
                    @foreach($jenisList as $j)
                        <option value="{{ $j }}" @selected($jenis === $j)>{{ \Illuminate\Support\Str::headline($j) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn-primary"><i class="bi bi-search"></i> Filter</button>
                @if($q || $jenis)
                    <a href="{{ route('petugas.indikator.index') }}" class="btn-secondary">Reset</a>
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
                        <th>Kelompok Indikator</th>
                        <th>Nama Kategori</th>
                        <th class="text-right">Urutan Tampil</th>
                        <th class="text-center">Status</th>
                        <th class="text-right">Baris Data</th>
                        <th>Metadata</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($indikator as $k)
                        <tr class="{{ ! $k->aktif ? 'bg-gray-50/60' : '' }}">
                            <td>
                                <span class="font-medium text-gray-900">{{ $k->jenis_terbaca }}</span>
                                <code class="block mt-0.5 text-[11px] text-gray-400 font-mono">{{ $k->jenis_indikator }}</code>
                            </td>
                            <td class="font-medium text-gray-900">{{ $k->label }}</td>
                            <td class="text-right tabular-nums">{{ $k->urutan }}</td>
                            <td class="text-center">
                                @if($k->aktif)
                                    <span class="badge-green">Aktif</span>
                                @else
                                    <span class="badge-gray">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <span class="{{ $k->data_agregat_count > 0 ? 'badge-blue' : 'badge-gray' }}">
                                    {{ number_format($k->data_agregat_count, 0, ',', '.') }}
                                </span>
                            </td>
                            <td>
                                @if($idMeta = $metadataAda[$k->jenis_indikator] ?? null)
                                    <a href="{{ route('petugas.metadata.edit', $idMeta) }}" class="text-xs text-brand-700 hover:underline">
                                        <i class="bi bi-book"></i> Lihat
                                    </a>
                                @else
                                    <a href="{{ route('petugas.metadata.create') }}" class="text-xs text-gray-400 hover:text-brand-700 hover:underline">
                                        <i class="bi bi-book"></i> Belum ada
                                    </a>
                                @endif
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('petugas.indikator.data', $k) }}"
                                       class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-brand-700 transition-colors"
                                       title="Kelola Data">
                                        <i class="bi bi-table"></i>
                                    </a>
                                    <a href="{{ route('petugas.indikator.edit', $k) }}"
                                       class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-brand-700 transition-colors"
                                       title="Ubah">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <x-konfirmasi
                                        :action="route('petugas.indikator.destroy', $k)"
                                        method="DELETE"
                                        judul="Hapus indikator {{ $k->label }}?"
                                        pesan="Baris indikator ini hilang dari daftar. Bila hanya ingin menyembunyikannya dari dashboard publik, gunakan Ubah lalu matikan status Aktif — jangan hapus."
                                        tombol="Ya, Hapus"
                                        ikon="bi-trash3"
                                        judul-pemicu="Hapus"
                                        kelas="p-2 rounded-lg text-gray-500 hover:bg-red-50 hover:text-red-600 transition-colors">
                                        <dl class="rounded-lg bg-gray-50 p-3 space-y-1 text-xs">
                                            <div class="flex justify-between gap-4">
                                                <dt class="text-gray-500">Kelompok Indikator</dt>
                                                <dd class="text-gray-900">{{ $k->jenis_terbaca }}
                                                    <code class="text-[11px] text-gray-400 font-mono">({{ $k->jenis_indikator }})</code>
                                                </dd>
                                            </div>
                                            <div class="flex justify-between gap-4">
                                                <dt class="text-gray-500">Baris data agregat</dt>
                                                <dd class="tabular-nums font-semibold text-gray-900">
                                                    {{ number_format($k->data_agregat_count, 0, ',', '.') }}
                                                </dd>
                                            </div>
                                        </dl>
                                        @if($k->data_agregat_count > 0)
                                            <p class="mt-2 text-xs text-red-600">
                                                <i class="bi bi-shield-exclamation mr-1"></i>
                                                Indikator ini masih dipakai data agregat, jadi penghapusannya akan
                                                <strong>ditolak</strong> oleh sistem. Nonaktifkan saja lewat Ubah.
                                            </p>
                                        @endif
                                    </x-konfirmasi>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-10 text-gray-400">
                                <i class="bi bi-inbox text-2xl block mb-2"></i>
                                Tidak ada indikator yang cocok dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($indikator->hasPages())
        <div class="mt-4">{{ $indikator->links() }}</div>
    @endif

</x-layouts.app>
