<x-layouts.app title="Pratinjau Import" breadcrumb="Halaman Petugas / Import Data / Pratinjau">

    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-lg font-bold text-gray-900">{{ $import->nama_file }}</h1>
            <p class="text-sm text-gray-500">
                Profil <strong>{{ $import->nama_profil }}</strong> ·
                diunggah {{ $import->created_at?->translatedFormat('d M Y H:i') }}
            </p>
        </div>
        <x-konfirmasi
            :action="route('petugas.import.batal', $import)"
            judul="Batalkan import ini?"
            pesan="Berkas yang sudah diunggah akan dihapus dari server dan pratinjau ini tidak bisa dibuka lagi."
            tombol="Ya, Batalkan Import"
            pemicu="Batalkan Import"
            ikon="bi-x-lg"
            kelas="btn-secondary">
            <p class="text-xs text-gray-600">
                Tidak ada data yang berubah — pembatalan ini aman. Untuk melanjutkan nanti,
                berkas <strong>{{ $import->nama_file }}</strong> harus diunggah ulang.
            </p>
        </x-konfirmasi>
    </div>

    {{-- ── Galat: menutup jalan ke penyimpanan ── --}}
    @if($hasil->adaGalat())
        <div class="alert-error mb-5">
            <p class="font-semibold mb-2 flex items-center gap-2">
                <i class="bi bi-x-octagon-fill"></i>
                {{ count($hasil->galat) }} masalah struktur — import tidak bisa dilanjutkan
            </p>
            <ul class="list-disc list-inside space-y-1 text-xs">
                @foreach($hasil->galat as $g)
                    <li>{{ $g }}</li>
                @endforeach
            </ul>
            <p class="text-xs mt-3">
                Perbaiki pemetaan kolomnya lalu buka ulang halaman ini — berkasnya tidak perlu diunggah lagi.
            </p>
        </div>
    @endif

    {{-- ── Peringatan: boleh lanjut, tapi perlu dilihat ── --}}
    @if($hasil->peringatan)
        <div class="alert-warning mb-5">
            <p class="font-semibold mb-2 flex items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill"></i>
                {{ count($hasil->peringatan) }} hal yang perlu diperiksa
            </p>
            <ul class="list-disc list-inside space-y-1 text-xs">
                @foreach($hasil->peringatan as $p)
                    <li>{{ $p }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ── Info: hal yang sengaja dilewati, bukan masalah ── --}}
    @if($hasil->info)
        <div class="alert-info mb-5">
            <p class="font-semibold mb-2 flex items-center gap-2">
                <i class="bi bi-info-circle-fill"></i>
                {{ count($hasil->info) }} catatan — tidak perlu ditindaklanjuti
            </p>
            <ul class="list-disc list-inside space-y-1 text-xs">
                @foreach($hasil->info as $i)
                    <li>{{ $i }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3 items-start">
        <div class="lg:col-span-2 space-y-6">

            {{-- ── Ringkasan per sheet ── --}}
            <div class="card overflow-hidden">
                <div class="p-4 border-b border-gray-100">
                    <h2 class="text-sm font-bold text-gray-900">Ringkasan per Sheet</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="tw-table">
                        <thead>
                            <tr>
                                <th>Sheet</th>
                                <th class="text-right">Kelurahan</th>
                                <th class="text-right">Kolom</th>
                                <th class="text-right">Total Angka</th>
                                <th class="text-right">Baris Header</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($hasil->ringkasan as $sheet => $r)
                                <tr>
                                    <td class="font-medium text-gray-900 font-mono text-xs">{{ $sheet }}</td>
                                    <td class="text-right tabular-nums">
                                        <span @class([
                                            'font-semibold',
                                            'text-yellow-700' => $r['kelurahan_terbaca'] < 15,
                                        ])>{{ $r['kelurahan_terbaca'] }}</span>
                                        <span class="text-gray-400">/ 15</span>
                                    </td>
                                    <td class="text-right tabular-nums">{{ $r['jumlah_kolom'] }}</td>
                                    <td class="text-right tabular-nums">{{ number_format($r['total_angka'], 0, ',', '.') }}</td>
                                    <td class="text-right tabular-nums text-gray-400">{{ $r['baris_header'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-10 text-gray-400">
                                        <i class="bi bi-inbox text-2xl block mb-2"></i>
                                        Tidak ada sheet yang berhasil dibaca.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ── Sampel baris ── --}}
            @if($hasil->totalBaris() > 0)
                <div class="card overflow-hidden">
                    <div class="p-4 border-b border-gray-100 flex items-center justify-between">
                        <h2 class="text-sm font-bold text-gray-900">Contoh Baris yang Akan Disimpan</h2>
                        <span class="text-xs text-gray-400">
                            {{ count($hasil->sampel()) }} dari {{ number_format($hasil->totalBaris(), 0, ',', '.') }} baris
                        </span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="tw-table">
                            <thead>
                                <tr>
                                    <th>Sheet</th>
                                    <th>Kelurahan</th>
                                    <th>Indikator</th>
                                    <th>Label</th>
                                    <th class="text-right">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($hasil->sampel() as $b)
                                    <tr>
                                        <td class="font-mono text-xs text-gray-500">{{ $b['sheet'] }}</td>
                                        <td class="font-medium text-gray-900">{{ $b['nama_kelurahan'] }}</td>
                                        <td class="font-mono text-xs text-gray-500">{{ $b['jenis_indikator'] }}</td>
                                        <td>{{ $b['label'] }}</td>
                                        <td class="text-right tabular-nums">{{ number_format($b['jumlah'], 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- ── Data yang sudah ada ── --}}
            @if($hasil->adaDuplikat())
                <div class="card overflow-hidden">
                    <div class="p-4 border-b border-gray-100">
                        <h2 class="text-sm font-bold text-gray-900">
                            {{ number_format(count($hasil->duplikat), 0, ',', '.') }} kombinasi sudah punya data
                        </h2>
                        <p class="text-xs text-gray-500 mt-1">
                            Periode ini sudah pernah diisi. Tentukan penanganannya di panel sebelah kanan.
                        </p>
                    </div>
                    <div class="overflow-x-auto max-h-80">
                        <table class="tw-table">
                            <thead>
                                <tr>
                                    <th>Kelurahan</th>
                                    <th>Label</th>
                                    <th class="text-right">Nilai Lama</th>
                                    <th class="text-right">Nilai Baru</th>
                                    <th class="text-right">Selisih</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(array_slice($hasil->duplikat, 0, 50) as $d)
                                    @php $selisih = $d['jumlah_baru'] - $d['jumlah_lama']; @endphp
                                    <tr @class(['text-gray-400' => $selisih === 0])>
                                        <td class="font-medium text-gray-900">{{ $d['nama_kelurahan'] }}</td>
                                        <td>{{ $d['label'] }}</td>
                                        <td class="text-right tabular-nums">{{ number_format($d['jumlah_lama'], 0, ',', '.') }}</td>
                                        <td class="text-right tabular-nums">{{ number_format($d['jumlah_baru'], 0, ',', '.') }}</td>
                                        <td class="text-right tabular-nums">
                                            {{ $selisih === 0 ? '—' : ($selisih > 0 ? '+' : '').number_format($selisih, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if(count($hasil->duplikat) > 50)
                        <div class="p-3 text-xs text-gray-400 border-t border-gray-100">
                            …dan {{ number_format(count($hasil->duplikat) - 50, 0, ',', '.') }} kombinasi lain.
                        </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- ── Panel konfirmasi ── --}}
        <div class="space-y-4">
            <div class="card p-5">
                <h3 class="text-sm font-bold text-gray-900 mb-4">Konfirmasi Penyimpanan</h3>

                <dl class="space-y-2 text-xs mb-5 pb-5 border-b border-gray-100">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Baris siap disimpan</dt>
                        <dd class="font-semibold tabular-nums text-gray-900">
                            {{ number_format($hasil->totalBaris(), 0, ',', '.') }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Sheet terbaca</dt>
                        <dd class="font-semibold tabular-nums text-gray-900">{{ count($hasil->ringkasan) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Sudah ada datanya</dt>
                        <dd class="font-semibold tabular-nums text-gray-900">
                            {{ number_format(count($hasil->duplikat), 0, ',', '.') }}
                        </dd>
                    </div>
                </dl>

                {{-- x-data dipasang di pembungkus, bukan di dalam form, supaya modal
                     konfirmasi yang diteleport ke <body> tetap bisa membaca nilai
                     tahun/semester/mode yang sedang dipilih dan menampilkannya. --}}
                <div x-data="{
                        tahun: '{{ old('tahun', $hasil->tahun) }}',
                        semester: '{{ old('semester', $hasil->semester) }}',
                        dup: '{{ old('mode_duplikat', 'timpa') }}',
                     }">
                <form method="POST" action="{{ route('petugas.import.konfirmasi', $import) }}"
                      id="form-konfirmasi-import">
                    @csrf

                    <div class="space-y-4">
                        <div>
                            <label for="k_tahun" class="block text-xs font-semibold text-gray-700 mb-1">Tahun</label>
                            <input type="number" name="tahun" id="k_tahun" class="form-input text-sm"
                                   min="2000" max="2100" required x-model="tahun"
                                   value="{{ old('tahun', $hasil->tahun) }}">
                            @error('tahun') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="k_semester" class="block text-xs font-semibold text-gray-700 mb-1">Semester</label>
                            <select name="semester" id="k_semester" class="form-select text-sm" required x-model="semester">
                                <option value="1" @selected(old('semester', $hasil->semester) == 1)>Semester 1</option>
                                <option value="2" @selected(old('semester', $hasil->semester) == 2)>Semester 2</option>
                            </select>
                            @error('semester') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- Tiga keadaan, sengaja dibedakan: konsisten · dugaan suara
                             terbanyak · tidak terbaca sama sekali. --}}
                        @if($hasil->periodeTerdeteksiOtomatis && ! $hasil->periodeBentrok)
                            <p class="text-[11px] text-green-700 flex items-start gap-1">
                                <i class="bi bi-check-circle-fill mt-0.5"></i>
                                Terbaca otomatis dari berkas dan konsisten. Ubah bila keliru.
                            </p>
                        @elseif($hasil->periodeBentrok)
                            <p class="text-[11px] text-yellow-700 flex items-start gap-1">
                                <i class="bi bi-question-circle-fill mt-0.5"></i>
                                Judul di berkas tidak seragam. Isian di atas diambil dari periode yang
                                paling banyak disebut — periksa rincian suaranya di peringatan, lalu
                                ubah bila keliru.
                            </p>
                        @else
                            <p class="text-[11px] text-yellow-700 flex items-start gap-1">
                                <i class="bi bi-exclamation-triangle-fill mt-0.5"></i>
                                Tidak terbaca otomatis — isi sendiri dan pastikan periodenya benar.
                            </p>
                        @endif

                        <div @class(['pt-2', 'opacity-50' => ! $hasil->adaDuplikat()])>
                            <span class="block text-xs font-semibold text-gray-700 mb-2">
                                Bila data sudah ada
                            </span>
                            <label class="flex items-start gap-2 mb-2 cursor-pointer">
                                <input type="radio" name="mode_duplikat" value="timpa" class="mt-0.5" x-model="dup"
                                       @checked(old('mode_duplikat', 'timpa') === 'timpa')>
                                <span class="text-xs">
                                    <span class="font-semibold text-gray-800">Timpa</span>
                                    <span class="block text-gray-500">Nilai lama diganti nilai dari berkas, perubahannya dicatat di audit log.</span>
                                </span>
                            </label>
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input type="radio" name="mode_duplikat" value="lewati" class="mt-0.5" x-model="dup"
                                       @checked(old('mode_duplikat') === 'lewati')>
                                <span class="text-xs">
                                    <span class="font-semibold text-gray-800">Lewati</span>
                                    <span class="block text-gray-500">Nilai lama dipertahankan, hanya kombinasi baru yang ditambahkan.</span>
                                </span>
                            </label>
                            @error('mode_duplikat') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                </form>

                    {{-- Tombolnya di luar <form> tapi tetap mengirimnya lewat atribut form="",
                         karena modal ini diteleport ke <body> — tidak boleh ada form bersarang. --}}
                    <div class="mt-5">
                        <x-konfirmasi
                            form="form-konfirmasi-import"
                            varian="utama"
                            judul="Simpan data ini ke database?"
                            tombol="Ya, Simpan Sekarang"
                            pemicu="Simpan ke Database"
                            ikon="bi-check2-circle"
                            kelas="btn-primary w-full justify-center"
                            :nonaktif="$hasil->adaGalat() || $hasil->totalBaris() === 0">
                            <dl class="rounded-lg bg-gray-50 p-3 space-y-1 text-xs">
                                <div class="flex justify-between gap-4">
                                    <dt class="text-gray-500">Periode</dt>
                                    <dd class="font-semibold text-gray-900">
                                        Semester <span x-text="semester"></span> Tahun <span x-text="tahun"></span>
                                    </dd>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <dt class="text-gray-500">Baris disimpan</dt>
                                    <dd class="font-semibold tabular-nums text-gray-900">
                                        {{ number_format($hasil->totalBaris(), 0, ',', '.') }}
                                    </dd>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <dt class="text-gray-500">Sudah ada datanya</dt>
                                    <dd class="font-semibold tabular-nums text-gray-900">
                                        {{ number_format(count($hasil->duplikat), 0, ',', '.') }}
                                    </dd>
                                </div>
                            </dl>

                            @if($hasil->adaDuplikat())
                                <p class="mt-2 text-xs" x-show="dup === 'timpa'">
                                    <i class="bi bi-exclamation-triangle-fill text-amber-500 mr-1"></i>
                                    <strong>{{ number_format(count($hasil->duplikat), 0, ',', '.') }}</strong>
                                    nilai lama akan <strong>ditimpa</strong> dan tidak bisa dikembalikan lewat aplikasi
                                    — perubahannya hanya tercatat di audit log.
                                </p>
                                <p class="mt-2 text-xs text-gray-600" x-show="dup === 'lewati'" x-cloak>
                                    <i class="bi bi-info-circle-fill text-gray-400 mr-1"></i>
                                    Nilai lama dipertahankan; hanya kombinasi baru yang ditambahkan.
                                </p>
                            @endif

                            @if($hasil->peringatan)
                                <p class="mt-2 text-xs text-yellow-700">
                                    <i class="bi bi-exclamation-circle-fill mr-1"></i>
                                    Masih ada {{ count($hasil->peringatan) }} peringatan yang belum ditindaklanjuti.
                                </p>
                            @endif

                            @unless($hasil->periodeTerdeteksiOtomatis)
                                <p class="mt-2 text-xs text-yellow-700">
                                    <i class="bi bi-calendar-event mr-1"></i>
                                    Periode tidak terbaca otomatis dari berkas — periksa sekali lagi
                                    sebelum menyimpan. Periode yang salah akan menimpa semester lain.
                                </p>
                            @endunless
                        </x-konfirmasi>

                        @if($hasil->adaGalat())
                            <p class="text-[11px] text-red-600 mt-2 text-center">
                                Selesaikan masalah struktur di atas lebih dulu.
                            </p>
                        @elseif($hasil->totalBaris() === 0)
                            <p class="text-[11px] text-red-600 mt-2 text-center">
                                Tidak ada baris yang bisa disimpan.
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="alert-info text-xs">
                <i class="bi bi-shield-check mr-1"></i>
                Sampai tombol di atas ditekan, <strong>tidak ada satu angka pun</strong> yang berubah di
                database. Penyimpanan berjalan dalam satu transaksi — bila gagal di tengah, seluruhnya
                dibatalkan.
            </div>
        </div>
    </div>

</x-layouts.app>
