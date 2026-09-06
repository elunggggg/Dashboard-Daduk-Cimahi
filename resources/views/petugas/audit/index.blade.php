<x-layouts.app title="Audit Log" breadcrumb="Halaman Petugas / Audit Log">

    @php
        $aksiStyle = [
            'CREATE' => ['badge-green', 'bi-plus-circle'],
            'UPDATE' => ['badge-blue',  'bi-pencil'],
            'DELETE' => ['badge-red',   'bi-trash3'],
            'LOGIN'  => ['badge-gray',  'bi-box-arrow-in-right'],
            'LOGOUT' => ['badge-gray',  'bi-box-arrow-left'],
            'IMPORT' => ['badge-blue',  'bi-upload'],
        ];
    @endphp

    <div class="mb-5">
        <h2 class="text-lg font-bold text-gray-900">Jejak Aktivitas</h2>
        <p class="text-sm text-gray-500">{{ number_format($logs->total(), 0, ',', '.') }} entri tercatat</p>
    </div>

    {{-- ── Filter ── --}}
    <form method="GET" action="{{ route('petugas.audit.index') }}" class="card p-4 mb-5">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
            <div>
                <label for="aksi" class="form-label">Aksi</label>
                <select name="aksi" id="aksi" class="form-select">
                    <option value="">Semua</option>
                    @foreach($aksiList as $a)
                        <option value="{{ $a }}" @selected(($filter['aksi'] ?? null) === $a)>{{ $a }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="user_id" class="form-label">Pengguna</label>
                <select name="user_id" id="user_id" class="form-select">
                    <option value="">Semua</option>
                    @foreach($penggunaList as $p)
                        <option value="{{ $p->id }}" @selected((int) ($filter['user_id'] ?? 0) === $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="dari" class="form-label">Dari Tanggal</label>
                <input type="date" name="dari" id="dari" value="{{ $filter['dari'] ?? '' }}" class="form-input">
            </div>
            <div>
                <label for="sampai" class="form-label">Sampai Tanggal</label>
                <input type="date" name="sampai" id="sampai" value="{{ $filter['sampai'] ?? '' }}" class="form-input">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn-primary"><i class="bi bi-funnel"></i> Filter</button>
                @if(array_filter($filter))
                    <a href="{{ route('petugas.audit.index') }}" class="btn-secondary">Reset</a>
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
                        <th>Waktu</th>
                        <th>Pengguna</th>
                        <th>Aksi</th>
                        <th>Tabel</th>
                        <th>IP</th>
                        <th class="text-right">Detail</th>
                    </tr>
                </thead>
                @forelse($logs as $log)
                    @php
                        [$badge, $icon] = $aksiStyle[$log->aksi] ?? ['badge-gray', 'bi-dot'];
                        $sebelum = $log->perubahan['sebelum'] ?? null;
                        $sesudah = $log->perubahan['sesudah'] ?? null;
                        $adaDetail = $sebelum || $sesudah;
                    @endphp
                    {{-- Satu <tbody> per entri agar scope Alpine mencakup baris detail di bawahnya --}}
                    <tbody x-data="{ open: false }">
                        <tr>
                            <td class="whitespace-nowrap text-gray-500">
                                {{ $log->created_at?->translatedFormat('d M Y H:i') ?? '—' }}
                            </td>
                            <td>
                                @if($log->user)
                                    <span class="font-medium text-gray-900">{{ $log->user->name }}</span>
                                    <span class="block text-xs text-gray-400">{{ $log->user->email }}</span>
                                @else
                                    <span class="text-gray-400 italic">Pengguna dihapus</span>
                                @endif
                            </td>
                            <td>
                                <span class="{{ $badge }}"><i class="bi {{ $icon }} mr-1"></i>{{ $log->aksi }}</span>
                            </td>
                            <td class="text-gray-500 font-mono text-xs">{{ $log->subjek_tipe ?? '—' }}</td>
                            <td class="text-gray-500 font-mono text-xs">{{ $log->ip_address ?? '—' }}</td>
                            <td class="text-right">
                                @if($adaDetail)
                                    <button type="button" @click="open = !open"
                                            class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-brand-700 transition-colors">
                                        <i class="bi" :class="open ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                                    </button>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                        </tr>

                        @if($adaDetail)
                            <tr x-show="open" x-cloak>
                                <td colspan="6" class="bg-gray-50 !px-4 !py-4">
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Data Lama</p>
                                            @if($sebelum)
                                                <pre class="text-xs bg-white border border-gray-200 rounded-lg p-3 overflow-x-auto text-gray-700">{{ json_encode($sebelum, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                            @else
                                                <p class="text-xs text-gray-400 italic">—</p>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Data Baru</p>
                                            @if($sesudah)
                                                <pre class="text-xs bg-white border border-gray-200 rounded-lg p-3 overflow-x-auto text-gray-700">{{ json_encode($sesudah, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                            @else
                                                <p class="text-xs text-gray-400 italic">—</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                @empty
                    <tbody>
                        <tr>
                            <td colspan="6" class="text-center py-10 text-gray-400">
                                <i class="bi bi-journal-x text-2xl block mb-2"></i>
                                Belum ada aktivitas yang cocok dengan filter.
                            </td>
                        </tr>
                    </tbody>
                @endforelse
            </table>
        </div>
    </div>

    @if($logs->hasPages())
        <div class="mt-4">{{ $logs->links() }}</div>
    @endif

</x-layouts.app>
