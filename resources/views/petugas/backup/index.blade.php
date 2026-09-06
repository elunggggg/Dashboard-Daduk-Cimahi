@php
    $formatByte = function ($b) {
        if ($b >= 1073741824) return number_format($b / 1073741824, 2, ',', '.').' GB';
        if ($b >= 1048576)    return number_format($b / 1048576, 2, ',', '.').' MB';
        if ($b >= 1024)       return number_format($b / 1024, 1, ',', '.').' KB';
        return $b.' B';
    };
@endphp

<x-layouts.app title="Backup Database" breadcrumb="Halaman Petugas / Backup Database">

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h2 class="text-lg font-bold text-gray-900">Backup Database</h2>
            <p class="text-sm text-gray-500">
                {{ $files->count() }} arsip tersimpan · total {{ $formatByte($totalByte) }}
            </p>
        </div>
        {{-- Formnya kosong dan dikirim lewat tombol di dalam modal (atribut form=""),
             supaya konfirmasi tampil lebih dulu sebelum proses sinkron yang lama dimulai. --}}
        <form method="POST" action="{{ route('petugas.backup.store') }}" id="form-buat-backup"
              onsubmit="const b = document.querySelector('button[form=&quot;form-buat-backup&quot;]');
                        if (b) { b.disabled = true; b.innerHTML = '<i class=&quot;bi bi-hourglass-split&quot;></i> Memproses…'; }">
            @csrf
        </form>

        <x-konfirmasi
            form="form-buat-backup"
            varian="peringatan"
            judul="Buat backup database sekarang?"
            pesan="Prosesnya berjalan sinkron — jangan tutup atau muat ulang halaman sampai selesai."
            tombol="Ya, Buat Backup"
            pemicu="Buat Backup Sekarang"
            ikon="bi-database-add"
            kelas="btn-primary">
            <ul class="list-disc list-inside space-y-1 text-xs text-gray-600">
                <li>Arsip berisi <strong>dump basis data saja</strong>, bukan berkas aplikasi.</li>
                <li>Biasanya selesai dalam beberapa detik, tapi bisa lebih lama bila datanya besar.</li>
                <li>Di Laragon, backup hanya jalan lewat Apache — bukan <code>php artisan serve</code>.</li>
            </ul>
        </x-konfirmasi>
    </div>

    <div class="alert-warning text-xs mb-5">
        <i class="bi bi-exclamation-triangle-fill mr-1"></i>
        Proses backup berjalan <strong>sinkron</strong> — halaman akan menunggu hingga selesai
        (biasanya beberapa detik). Arsip berisi <strong>dump basis data saja</strong>, bukan berkas aplikasi.
        Simpan salinannya di luar server ini; arsip yang hanya ada di satu mesin bukan cadangan.
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="tw-table">
                <thead>
                    <tr>
                        <th>Nama Berkas</th>
                        <th class="text-right">Ukuran</th>
                        <th>Dibuat</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($files as $f)
                        <tr>
                            <td class="font-mono text-xs text-gray-900">
                                <i class="bi bi-file-earmark-zip text-amber-500 mr-1"></i>{{ $f['nama'] }}
                            </td>
                            <td class="text-right tabular-nums">{{ $formatByte($f['ukuran']) }}</td>
                            <td class="text-gray-500 whitespace-nowrap">
                                {{ \Carbon\Carbon::createFromTimestamp($f['waktu'])->translatedFormat('d M Y H:i') }}
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    <form method="POST" action="{{ route('petugas.backup.unduh') }}">
                                        @csrf
                                        <input type="hidden" name="nama" value="{{ $f['nama'] }}">
                                        <button type="submit" title="Unduh"
                                                class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-brand-700 transition-colors">
                                            <i class="bi bi-download"></i>
                                        </button>
                                    </form>
                                    <x-konfirmasi
                                        :action="route('petugas.backup.destroy')"
                                        method="DELETE"
                                        judul="Hapus arsip backup ini?"
                                        pesan="Arsipnya dihapus permanen dari server. Tindakan ini tidak dapat dibatalkan."
                                        tombol="Ya, Hapus Arsip"
                                        ikon="bi-trash3"
                                        judul-pemicu="Hapus"
                                        kelas="p-2 rounded-lg text-gray-500 hover:bg-red-50 hover:text-red-600 transition-colors">
                                        <x-slot:input>
                                            <input type="hidden" name="nama" value="{{ $f['nama'] }}">
                                        </x-slot:input>
                                        <dl class="rounded-lg bg-gray-50 p-3 space-y-1 text-xs">
                                            <div class="flex justify-between gap-4">
                                                <dt class="text-gray-500">Berkas</dt>
                                                <dd class="font-mono text-gray-900 truncate">{{ $f['nama'] }}</dd>
                                            </div>
                                            <div class="flex justify-between gap-4">
                                                <dt class="text-gray-500">Ukuran</dt>
                                                <dd class="tabular-nums text-gray-900">{{ $formatByte($f['ukuran']) }}</dd>
                                            </div>
                                            <div class="flex justify-between gap-4">
                                                <dt class="text-gray-500">Dibuat</dt>
                                                <dd class="text-gray-900">
                                                    {{ \Carbon\Carbon::createFromTimestamp($f['waktu'])->translatedFormat('d M Y H:i') }}
                                                </dd>
                                            </div>
                                        </dl>
                                        <p class="mt-2 text-xs text-gray-500">
                                            Pastikan sudah ada salinannya di luar server ini sebelum menghapus.
                                        </p>
                                    </x-konfirmasi>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-10 text-gray-400">
                                <i class="bi bi-database-x text-2xl block mb-2"></i>
                                Belum ada arsip backup.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($riwayat->isNotEmpty())
        <div class="card p-4 mt-5">
            <h3 class="text-sm font-bold text-gray-900 mb-3">Riwayat Percobaan</h3>
            <div class="space-y-1.5">
                @foreach($riwayat as $r)
                    <div class="flex items-center gap-2 text-xs">
                        <span class="{{ $r->status === 'sukses' ? 'badge-green' : 'badge-red' }}">{{ $r->status }}</span>
                        <span class="font-mono text-gray-600 truncate">{{ $r->nama_file }}</span>
                        <span class="ml-auto text-gray-400 whitespace-nowrap">
                            {{ $r->created_at?->translatedFormat('d M Y H:i') }}
                        </span>
                    </div>
                    @if($r->catatan)
                        <p class="text-[11px] text-red-500 pl-2 truncate" title="{{ $r->catatan }}">{{ str($r->catatan)->limit(120) }}</p>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

</x-layouts.app>
