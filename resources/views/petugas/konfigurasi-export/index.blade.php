<x-layouts.app title="Konfigurasi Unduh" breadcrumb="Halaman Petugas / Konfigurasi Unduh">

    <div class="max-w-4xl space-y-5">

        <div class="card p-4 bg-brand-50/40 border-brand-100 text-sm text-gray-600">
            <p class="flex items-start gap-2 mb-2">
                <i class="bi bi-info-circle-fill text-brand-600 mt-0.5"></i>
                <span>Mengatur <strong>bentuk berkas unduhan</strong> data agregat. Setiap perubahan langsung tersimpan &mdash; tidak ada tombol "Simpan" untuk daftar kolom.</span>
            </p>
            <ul class="list-disc list-inside space-y-1 pl-1">
                <li><strong>Unduh PDF</strong> &mdash; memakai daftar kolom di bawah (satu tabel rata): <i class="bi bi-eye"></i>/<i class="bi bi-eye-slash"></i> ikutkan/hilangkan kolom, <i class="bi bi-arrow-up"></i><i class="bi bi-arrow-down"></i> geser urutan, <strong>Label</strong> ganti teks header.</li>
                <li><strong>Unduh Excel</strong> &mdash; selalu bergaya berkas DKB: <em>satu sheet per indikator</em>, baris = kelurahan (+ subtotal kecamatan &amp; total Kota), kolom = kategori. Daftar kolom di bawah tidak berlaku untuk Excel.</li>
            </ul>
            <p class="mt-2 text-xs text-gray-500">
                Kolom <strong>Laki-laki</strong> &amp; <strong>Perempuan</strong> hanya muncul saat mengunduh indikator yang punya rincian jenis kelamin
                (mis. Umur Tunggal, Status Kawin, Agama). Untuk indikator lain kolom itu otomatis dilewati.
            </p>
        </div>

        {{-- ── Daftar kolom ── --}}
        <div class="card overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-sm font-bold text-gray-900">Kolom Unduh PDF</h2>
                <span class="text-xs text-gray-400">{{ $kolom->where('aktif', true)->count() }} / {{ $kolom->count() }} aktif</span>
            </div>
            <div class="divide-y divide-gray-50">
                @foreach ($kolom as $i => $k)
                    <div class="flex items-center gap-3 px-4 py-2.5 {{ $k->aktif ? '' : 'bg-gray-50/60' }}">

                        {{-- Aktif / nonaktif --}}
                        <form method="POST" action="{{ route('petugas.konfigurasi-export.atur', $k) }}">
                            @csrf @method('PATCH')
                            <button type="submit" name="aktif" value="{{ $k->aktif ? 0 : 1 }}"
                                    class="p-1.5 rounded-lg {{ $k->aktif ? 'text-green-600 hover:bg-green-50' : 'text-gray-400 hover:bg-gray-100' }}"
                                    title="{{ $k->aktif ? 'Hilangkan dari unduhan' : 'Ikutkan ke unduhan' }}">
                                <i class="bi {{ $k->aktif ? 'bi-eye-fill' : 'bi-eye-slash' }}"></i>
                            </button>
                        </form>

                        <div class="w-32 flex-shrink-0">
                            <code class="text-[11px] text-gray-500 font-mono">{{ $k->kunci }}</code>
                            <span class="block text-[10px] text-gray-300">{{ $k->format === \App\Models\KonfigurasiExport::FORMAT_ANGKA ? 'angka' : 'teks' }}</span>
                        </div>

                        {{-- Label header --}}
                        <form method="POST" action="{{ route('petugas.konfigurasi-export.atur', $k) }}" class="flex-1 min-w-0">
                            @csrf @method('PATCH')
                            <input type="text" name="label" value="{{ $k->label }}" maxlength="100"
                                   onchange="this.form.submit()"
                                   class="form-input text-sm py-1 w-full {{ $k->aktif ? '' : 'text-gray-400' }}"
                                   title="{{ $k->keteranganSumber() }}">
                        </form>

                        {{-- Geser urutan --}}
                        <div class="flex items-center flex-shrink-0">
                            <form method="POST" action="{{ route('petugas.konfigurasi-export.atur', $k) }}">
                                @csrf @method('PATCH')
                                <button type="submit" name="arah" value="naik" @disabled($i === 0)
                                        class="p-1.5 rounded-lg text-gray-500 hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed" title="Naik">
                                    <i class="bi bi-arrow-up"></i>
                                </button>
                            </form>
                            <form method="POST" action="{{ route('petugas.konfigurasi-export.atur', $k) }}">
                                @csrf @method('PATCH')
                                <button type="submit" name="arah" value="turun" @disabled($i === $kolom->count() - 1)
                                        class="p-1.5 rounded-lg text-gray-500 hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed" title="Turun">
                                    <i class="bi bi-arrow-down"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="px-4 py-3 border-t border-gray-100 flex justify-end">
                <x-konfirmasi
                    :action="route('petugas.konfigurasi-export.reset')"
                    method="POST"
                    varian="peringatan"
                    judul="Kembalikan kolom unduhan ke bawaan?"
                    pesan="Seluruh label, urutan, dan status aktif kolom dikembalikan ke keadaan awal (9 kolom standar). Tidak bisa dibatalkan."
                    tombol="Ya, Reset"
                    ikon="bi-arrow-counterclockwise"
                    pemicu="Reset ke Bawaan"
                    judul-pemicu="Kembalikan kolom unduhan ke bawaan"
                    kelas="btn-secondary text-sm" />
            </div>
        </div>

        {{-- ── Pengaturan format berkas ── --}}
        <div class="card p-6">
            <h2 class="text-sm font-bold text-gray-900 mb-1">Pengaturan Format Berkas</h2>
            <p class="text-xs text-gray-400 mb-5">Berlaku untuk fitur Unduh di Dashboard Publik.</p>

            <form method="POST" action="{{ route('petugas.konfigurasi-export.pengaturan') }}" class="grid gap-4 sm:grid-cols-2">
                @csrf @method('PATCH')

                <div>
                    <label for="format_bawaan" class="form-label">Format bawaan</label>
                    <select name="format_bawaan" id="format_bawaan" class="form-select @error('format_bawaan') border-red-400 @enderror">
                        <option value="excel" @selected(old('format_bawaan', $pengaturan->format_bawaan) === 'excel')>Excel (.xlsx)</option>
                        <option value="pdf" @selected(old('format_bawaan', $pengaturan->format_bawaan) === 'pdf')>PDF</option>
                    </select>
                    @error('format_bawaan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="orientasi_pdf" class="form-label">Orientasi PDF</label>
                    <select name="orientasi_pdf" id="orientasi_pdf" class="form-select @error('orientasi_pdf') border-red-400 @enderror">
                        <option value="landscape" @selected(old('orientasi_pdf', $pengaturan->orientasi_pdf) === 'landscape')>Landscape (melebar)</option>
                        <option value="potrait" @selected(old('orientasi_pdf', $pengaturan->orientasi_pdf) === 'potrait')>Portrait (tegak)</option>
                    </select>
                    @error('orientasi_pdf') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="batas_baris_pdf" class="form-label">Batas baris PDF</label>
                    <input type="number" name="batas_baris_pdf" id="batas_baris_pdf" min="100" max="100000"
                           value="{{ old('batas_baris_pdf', $pengaturan->batas_baris_pdf) }}"
                           class="form-input @error('batas_baris_pdf') border-red-400 @enderror">
                    <p class="mt-1 text-xs text-gray-400">Unduh PDF di atas angka ini ditolak (dompdf lambat/kehabisan memori). Excel tidak dibatasi.</p>
                    @error('batas_baris_pdf') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="kop_judul" class="form-label">Judul kop PDF</label>
                        <input type="text" name="kop_judul" id="kop_judul" maxlength="150"
                               value="{{ old('kop_judul', $pengaturan->kop_judul) }}"
                               placeholder="{{ \App\Models\PengaturanExport::KOP_JUDUL_BAWAAN }}"
                               class="form-input @error('kop_judul') border-red-400 @enderror">
                        @error('kop_judul') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="kop_subjudul" class="form-label">Subjudul kop PDF</label>
                        <input type="text" name="kop_subjudul" id="kop_subjudul" maxlength="200"
                               value="{{ old('kop_subjudul', $pengaturan->kop_subjudul) }}"
                               placeholder="{{ \App\Models\PengaturanExport::KOP_SUBJUDUL_BAWAAN }}"
                               class="form-input @error('kop_subjudul') border-red-400 @enderror">
                        @error('kop_subjudul') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <p class="sm:col-span-2 text-xs text-gray-400 -mt-2">Kosongkan untuk memakai teks bawaan.</p>
                </div>

                <div class="sm:col-span-2">
                    <button type="submit" class="btn-primary"><i class="bi bi-check-lg"></i> Simpan Pengaturan</button>
                </div>
            </form>
        </div>

        {{-- ── Pratinjau ── --}}
        <div class="card overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100">
                <h2 class="text-sm font-bold text-gray-900">Pratinjau Unduh PDF</h2>
                <p class="text-xs text-gray-400">15 baris pertama unduh PDF "semua indikator" (tanpa filter), memakai konfigurasi kolom di atas. Unduh Excel tidak seperti ini &mdash; lihat keterangan di atas.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="tw-table text-xs">
                    <thead>
                        <tr>
                            @foreach ($pratinjauKolom as $pk)
                                <th @class(['text-right' => $pk->format === \App\Models\KonfigurasiExport::FORMAT_ANGKA])>{{ $pk->label }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pratinjauBaris as $pb)
                            <tr>
                                @foreach ($pratinjauKolom as $pk)
                                    @php $nilai = $pb[$pk->kunci] ?? null; @endphp
                                    <td @class(['text-right tabular-nums' => $pk->format === \App\Models\KonfigurasiExport::FORMAT_ANGKA])>
                                        {{ $pk->format === \App\Models\KonfigurasiExport::FORMAT_ANGKA
                                            ? ($nilai === null ? '—' : number_format((int) $nilai, 0, ',', '.'))
                                            : ($nilai === null || $nilai === '' ? '—' : $nilai) }}
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr><td colspan="{{ max($pratinjauKolom->count(), 1) }}" class="text-center py-6 text-gray-400">Belum ada data untuk dipratinjau.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</x-layouts.app>
