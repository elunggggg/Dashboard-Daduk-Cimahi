<x-layouts.app title="Ubah Metadata" breadcrumb="Halaman Petugas / Kelola Metadata / Ubah">

    <div class="max-w-2xl">
        <a href="{{ route('petugas.metadata.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-900 mb-4">
            <i class="bi bi-arrow-left"></i> Kembali ke daftar metadata
        </a>

        <form method="POST" action="{{ route('petugas.metadata.update', $metadata) }}" class="card p-6">
            @csrf
            @method('PUT')

            <h2 class="text-lg font-bold text-gray-900 mb-1">Ubah {{ $metadata->nama }}</h2>
            <p class="text-sm text-gray-500 mb-6">Perubahan akan tercatat di Audit Log dan langsung tampil di halaman publik Metadata.</p>

            @include('petugas.metadata._form', ['metadata' => $metadata, 'modulList' => $modulList])

            <div class="flex items-center gap-2 mt-6 pt-5 border-t border-gray-100">
                <button type="submit" class="btn-primary"><i class="bi bi-check-lg"></i> Simpan Perubahan</button>
                <a href="{{ route('petugas.metadata.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>

</x-layouts.app>
