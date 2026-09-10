<x-layouts.app title="Ubah Kelurahan" breadcrumb="Halaman Petugas / Kelola Wilayah / Ubah">

    <div class="max-w-2xl">
        <a href="{{ route('petugas.wilayah.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-900 mb-4">
            <i class="bi bi-arrow-left"></i> Kembali ke daftar wilayah
        </a>

        <form method="POST" action="{{ route('petugas.wilayah.update', $wilayah) }}" class="card p-6">
            @csrf
            @method('PUT')

            <h2 class="text-lg font-bold text-gray-900 mb-1">Ubah {{ $wilayah->nama_kelurahan }}</h2>
            <p class="text-sm text-gray-500 mb-6">Perubahan akan tercatat di Audit Log.</p>

            @include('petugas.wilayah._form', ['wilayah' => $wilayah, 'kecamatanList' => $kecamatanList])

            <div class="flex items-center gap-2 mt-6 pt-5 border-t border-gray-100">
                <button type="submit" class="btn-primary"><i class="bi bi-check-lg"></i> Simpan Perubahan</button>
                <a href="{{ route('petugas.wilayah.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>

        {{-- ── Alias nama wilayah ── --}}
        <div class="card p-6 mt-6">
            <h2 class="text-lg font-bold text-gray-900 mb-1">Ejaan Alternatif (Alias)</h2>
            <p class="text-sm text-gray-500 mb-5">
                Variasi penulisan nama kelurahan ini yang mungkin muncul di berkas Excel Disdukcapil.
                Saat unggah data, baris yang namanya cocok dengan salah satu alias tetap dikenali sebagai
                <strong>{{ $wilayah->nama_kelurahan }}</strong>.
            </p>

            @if($wilayah->alias->isEmpty())
                <p class="text-sm text-gray-400 mb-5">
                    <i class="bi bi-info-circle mr-1"></i>
                    Belum ada alias. Nama resmi <strong>{{ $wilayah->nama_kelurahan }}</strong> tetap selalu dikenali.
                </p>
            @else
                <ul class="divide-y divide-gray-100 mb-5 border-y border-gray-100">
                    @foreach($wilayah->alias->sortBy('nama_alias') as $alias)
                        <li class="flex items-center justify-between py-2">
                            <span class="font-mono text-sm text-gray-700">{{ $alias->nama_alias }}</span>
                            <x-konfirmasi
                                :action="route('petugas.wilayah.alias.hapus', $alias)"
                                method="DELETE"
                                judul="Hapus alias {{ $alias->nama_alias }}?"
                                pesan="Setelah dihapus, baris Excel yang memakai ejaan ini tidak lagi dikenali sebagai {{ $wilayah->nama_kelurahan }} dan akan terlewat saat unggah data."
                                tombol="Ya, Hapus Alias"
                                pemicu="Hapus"
                                kelas="text-xs font-semibold text-red-600 hover:underline" />
                        </li>
                    @endforeach
                </ul>
            @endif

            <form method="POST" action="{{ route('petugas.wilayah.alias.tambah', $wilayah) }}"
                  class="flex flex-wrap items-start gap-2">
                @csrf
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="nama_alias" required maxlength="100"
                           placeholder="mis. KARANG MEKAR"
                           class="form-input font-mono @error('nama_alias') border-red-400 @enderror">
                    <p class="mt-1 text-xs text-gray-400">
                        Otomatis dibakukan jadi huruf kapital dengan spasi tunggal.
                    </p>
                    @error('nama_alias') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="btn-secondary"><i class="bi bi-plus-lg"></i> Tambah Alias</button>
            </form>
        </div>
    </div>

</x-layouts.app>
