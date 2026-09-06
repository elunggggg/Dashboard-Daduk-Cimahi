<x-layouts.app title="Ubah Pengguna" breadcrumb="Halaman Petugas / Kelola Pengguna / Ubah">

    <div class="max-w-2xl">
        <a href="{{ route('petugas.pengguna.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-900 mb-4">
            <i class="bi bi-arrow-left"></i> Kembali ke daftar pengguna
        </a>

        <form method="POST" action="{{ route('petugas.pengguna.update', $pengguna) }}" class="card p-6">
            @csrf
            @method('PUT')

            <h2 class="text-lg font-bold text-gray-900 mb-1">Ubah {{ $pengguna->name }}</h2>
            <p class="text-sm text-gray-500 mb-6">Perubahan akan tercatat di Audit Log (kata sandi tidak pernah disimpan di log).</p>

            @include('petugas.pengguna._form', ['pengguna' => $pengguna])

            <div class="flex items-center gap-2 mt-6 pt-5 border-t border-gray-100">
                <button type="submit" class="btn-primary"><i class="bi bi-check-lg"></i> Simpan Perubahan</button>
                <a href="{{ route('petugas.pengguna.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>

</x-layouts.app>
