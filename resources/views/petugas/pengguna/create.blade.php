<x-layouts.app title="Tambah Pengguna" breadcrumb="Halaman Petugas / Kelola Pengguna / Tambah">

    <div class="max-w-2xl">
        <a href="{{ route('petugas.pengguna.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-900 mb-4">
            <i class="bi bi-arrow-left"></i> Kembali ke daftar pengguna
        </a>

        <form method="POST" action="{{ route('petugas.pengguna.store') }}" class="card p-6">
            @csrf

            <h2 class="text-lg font-bold text-gray-900 mb-1">Tambah Pengguna</h2>
            <p class="text-sm text-gray-500 mb-6">Sampaikan kata sandi awal ke pengguna melalui kanal yang aman.</p>

            @include('petugas.pengguna._form', ['pengguna' => null])

            <div class="flex items-center gap-2 mt-6 pt-5 border-t border-gray-100">
                <button type="submit" class="btn-primary"><i class="bi bi-check-lg"></i> Simpan</button>
                <a href="{{ route('petugas.pengguna.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>

</x-layouts.app>
