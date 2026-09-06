<x-layouts.app title="Ubah Indikator" breadcrumb="Halaman Petugas / Kelola Indikator / Ubah">

    <div class="max-w-2xl">
        <a href="{{ route('petugas.indikator.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-900 mb-4">
            <i class="bi bi-arrow-left"></i> Kembali ke daftar indikator
        </a>

        <form method="POST" action="{{ route('petugas.indikator.update', $indikator) }}" class="card p-6">
            @csrf
            @method('PUT')

            <h2 class="text-lg font-bold text-gray-900 mb-1">Ubah {{ $indikator->label }}</h2>
            <p class="text-sm text-gray-500 mb-6">
                Perubahan akan tercatat di Audit Log. Baris data agregat yang sudah memakai indikator ini
                ({{ number_format($indikator->dataAgregat()->count(), 0, ',', '.') }} baris) tidak ikut berubah.
            </p>

            @include('petugas.indikator._form', ['indikator' => $indikator, 'jenisList' => $jenisList])

            <div class="flex items-center gap-2 mt-6 pt-5 border-t border-gray-100">
                <button type="submit" class="btn-primary"><i class="bi bi-check-lg"></i> Simpan Perubahan</button>
                <a href="{{ route('petugas.indikator.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>

</x-layouts.app>
