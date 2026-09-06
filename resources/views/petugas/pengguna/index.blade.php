<x-layouts.app title="Kelola Pengguna" breadcrumb="Halaman Petugas / Kelola Pengguna">

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h2 class="text-lg font-bold text-gray-900">Akun Pengguna</h2>
            <p class="text-sm text-gray-500">{{ $pengguna->total() }} akun terdaftar</p>
        </div>
        <a href="{{ route('petugas.pengguna.create') }}" class="btn-primary">
            <i class="bi bi-person-plus"></i> Tambah Pengguna
        </a>
    </div>

    <form method="GET" action="{{ route('petugas.pengguna.index') }}" class="card p-4 mb-5">
        <div class="grid gap-3 sm:grid-cols-[1fr_auto] sm:items-end">
            <div>
                <label for="q" class="form-label">Cari</label>
                <input type="text" name="q" id="q" value="{{ $q }}"
                       placeholder="Nama atau email…" class="form-input">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn-primary"><i class="bi bi-search"></i> Filter</button>
                @if($q)
                    <a href="{{ route('petugas.pengguna.index') }}" class="btn-secondary">Reset</a>
                @endif
            </div>
        </div>
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="tw-table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Dibuat</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pengguna as $u)
                        <tr>
                            <td>
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center flex-shrink-0">
                                        <span class="text-xs font-bold">{{ strtoupper(substr($u->name, 0, 1)) }}</span>
                                    </div>
                                    <span class="font-medium text-gray-900">{{ $u->name }}</span>
                                    @if($u->is(auth()->user()))
                                        <span class="badge-gray">Anda</span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-gray-500">{{ $u->email }}</td>
                            <td class="text-gray-500">{{ $u->created_at?->translatedFormat('d M Y') ?? '—' }}</td>
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('petugas.pengguna.edit', $u) }}"
                                       class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-brand-700 transition-colors"
                                       title="Ubah">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    @unless($u->is(auth()->user()))
                                        <x-konfirmasi
                                            :action="route('petugas.pengguna.destroy', $u)"
                                            method="DELETE"
                                            judul="Hapus akun {{ $u->name }}?"
                                            pesan="Akun ini langsung kehilangan akses ke dashboard. Jejaknya di audit log tetap tersimpan."
                                            tombol="Ya, Hapus Akun"
                                            ikon="bi-trash3"
                                            judul-pemicu="Hapus"
                                            kelas="p-2 rounded-lg text-gray-500 hover:bg-red-50 hover:text-red-600 transition-colors">
                                            <dl class="rounded-lg bg-gray-50 p-3 space-y-1 text-xs">
                                                <div class="flex justify-between gap-4">
                                                    <dt class="text-gray-500">Email</dt>
                                                    <dd class="text-gray-900">{{ $u->email }}</dd>
                                                </div>
                                            </dl>
                                            <p class="mt-2 text-xs text-red-600">
                                                <i class="bi bi-shield-exclamation mr-1"></i>
                                                Bila ini akun Petugas terakhir, penghapusannya akan ditolak sistem.
                                            </p>
                                        </x-konfirmasi>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-10 text-gray-400">
                                <i class="bi bi-person-x text-2xl block mb-2"></i>
                                Tidak ada pengguna yang cocok dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($pengguna->hasPages())
        <div class="mt-4">{{ $pengguna->links() }}</div>
    @endif

</x-layouts.app>
