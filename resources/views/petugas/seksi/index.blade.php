<x-layouts.app title="Bagian Dashboard" breadcrumb="Halaman Petugas / Bagian Dashboard">

    <div class="max-w-4xl space-y-5">

        <div class="card p-4 bg-brand-50/40 border-brand-100 text-sm text-gray-600">
            <p class="flex items-start gap-2 mb-2">
                <i class="bi bi-info-circle-fill text-brand-600 mt-0.5"></i>
                <span>Setiap perubahan langsung tersimpan (tidak ada tombol "Simpan").</span>
            </p>
            <ul class="list-disc list-inside space-y-1 pl-1">
                <li><i class="bi bi-eye"></i> / <i class="bi bi-eye-slash"></i> &mdash; tampilkan / sembunyikan bagian dari halaman publik.</li>
                <li><strong>Halaman</strong> &mdash; pindahkan bagian ke modul mana pun: <em>Dashboard Publik</em>, <em>Demografi</em>, <em>Sosial</em>, atau <em>Mobilitas</em>.</li>
                <li><i class="bi bi-arrow-up"></i> <i class="bi bi-arrow-down"></i> &mdash; geser posisi bagian naik / turun di halamannya.</li>
                <li><strong>Lebar</strong> &mdash; berapa bagian per baris: <em>Sepertiga</em> = 3 per baris, <em>Separuh</em> = 2 per baris, <em>Penuh</em> = melebar 1 baris sendiri. Kalau ada bagian yang disembunyikan, sisa bagian di baris itu otomatis melebar mengisi ruang.</li>
            </ul>
            <p class="mt-2 text-xs text-gray-500">
                Bagian di halaman <strong>Dashboard Publik</strong> selalu se-Kota &amp; periode terbaru (tanpa filter). Daftar belum lengkap? Buka dulu halaman
                <a href="{{ route('demografi.index') }}" target="_blank" class="text-brand-700 underline">Demografi</a> /
                <a href="{{ route('sosial.index') }}" target="_blank" class="text-brand-700 underline">Sosial</a> /
                <a href="{{ route('mobilitas.index') }}" target="_blank" class="text-brand-700 underline">Mobilitas</a>, lalu segarkan halaman ini.
            </p>
        </div>

        <div class="flex justify-end">
            <x-konfirmasi
                :action="route('petugas.seksi.reset')"
                method="POST"
                varian="peringatan"
                judul="Reset semua pengaturan bagian?"
                pesan="Seluruh pengaturan tampil/sembunyi, halaman, urutan, dan lebar dikembalikan ke keadaan awal (sesuai bawaan aplikasi). Tidak bisa dibatalkan."
                tombol="Ya, Reset ke Awal"
                ikon="bi-arrow-counterclockwise"
                pemicu="Reset ke Awal"
                judul-pemicu="Kembalikan semua bagian ke keadaan awal"
                kelas="btn-secondary text-sm">
                <p class="text-xs text-gray-500">
                    Daftar bagian akan terbentuk ulang otomatis saat halaman Demografi / Sosial / Mobilitas dibuka lagi.
                </p>
            </x-konfirmasi>
        </div>

        @if ($seksiPerHalaman->every(fn ($g) => $g->isEmpty()))
            <div class="card p-8 text-center text-gray-400">
                <i class="bi bi-inbox text-2xl block mb-2"></i>
                Belum ada bagian yang tercatat. Buka salah satu halaman publik dulu, lalu kembali ke sini.
            </div>
        @else
            @php $lebarLabel = ['sepertiga' => 'Sepertiga (3/baris)', 'separuh' => 'Separuh (2/baris)', 'penuh' => 'Penuh (1/baris)']; @endphp

            @foreach ($seksiPerHalaman as $halaman => $daftar)
                @php $bisaPindah = in_array($halaman, \App\Services\SeksiDashboardRegistry::HALAMAN_MODUL, true); @endphp
                <div class="card overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                        <h2 class="text-sm font-bold text-gray-900">
                            {{ \App\Models\SeksiDashboard::LABEL_HALAMAN[$halaman] ?? ucfirst($halaman) }}
                        </h2>
                        <span class="text-xs text-gray-400">
                            {{ $daftar->where('tampil', true)->count() }} / {{ $daftar->count() }} tampil
                        </span>
                    </div>
                    <div class="divide-y divide-gray-50">
                        @if ($daftar->isEmpty())
                            <p class="px-4 py-4 text-xs text-gray-400">
                                Belum ada bagian di sini. Pindahkan bagian dari modul lain lewat kolom <strong>Halaman</strong>.
                            </p>
                        @endif
                        @foreach ($daftar as $i => $seksi)
                            @php $terkunci = in_array($seksi->kunci, \App\Services\SeksiDashboardRegistry::TERKUNCI, true); @endphp
                            <div class="flex items-center gap-3 px-4 py-2.5 {{ $seksi->tampil ? '' : 'bg-gray-50/60' }}">

                                {{-- Tampil / sembunyi --}}
                                <form method="POST" action="{{ route('petugas.seksi.atur', $seksi) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" name="tampil" value="{{ $seksi->tampil ? 0 : 1 }}"
                                            class="p-1.5 rounded-lg {{ $seksi->tampil ? 'text-green-600 hover:bg-green-50' : 'text-gray-400 hover:bg-gray-100' }}"
                                            title="{{ $seksi->tampil ? 'Sembunyikan' : 'Tampilkan' }}">
                                        <i class="bi {{ $seksi->tampil ? 'bi-eye-fill' : 'bi-eye-slash' }}"></i>
                                    </button>
                                </form>

                                <div class="flex-1 min-w-0">
                                    <p class="text-sm {{ $seksi->tampil ? 'text-gray-900' : 'text-gray-400 line-through' }} truncate">{{ $seksi->judul }}</p>
                                    <code class="text-[10px] text-gray-300 font-mono">{{ $seksi->kunci }}</code>
                                </div>

                                @if ($terkunci)
                                    <span class="text-[11px] text-gray-400 italic flex-shrink-0">bawaan Dashboard — hanya tampil/sembunyi</span>
                                @else
                                {{-- Pindah halaman --}}
                                @if ($bisaPindah)
                                    <form method="POST" action="{{ route('petugas.seksi.atur', $seksi) }}">
                                        @csrf @method('PATCH')
                                        <select name="halaman" onchange="this.form.submit()" class="form-select text-xs py-1 w-auto">
                                            @foreach (\App\Models\SeksiDashboard::LABEL_HALAMAN as $hVal => $hLbl)
                                                <option value="{{ $hVal }}" @selected($seksi->halaman === $hVal)>{{ $hLbl }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                @endif

                                {{-- Geser urutan --}}
                                <div class="flex items-center">
                                    <form method="POST" action="{{ route('petugas.seksi.atur', $seksi) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" name="arah" value="naik" @disabled($i === 0)
                                                class="p-1.5 rounded-lg text-gray-500 hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed" title="Naik">
                                            <i class="bi bi-arrow-up"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('petugas.seksi.atur', $seksi) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" name="arah" value="turun" @disabled($i === $daftar->count() - 1)
                                                class="p-1.5 rounded-lg text-gray-500 hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed" title="Turun">
                                            <i class="bi bi-arrow-down"></i>
                                        </button>
                                    </form>
                                </div>

                                {{-- Lebar --}}
                                @if ($bisaPindah)
                                    <form method="POST" action="{{ route('petugas.seksi.atur', $seksi) }}">
                                        @csrf @method('PATCH')
                                        <select name="lebar" onchange="this.form.submit()" class="form-select text-xs py-1 w-auto">
                                            @foreach ($lebarLabel as $val => $lbl)
                                                <option value="{{ $val }}" @selected($seksi->lebar === $val)>{{ $lbl }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-300 w-[7.5rem] text-center">&mdash;</span>
                                @endif
                                @endif {{-- !$terkunci --}}
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="pt-1">
                <a href="{{ route('dashboard.publik') }}" target="_blank" class="btn-secondary text-sm">
                    <i class="bi bi-box-arrow-up-right"></i> Lihat Dashboard Publik
                </a>
            </div>
        @endif
    </div>

</x-layouts.app>
