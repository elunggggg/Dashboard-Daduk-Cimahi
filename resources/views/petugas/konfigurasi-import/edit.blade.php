<x-layouts.app title="Ubah Konfigurasi Import" breadcrumb="Halaman Petugas / Konfigurasi Import / Ubah">

    <div class="max-w-3xl">
        <a href="{{ route('petugas.konfigurasi-import.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-900 mb-4">
            <i class="bi bi-arrow-left"></i> Kembali ke daftar konfigurasi
        </a>

        <form method="POST" action="{{ route('petugas.konfigurasi-import.update', $konfigurasi) }}" class="card p-6">
            @csrf
            @method('PUT')

            <h2 class="text-lg font-bold text-gray-900 mb-1">Ubah {{ $konfigurasi->label }}</h2>
            <p class="text-sm text-gray-500 mb-6">
                Sheet {{ $konfigurasi->nama_sheet }} · Perubahan tercatat di Audit Log.
                Uji ulang lewat <a href="{{ route('petugas.konfigurasi-import.uji') }}" class="text-brand-700 hover:underline">Uji Coba Pemetaan</a>
                sebelum dipakai import sungguhan.
            </p>

            @include('petugas.konfigurasi-import._form', ['konfigurasi' => $konfigurasi, 'profilList' => $profilList, 'sheetList' => $sheetList])

            <div class="flex items-center gap-2 mt-6 pt-5 border-t border-gray-100">
                <button type="submit" class="btn-primary"><i class="bi bi-check-lg"></i> Simpan Perubahan</button>
                <a href="{{ route('petugas.konfigurasi-import.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>

</x-layouts.app>
