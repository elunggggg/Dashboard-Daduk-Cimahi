<x-layouts.app title="Bagian Dashboard" breadcrumb="Halaman Petugas / Bagian Dashboard">

    <div class="max-w-4xl space-y-5">

        <div class="card p-4 bg-brand-50/40 border-brand-100 text-sm text-gray-600">
            <p class="flex items-start gap-2">
                <i class="bi bi-info-circle-fill text-brand-600 mt-0.5"></i>
                <span>
                    Atur tiap grafik/tabel di halaman publik: <strong class="text-gray-800">tampil/sembunyi</strong>,
                    <strong class="text-gray-800">pindah halaman</strong> (Demografi &harr; Sosial),
                    <strong class="text-gray-800">urutan</strong>, dan <strong class="text-gray-800">lebar</strong>
                    (sepertiga / separuh / penuh). Halaman publik memakai grid cair &mdash; menyembunyikan,
                    memindah, atau mengecilkan satu bagian membuat sisanya otomatis mengalir mengisi ruang.
                    Bagian yang belum muncul di daftar: buka dulu halaman
                    <a href="{{ route('demografi.index') }}" target="_blank" class="text-brand-700 underline">Demografi</a> /
                    <a href="{{ route('sosial.index') }}" target="_blank" class="text-brand-700 underline">Sosial</a> /
                    <a href="{{ route('mobilitas.index') }}" target="_blank" class="text-brand-700 underline">Mobilitas</a>,
                    lalu segarkan halaman ini. Bagian <strong>Mobilitas</strong> hanya bisa disembunyikan/diurutkan
                    (halaman itu memakai tata letak sendiri, tidak bisa jadi tujuan pindah).
                </span>
            </p>
        </div>

        @if ($seksiPerHalaman->isEmpty())
            <div class="card p-8 text-center text-gray-400">
                <i class="bi bi-inbox text-2xl block mb-2"></i>
                Belum ada bagian yang tercatat. Buka salah satu halaman publik dulu, lalu kembali ke sini.
            </div>
        @else
            <form method="POST" action="{{ route('petugas.seksi.update') }}" class="space-y-5">
                @csrf
                @method('PUT')

                @foreach ($seksiPerHalaman as $halaman => $daftar)
                    @php $bisaPindah = in_array($halaman, ['demografi', 'sosial'], true); @endphp
                    <div class="card overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                            <h2 class="text-sm font-bold text-gray-900">
                                {{ \App\Models\SeksiDashboard::LABEL_HALAMAN[$halaman] ?? ucfirst($halaman) }}
                            </h2>
                            <span class="text-xs text-gray-400">
                                {{ $daftar->where('tampil', true)->count() }} / {{ $daftar->count() }} tampil
                            </span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-left text-[11px] text-gray-400 uppercase tracking-wide bg-gray-50 border-b border-gray-100">
                                        <th class="px-3 py-2 font-semibold">Tampil</th>
                                        <th class="px-3 py-2 font-semibold">Bagian</th>
                                        <th class="px-3 py-2 font-semibold">Halaman</th>
                                        <th class="px-3 py-2 font-semibold text-center">Urutan</th>
                                        <th class="px-3 py-2 font-semibold">Lebar</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @foreach ($daftar as $seksi)
                                        <tr class="{{ $seksi->tampil ? '' : 'bg-gray-50/60' }}">
                                            <td class="px-3 py-2">
                                                <input type="checkbox" name="tampil[]" value="{{ $seksi->id }}"
                                                       class="rounded border-gray-300 text-brand-600 focus:ring-brand-500"
                                                       @checked($seksi->tampil)>
                                            </td>
                                            <td class="px-3 py-2 {{ $seksi->tampil ? 'text-gray-900' : 'text-gray-400 line-through' }}">
                                                {{ $seksi->judul }}
                                                <code class="block text-[10px] text-gray-300 font-mono">{{ $seksi->kunci }}</code>
                                            </td>
                                            <td class="px-3 py-2">
                                                @if ($bisaPindah)
                                                    <select name="halaman[{{ $seksi->id }}]" class="form-select text-xs py-1 w-auto">
                                                        <option value="demografi" @selected($seksi->halaman === 'demografi')>Demografi</option>
                                                        <option value="sosial" @selected($seksi->halaman === 'sosial')>Sosial</option>
                                                    </select>
                                                @else
                                                    <span class="text-xs text-gray-400">Mobilitas</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <input type="number" name="urutan[{{ $seksi->id }}]" value="{{ $seksi->urutan }}"
                                                       min="0" max="9999"
                                                       class="form-input text-xs py-1 w-16 text-center">
                                            </td>
                                            <td class="px-3 py-2">
                                                @if ($bisaPindah)
                                                    <select name="lebar[{{ $seksi->id }}]" class="form-select text-xs py-1 w-auto">
                                                        <option value="sepertiga" @selected($seksi->lebar === 'sepertiga')>Sepertiga</option>
                                                        <option value="separuh" @selected($seksi->lebar === 'separuh')>Separuh</option>
                                                        <option value="penuh" @selected($seksi->lebar === 'penuh')>Penuh</option>
                                                    </select>
                                                @else
                                                    <span class="text-xs text-gray-300">&mdash;</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach

                <div class="flex items-center gap-2 pt-1">
                    <button type="submit" class="btn-primary"><i class="bi bi-check-lg"></i> Simpan Perubahan</button>
                    <a href="{{ route('dashboard.publik') }}" target="_blank" class="btn-secondary">
                        <i class="bi bi-box-arrow-up-right"></i> Lihat Dashboard Publik
                    </a>
                </div>
            </form>
        @endif
    </div>

</x-layouts.app>
