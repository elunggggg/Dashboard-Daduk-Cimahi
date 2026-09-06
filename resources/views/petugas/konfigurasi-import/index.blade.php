<x-layouts.app title="Konfigurasi Import" breadcrumb="Halaman Petugas / Konfigurasi Import">

    {{-- ── Toolbar ── --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h2 class="text-lg font-bold text-gray-900">Pemetaan Sheet Excel</h2>
            <p class="text-sm text-gray-500">{{ $konfigurasi->total() }} baris konfigurasi</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('petugas.konfigurasi-import.uji') }}" class="btn-secondary">
                <i class="bi bi-clipboard-check"></i> Uji Coba Pemetaan
            </a>
            <a href="{{ route('petugas.konfigurasi-import.create') }}" class="btn-primary">
                <i class="bi bi-plus-lg"></i> Tambah Konfigurasi
            </a>
        </div>
    </div>

    <div class="alert-info text-xs mb-5">
        <i class="bi bi-info-circle mr-1"></i>
        Halaman ini mengatur BAGAIMANA importer membaca sheet Excel (nama kolom yang dicari, offset, orientasi).
        Untuk mengatur label/urutan/status aktif indikator yang SUDAH punya data, gunakan
        <a href="{{ route('petugas.indikator.index') }}" class="underline font-medium">Kelola Indikator</a>.
    </div>

    {{-- ── Filter ── --}}
    <form method="GET" action="{{ route('petugas.konfigurasi-import.index') }}" class="card p-4 mb-5">
        <div class="grid gap-3 sm:grid-cols-[1fr_auto_auto_auto] sm:items-end">
            <div>
                <label for="q" class="form-label">Cari</label>
                <input type="text" name="q" id="q" value="{{ $q }}"
                       placeholder="Label, jenis indikator, atau teks header…"
                       class="form-input">
            </div>
            <div>
                <label for="profil" class="form-label">Profil</label>
                <select name="profil" id="profil" class="form-select">
                    <option value="">Semua</option>
                    @foreach($profilList as $p)
                        <option value="{{ $p }}" @selected($namaProfil === $p)>{{ $p }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="sheet" class="form-label">Sheet</label>
                <select name="sheet" id="sheet" class="form-select">
                    <option value="">Semua</option>
                    @foreach($sheetList as $s)
                        <option value="{{ $s }}" @selected($namaSheet === $s)>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn-primary"><i class="bi bi-search"></i> Filter</button>
                @if($q || $namaProfil || $namaSheet)
                    <a href="{{ route('petugas.konfigurasi-import.index') }}" class="btn-secondary">Reset</a>
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
                        <th>Sheet</th>
                        <th>Jenis</th>
                        <th>Label</th>
                        <th>Teks Header</th>
                        <th class="text-right">Offset</th>
                        <th>Orientasi</th>
                        <th class="text-center">Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($konfigurasi as $k)
                        <tr class="{{ ! $k->aktif ? 'bg-gray-50/60' : '' }}">
                            <td>
                                <code class="text-xs bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded font-mono">{{ $k->nama_sheet }}</code>
                                @if($k->alias_sheet)
                                    <span class="block text-[10px] text-gray-400 mt-0.5">alias: {{ $k->alias_sheet }}</span>
                                @endif
                            </td>
                            <td class="text-xs text-gray-500 font-mono">{{ $k->jenis_indikator }}</td>
                            <td class="font-medium text-gray-900">{{ $k->label }}</td>
                            <td class="text-xs text-gray-600">{{ $k->teks_header }}</td>
                            <td class="text-right tabular-nums">{{ $k->offset_kolom }}</td>
                            <td class="text-xs">
                                <span class="badge-gray">{{ $k->orientasi }}</span>
                            </td>
                            <td class="text-center">
                                @if($k->aktif)
                                    <span class="badge-green">Aktif</span>
                                @else
                                    <span class="badge-gray">Nonaktif</span>
                                @endif
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('petugas.konfigurasi-import.edit', $k) }}"
                                       class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-brand-700 transition-colors"
                                       title="Ubah">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <x-konfirmasi
                                        :action="route('petugas.konfigurasi-import.destroy', $k)"
                                        method="DELETE"
                                        judul="Hapus konfigurasi {{ $k->label }}?"
                                        pesan="Sheet {{ $k->nama_sheet }} tidak akan lagi membaca kolom ini saat import berikutnya. Data yang SUDAH masuk ke database tidak ikut terhapus."
                                        tombol="Ya, Hapus"
                                        ikon="bi-trash3"
                                        judul-pemicu="Hapus"
                                        kelas="p-2 rounded-lg text-gray-500 hover:bg-red-50 hover:text-red-600 transition-colors" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-10 text-gray-400">
                                <i class="bi bi-inbox text-2xl block mb-2"></i>
                                Tidak ada konfigurasi yang cocok dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($konfigurasi->hasPages())
        <div class="mt-4">{{ $konfigurasi->links() }}</div>
    @endif

</x-layouts.app>
