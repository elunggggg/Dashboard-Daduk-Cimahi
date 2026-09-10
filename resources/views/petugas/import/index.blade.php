<x-layouts.app title="Unggah Data" breadcrumb="Halaman Petugas / Unggah Data">

    {{-- Daftar galat baris, ditaruh di atas agar langsung terlihat --}}
    @if (session('import_errors'))
        <div class="alert-error mb-5">
            <p class="font-semibold mb-2 flex items-center gap-2">
                <i class="bi bi-x-octagon-fill"></i>
                Baris bermasalah — tidak ada data yang tersimpan
            </p>
            <ul class="list-disc list-inside space-y-0.5 text-xs">
                @foreach (session('import_errors') as $baris)
                    <li>{{ $baris }}</li>
                @endforeach
            </ul>
            @if (session('import_errors_sisa') > 0)
                <p class="text-xs mt-2 italic">…dan {{ session('import_errors_sisa') }} kesalahan lain.</p>
            @endif
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3 items-start">

        <div class="lg:col-span-2 space-y-6">

            {{-- ── Pemilih mode ── --}}
            <div class="card overflow-hidden" x-data="{ mode: '{{ $errors->any() && old('nama_profil') ? 'dkb' : 'dkb' }}' }">

                <div class="flex border-b border-gray-100">
                    <button type="button" @click="mode = 'dkb'"
                        :class="mode === 'dkb' ? 'border-brand-600 text-brand-700 bg-brand-50/50' :
                            'border-transparent text-gray-500 hover:text-gray-700'"
                        class="flex-1 px-4 py-3 text-sm font-semibold border-b-2 transition-colors text-left">
                        <i class="bi bi-file-earmark-spreadsheet mr-1"></i> Berkas DKB Mentah
                        <span class="block text-[11px] font-normal text-gray-400 mt-0.5">Berkas asli dari
                            Disdukcapil</span>
                    </button>
                    <button type="button" @click="mode = 'template'"
                        :class="mode === 'template' ? 'border-brand-600 text-brand-700 bg-brand-50/50' :
                            'border-transparent text-gray-500 hover:text-gray-700'"
                        class="flex-1 px-4 py-3 text-sm font-semibold border-b-2 transition-colors text-left">
                        <i class="bi bi-table mr-1"></i> Template Sederhana
                        <span class="block text-[11px] font-normal text-gray-400 mt-0.5">Berkas flat yang sudah
                            dirapikan</span>
                    </button>
                </div>

                {{-- ── MODE A: berkas DKB mentah ── --}}
                <div x-show="mode === 'dkb'" x-cloak class="p-6">
                    <p class="text-sm text-gray-500 mb-5">
                        Unggah berkas <strong>apa adanya</strong> dari Disdukcapil. Sistem membaca kolomnya
                        berdasarkan teks header, lalu menampilkan pratinjau sebelum apa pun disimpan.
                        Format .xlsx atau .xls — maksimal 20 MB.
                    </p>

                    <form method="POST" action="{{ route('petugas.import.dkb') }}" enctype="multipart/form-data"
                        x-data="{ nama: '' }">
                        @csrf

                        <label for="file_dkb"
                            class="flex flex-col items-center justify-center gap-2 w-full rounded-xl border-2 border-dashed
                                      border-gray-300 hover:border-brand-500 hover:bg-brand-50/40 transition-colors
                                      px-4 py-10 cursor-pointer text-center">
                            <i class="bi bi-cloud-arrow-up text-3xl text-gray-400"></i>
                            <span class="text-sm font-medium text-gray-700">Klik untuk memilih berkas DKB</span>
                            <span class="text-xs text-gray-400" x-text="nama || 'Belum ada berkas dipilih'"></span>
                            <input type="file" name="file" id="file_dkb" class="hidden" required
                                accept=".xlsx,.xls" @change="nama = $event.target.files[0]?.name ?? ''">
                        </label>
                        @error('file')
                            <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                        @enderror

                        <div class="grid gap-4 sm:grid-cols-3 mt-5">
                            <div>
                                <label for="nama_profil" class="block text-xs font-semibold text-gray-700 mb-1">
                                    Profil Pemetaan
                                </label>
                                <select name="nama_profil" id="nama_profil" class="form-select text-sm" required>
                                    @foreach ($profil as $p)
                                        <option value="{{ $p }}" @selected(old('nama_profil') === $p)>
                                            {{ $p }}</option>
                                    @endforeach
                                </select>
                                @error('nama_profil')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="tahun" class="block text-xs font-semibold text-gray-700 mb-1">
                                    Tahun <span class="text-gray-400 font-normal">(opsional)</span>
                                </label>
                                <input type="number" name="tahun" id="tahun" class="form-input text-sm"
                                    min="2000" max="2100" placeholder="Deteksi otomatis"
                                    value="{{ old('tahun') }}">
                                @error('tahun')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="semester" class="block text-xs font-semibold text-gray-700 mb-1">
                                    Semester <span class="text-gray-400 font-normal">(opsional)</span>
                                </label>
                                <select name="semester" id="semester" class="form-select text-sm">
                                    <option value="">Deteksi otomatis</option>
                                    <option value="1" @selected(old('semester') == 1)>Semester 1</option>
                                    <option value="2" @selected(old('semester') == 2)>Semester 2</option>
                                </select>
                                @error('semester')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 mt-5">
                            <button type="submit" class="btn-primary">
                                <i class="bi bi-eye"></i> Baca &amp; Pratinjau
                            </button>
                        </div>
                    </form>

                    <div class="alert-info text-xs mt-5">
                        <i class="bi bi-info-circle-fill mr-1"></i>
                        Tahun dan semester dibaca otomatis dari judul sheet. Isi kolomnya hanya bila
                        judul di berkas tidak memuat periode atau tertulis keliru.
                    </div>
                </div>

                {{-- ── MODE B: template flat ── --}}
                <div x-show="mode === 'template'" x-cloak class="p-6">
                    <p class="text-sm text-gray-500 mb-5">
                        Jalur cadangan untuk berkas yang formatnya terlalu tidak beraturan untuk diparse
                        otomatis — rapikan dulu di Excel mengikuti template. Format .xlsx, .xls, atau .csv —
                        maksimal 10 MB.
                    </p>

                    <form method="POST" action="{{ route('petugas.import.store') }}" enctype="multipart/form-data"
                        id="form-import-template" x-data="{ nama: '' }">
                        @csrf

                        <label for="file"
                            class="flex flex-col items-center justify-center gap-2 w-full rounded-xl border-2 border-dashed
                                      border-gray-300 hover:border-brand-500 hover:bg-brand-50/40 transition-colors
                                      px-4 py-10 cursor-pointer text-center">
                            <i class="bi bi-cloud-arrow-up text-3xl text-gray-400"></i>
                            <span class="text-sm font-medium text-gray-700">Klik untuk memilih berkas</span>
                            <span class="text-xs text-gray-400" x-text="nama || 'Belum ada berkas dipilih'"></span>
                            <input type="file" name="file" id="file" class="hidden"
                                accept=".xlsx,.xls,.csv" @change="nama = $event.target.files[0]?.name ?? ''">
                        </label>

                        <div class="flex flex-wrap items-center gap-2 mt-5">
                            {{-- Mode B tidak punya pratinjau, jadi konfirmasinya di sinilah
                                 satu-satunya kesempatan Petugas membatalkan. --}}
                            <x-konfirmasi form="form-import-template" varian="peringatan"
                                judul="Proses unggah sekarang?"
                                pesan="Mode template langsung menyimpan ke database tanpa pratinjau."
                                tombol="Ya, Proses Sekarang" pemicu="Proses Unggah" ikon="bi-upload"
                                kelas="btn-primary">
                                <ul class="list-disc list-inside space-y-1 text-xs text-gray-600">
                                    <li>Bersifat <strong>semua-atau-tidak sama sekali</strong> — satu baris tidak valid
                                        membuat seluruh berkas ditolak.</li>
                                    <li>Baris yang cocok wilayah + periode + kategori akan <strong>menimpa</strong>
                                        nilai lama.</li>
                                    <li>Butuh melihat dulu apa yang akan berubah? Pakai <strong>File DKB Mentah</strong>
                                        yang punya pratinjau.</li>
                                </ul>
                                <p class="mt-2 text-xs text-gray-500"
                                    x-text="nama ? 'Berkas: ' + nama : 'Belum ada berkas dipilih.'"></p>
                            </x-konfirmasi>
                            <a href="{{ route('petugas.import.template') }}" class="btn-secondary">
                                <i class="bi bi-download"></i> Template Kosong
                            </a>
                            <a href="{{ route('petugas.import.template-contoh') }}" class="btn-secondary">
                                <i class="bi bi-download"></i> Contoh Terisi
                            </a>
                        </div>
                    </form>

                    <div class="alert-warning text-xs mt-5">
                        <i class="bi bi-exclamation-triangle-fill mr-1"></i>
                        Mode ini bersifat <strong>semua-atau-tidak sama sekali</strong> dan langsung disimpan
                        tanpa pratinjau. Bila ada satu baris tidak valid, seluruh berkas ditolak dan data lama
                        tidak berubah. Baris yang cocok dengan kombinasi wilayah + periode + kategori yang sudah
                        ada akan <strong>menimpa</strong> nilainya.
                    </div>
                </div>
            </div>

            {{-- ── Riwayat ── --}}
            <div class="card overflow-hidden">
                <div class="p-4 border-b border-gray-100">
                    <h2 class="text-sm font-bold text-gray-900">Riwayat Unggah</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="tw-table">
                        <thead>
                            <tr>
                                <th>Berkas</th>
                                <th>Mode</th>
                                <th>Oleh</th>
                                <th class="text-right">Baris</th>
                                <th>Status</th>
                                <th>Waktu</th>
                                <th class="text-center"><i class="bi bi-gear"></i></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($riwayat as $r)
                                {{-- Baris yang datanya sudah dibuang diredupkan supaya mata
                                     langsung tahu mana import yang masih berlaku. --}}
                                <tr @class(['bg-gray-50/60' => $r->data_dihapus_pada])>
                                    <td class="font-medium text-gray-900">
                                        {{ $r->nama_file }}
                                        @if ($r->tahun && $r->semester)
                                            <span class="block text-[11px] text-gray-400">
                                                Semester {{ $r->semester }} Tahun {{ $r->tahun }}
                                            </span>
                                        @endif
                                        @if ($r->pesan_error)
                                            <span class="block text-[11px] text-red-500 truncate max-w-xs"
                                                title="{{ $r->pesan_error }}">
                                                {{ str($r->pesan_error)->limit(80) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-gray-500 text-xs">
                                        {{ $r->mode === \App\Models\ImportExcel::MODE_DKB ? 'DKB Mentah' : 'Template' }}
                                    </td>
                                    <td class="text-gray-500">{{ $r->user->name ?? '—' }}</td>
                                    <td class="text-right tabular-nums">
                                        @if ($r->data_dihapus_pada)
                                            {{-- Angkanya dicoret, bukan dihapus: jumlah itu tetap
                                                 fakta sejarah, hanya saja tidak berlaku lagi. --}}
                                            <span class="line-through text-gray-400"
                                                  title="Jumlah baris saat unggahan ini berhasil">
                                                {{ number_format($r->jumlah_baris, 0, ',', '.') }}
                                            </span>
                                            <span class="block text-[11px] font-semibold text-gray-400">
                                                0 tersisa
                                            </span>
                                        @else
                                            {{ number_format($r->jumlah_baris, 0, ',', '.') }}
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $gaya = [
                                                \App\Models\ImportExcel::STATUS_BERHASIL => [
                                                    'badge-green',
                                                    'bi-check-circle',
                                                ],
                                                \App\Models\ImportExcel::STATUS_GAGAL => ['badge-red', 'bi-x-circle'],
                                                \App\Models\ImportExcel::STATUS_PRATINJAU => [
                                                    'badge-blue',
                                                    'bi-hourglass-split',
                                                ],
                                                \App\Models\ImportExcel::STATUS_MENUNGGU => ['badge-gray', 'bi-clock'],
                                                \App\Models\ImportExcel::STATUS_DIBATALKAN => [
                                                    'badge-gray',
                                                    'bi-slash-circle',
                                                ],
                                            ][$r->status] ?? ['badge-gray', 'bi-dot'];
                                        @endphp
                                        <span class="{{ $gaya[0] }}"><i
                                                class="bi {{ $gaya[1] }} mr-1"></i>{{ $r->labelStatus() }}</span>
                                        @if ($r->data_dihapus_pada)
                                            <span class="block mt-1.5">
                                                <span class="badge-red">
                                                    <i class="bi bi-trash3 mr-1"></i>Data dihapus
                                                </span>
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-gray-500 whitespace-nowrap">
                                        <span class="block">{{ $r->created_at?->translatedFormat('d M Y H:i') }}</span>
                                        @if ($r->data_dihapus_pada)
                                            {{-- Waktu penghapusan ditampilkan, bukan disembunyikan di
                                                 tooltip: tooltip tidak muncul di layar sentuh, dan ini
                                                 justru keterangan yang dicari saat menelusuri kesalahan. --}}
                                            <span class="block mt-1 text-[11px] text-red-600">
                                                <i class="bi bi-trash3 mr-0.5"></i>
                                                dihapus {{ $r->data_dihapus_pada->translatedFormat('d M Y H:i') }}
                                            </span>
                                            <span class="block text-[11px] text-gray-400">
                                                oleh {{ $r->penghapus->name ?? '—' }}
                                                @if ($r->jumlah_baris_dihapus)
                                                    · {{ number_format($r->jumlah_baris_dihapus, 0, ',', '.') }} baris
                                                @endif
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-right whitespace-nowrap">
                                        @if ($r->bisaDikonfirmasi() && $r->mode === \App\Models\ImportExcel::MODE_DKB)
                                            <a href="{{ route('petugas.import.pratinjau', $r) }}"
                                                class="text-xs font-semibold text-brand-700 hover:underline">
                                                Lanjutkan
                                            </a>
                                        @elseif($r->bisaHapusData())
                                            @php
                                                $barisAktif = $r->data_agregat_count ?? $r->dataAgregat()->count();
                                                $ditimpa = (int) ($r->catatan_hasil['diperbarui'] ?? 0);
                                            @endphp
                                            <x-konfirmasi :action="route('petugas.import.hapus-data', $r)" method="DELETE"
                                                judul="Hapus data hasil unggah ini?"
                                                pesan="Dipakai bila berkas atau periodenya ternyata salah. Setelah dihapus, unggah berkas yang benar."
                                                tombol="Ya, Hapus Datanya" pemicu="Hapus Data"
                                                kelas="text-xs font-semibold text-red-600 hover:underline">
                                                <dl class="rounded-lg bg-gray-50 p-3 space-y-1 text-xs">
                                                    <div class="flex justify-between gap-4">
                                                        <dt class="text-gray-500">Berkas</dt>
                                                        <dd class="text-gray-900 truncate">{{ $r->nama_file }}</dd>
                                                    </div>
                                                    <div class="flex justify-between gap-4">
                                                        <dt class="text-gray-500">Periode</dt>
                                                        <dd class="text-gray-900">Semester {{ $r->semester }} Tahun
                                                            {{ $r->tahun }}</dd>
                                                    </div>
                                                    <div class="flex justify-between gap-4">
                                                        <dt class="text-gray-500">Baris yang akan dihapus</dt>
                                                        <dd class="font-semibold tabular-nums text-gray-900">
                                                            {{ number_format($barisAktif, 0, ',', '.') }}
                                                        </dd>
                                                    </div>
                                                </dl>

                                                @if ($barisAktif < $r->jumlah_baris)
                                                    <p class="mt-2 text-xs text-gray-500">
                                                        Unggahan ini semula menulis
                                                        {{ number_format($r->jumlah_baris, 0, ',', '.') }} baris;
                                                        sisanya sudah ditimpa unggahan yang lebih baru sehingga tidak ikut
                                                        terhapus.
                                                    </p>
                                                @endif

                                                @if ($ditimpa > 0)
                                                    <p class="mt-2 text-xs text-red-600">
                                                        <i class="bi bi-exclamation-triangle-fill mr-1"></i>
                                                        {{ number_format($ditimpa, 0, ',', '.') }} baris di antaranya
                                                        dulu
                                                        <strong>menimpa</strong> nilai yang sudah ada. Menghapus tidak
                                                        mengembalikan
                                                        nilai lama itu — nilainya hanya tercatat di audit log.
                                                    </p>
                                                @endif

                                                <p class="mt-2 text-xs text-gray-500">
                                                    Periode yang tidak punya data lagi setelah ini akan ikut dibuang
                                                    dari
                                                    dropdown filter. Catatan riwayat unggah ini tetap disimpan.
                                                </p>
                                            </x-konfirmasi>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-10 text-gray-400">
                                        <i class="bi bi-inbox text-2xl block mb-2"></i>
                                        Belum ada riwayat unggah.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($riwayat->hasPages())
                <div>{{ $riwayat->links() }}</div>
            @endif
        </div>

        {{-- ── Panel samping ── --}}
        <div class="space-y-4">
            <div class="card p-4">
                <h3 class="text-sm font-bold text-gray-900 mb-2">Cara Kerja Mode DKB</h3>
                <ol class="text-xs text-gray-600 space-y-1.5 list-decimal list-inside">
                    <li>Kolom dicari lewat <strong>teks header</strong>, bukan posisi — kolom boleh bergeser.</li>
                    <li>Baris dicocokkan lewat <strong>nama kelurahan</strong> beserta aliasnya.</li>
                    <li>Baris subtotal, total kota, dan persentase otomatis terlewati.</li>
                    <li>Pembacaan berhenti setelah 15 kelurahan, agar tabel pivot kedua tidak ikut terbaca.</li>
                    <li>Semua disimpan dalam satu transaksi — gagal di tengah berarti dibatalkan penuh.</li>
                </ol>
            </div>

            <div class="card p-4">
                <h3 class="text-sm font-bold text-gray-900 mb-2">Format Kolom Template</h3>
                <ol class="text-xs text-gray-600 space-y-1 list-decimal list-inside">
                    <li><code class="font-mono bg-gray-100 px-1 rounded">kode_kemendagri</code> — mis. 32.77.01.1001
                    </li>
                    <li><code class="font-mono bg-gray-100 px-1 rounded">tahun</code> — mis. 2025</li>
                    <li><code class="font-mono bg-gray-100 px-1 rounded">semester</code> — 1 atau 2</li>
                    <li><code class="font-mono bg-gray-100 px-1 rounded">jenis_indikator</code></li>
                    <li><code class="font-mono bg-gray-100 px-1 rounded">label</code></li>
                    <li><code class="font-mono bg-gray-100 px-1 rounded">jumlah</code> — bilangan bulat ≥ 0</li>
                </ol>
            </div>

            <div class="card p-4" x-data="{ buka: null }">
                <h3 class="text-sm font-bold text-gray-900 mb-2">Nilai yang Dikenali</h3>
                <p class="text-xs text-gray-400 mb-3">Klik indikator untuk melihat label yang sah.</p>
                <div class="space-y-1 max-h-96 overflow-y-auto">
                    @foreach ($referensi as $jenis => $items)
                        <div>
                            <button type="button"
                                @click="buka = (buka === '{{ $jenis }}' ? null : '{{ $jenis }}')"
                                class="w-full flex items-center justify-between text-left text-xs font-mono
                                           px-2 py-1.5 rounded hover:bg-gray-50">
                                <span class="text-gray-700">{{ $jenis }}</span>
                                <span class="text-gray-400">{{ $items->count() }}</span>
                            </button>
                            <ul x-show="buka === '{{ $jenis }}'" x-cloak
                                class="pl-3 py-1 space-y-0.5 border-l-2 border-gray-100 ml-2">
                                @foreach ($items as $it)
                                    <li class="text-[11px] text-gray-500">{{ $it->label }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

</x-layouts.app>
