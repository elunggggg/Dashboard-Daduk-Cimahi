<x-layouts.app title="Tambah Indikator" breadcrumb="Halaman Petugas / Kelola Indikator / Tambah">

    <div class="max-w-2xl">
        <a href="{{ route('petugas.indikator.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-900 mb-4">
            <i class="bi bi-arrow-left"></i> Kembali ke daftar indikator
        </a>

        <form method="POST" action="{{ route('petugas.indikator.store') }}" class="card p-6">
            @csrf

            <h2 class="text-lg font-bold text-gray-900 mb-1">Tambah Indikator Baru</h2>
            <p class="text-sm text-gray-500 mb-6">
                Menambah indikator di sini TIDAK mengisi angkanya — data tetap harus masuk lewat Import
                atau diisi manual lewat mekanisme lain. Halaman ini hanya mendaftarkan kategorinya.
            </p>

            @include('petugas.indikator._form', ['indikator' => null, 'jenisList' => $jenisList])

            <div class="flex items-center gap-2 mt-6 pt-5 border-t border-gray-100">
                <button type="submit" class="btn-primary"><i class="bi bi-check-lg"></i> Simpan</button>
                <a href="{{ route('petugas.indikator.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>

</x-layouts.app>
