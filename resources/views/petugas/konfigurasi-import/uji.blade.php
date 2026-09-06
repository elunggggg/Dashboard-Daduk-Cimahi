<x-layouts.app title="Uji Coba Pemetaan" breadcrumb="Halaman Petugas / Konfigurasi Import / Uji Coba">

    <div class="max-w-4xl space-y-5">
        <a href="{{ route('petugas.konfigurasi-import.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-900">
            <i class="bi bi-arrow-left"></i> Kembali ke daftar konfigurasi
        </a>

        <div class="card p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-1">Uji Coba Pemetaan</h2>
            <p class="text-sm text-gray-500 mb-5">
                Unggah berkas Excel (tanpa disimpan permanen) untuk melihat apakah konfigurasi sebuah sheet
                sudah mendarat di kolom yang benar, sebelum dipakai import sungguhan.
            </p>

            <form method="POST" action="{{ route('petugas.konfigurasi-import.uji-proses') }}" enctype="multipart/form-data" class="grid gap-4 sm:grid-cols-3 sm:items-end">
                @csrf
                <div class="sm:col-span-3">
                    <label for="file" class="form-label">Berkas Excel <span class="text-red-500">*</span></label>
                    <input type="file" name="file" id="file" accept=".xlsx,.xls" required
                           class="form-input @error('file') border-red-400 @enderror">
                    @error('file') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="nama_profil" class="form-label">Profil <span class="text-red-500">*</span></label>
                    <select name="nama_profil" id="nama_profil" required class="form-select @error('nama_profil') border-red-400 @enderror">
                        @foreach($profilList as $p)
                            <option value="{{ $p }}" @selected(($namaProfil ?? '') === $p)>{{ $p }}</option>
                        @endforeach
                    </select>
                    @error('nama_profil') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="nama_sheet" class="form-label">Nama Sheet</label>
                    <input type="text" name="nama_sheet" id="nama_sheet" list="sheet_datalist"
                           value="{{ old('nama_sheet', $sheetDiuji ?? '') }}"
                           placeholder="Kosongkan dulu untuk melihat daftar sheet di berkas"
                           class="form-input font-mono @error('nama_sheet') border-red-400 @enderror">
                    @if(isset($sheetTersedia))
                        <datalist id="sheet_datalist">
                            @foreach($sheetTersedia as $s)<option value="{{ $s }}">@endforeach
                        </datalist>
                    @endif
                    @error('nama_sheet') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-3">
                    <button type="submit" class="btn-primary"><i class="bi bi-play-fill"></i> Uji</button>
                </div>
            </form>
        </div>

        @if(isset($sheetTersedia))
            <div class="card p-6">
                <h3 class="text-sm font-bold text-gray-900 mb-1">Sheet di dalam "{{ $namaBerkas }}"</h3>
                <p class="text-xs text-gray-400 mb-3">
                    {{ count($sheetTersedia) }} sheet ditemukan. Isi salah satu nama persis di kolom "Nama Sheet" di atas lalu Uji lagi.
                </p>
                <div class="flex flex-wrap gap-1.5">
                    @foreach($sheetTersedia as $s)
                        <span class="badge-gray font-mono text-[11px]">{{ $s }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        @if($hasil !== null)
            <div class="card overflow-hidden">
                <div class="p-4 border-b border-gray-100">
                    <h3 class="text-sm font-bold text-gray-900">Hasil Pemetaan — {{ $sheetDiuji }}</h3>
                    <p class="text-xs text-gray-400">
                        Kolom wilayah: {{ $hasil['kolom_wilayah'] !== null ? \App\Services\Import\PemetaKolom::hurufKolom($hasil['kolom_wilayah']) : 'TIDAK KETEMU' }}
                        pada baris Excel {{ $hasil['baris_header'] !== null ? $hasil['baris_header'] + 1 : '-' }}
                    </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="tw-table">
                        <thead>
                            <tr>
                                <th>Label</th>
                                <th>Teks Header Dicari</th>
                                <th class="text-right">Offset</th>
                                <th>Kolom Ditemukan</th>
                                <th>Isi di Baris Header</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($hasil['kolom'] as $k)
                                <tr class="{{ $k['indeks'] === null ? 'bg-red-50/60' : '' }}">
                                    <td class="font-medium text-gray-900">{{ $k['label'] }}</td>
                                    <td class="text-xs text-gray-600">{{ $k['teks_header'] }}</td>
                                    <td class="text-right tabular-nums">{{ $k['offset_kolom'] }}</td>
                                    <td>
                                        @if($k['indeks'] !== null)
                                            <span class="badge-green font-mono">{{ $k['huruf'] }}</span>
                                        @else
                                            <span class="badge-red">tidak ketemu</span>
                                        @endif
                                    </td>
                                    <td class="text-xs text-gray-500">{{ $k['isi_header'] ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center py-6 text-gray-400">Tidak ada konfigurasi untuk sheet ini pada profil terpilih.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($hasil['cuplikan'] !== [])
                    <div class="p-4 border-t border-gray-100">
                        <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wide mb-2">Cuplikan Baris Data</h4>
                        <div class="overflow-x-auto">
                            <table class="tw-table text-xs">
                                <thead>
                                    <tr>
                                        <th>Baris Excel</th>
                                        <th>Wilayah</th>
                                        @foreach($hasil['kolom'] as $k)<th>{{ $k['label'] }}</th>@endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($hasil['cuplikan'] as $c)
                                        <tr>
                                            <td class="tabular-nums">{{ $c['baris_excel'] }}</td>
                                            <td class="font-medium">{{ $c['wilayah'] }}</td>
                                            @foreach($hasil['kolom'] as $k)
                                                <td class="tabular-nums">{{ $c['nilai'][$k['label']] ?? '—' }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>

</x-layouts.app>
