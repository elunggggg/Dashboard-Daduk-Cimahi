<x-layouts.app title="Tampilan Dashboard Publik" breadcrumb="Halaman Petugas / Tampilan Dashboard Publik">

    <div class="max-w-2xl" x-data="{ preview: null, previewLogo: null }">
        <form method="POST" action="{{ route('petugas.pengaturan.update') }}" enctype="multipart/form-data" class="card p-6">
            @csrf
            @method('PUT')

            <h2 class="text-lg font-bold text-gray-900 mb-1">Gambar Latar Belakang Header</h2>
            <p class="text-sm text-gray-500 mb-6">
                Gambar ini MENGGANTIKAN judul teks "Dashboard Statistik Kependudukan..." di bagian atas Dashboard
                Publik. Kotak header ukurannya TETAP — gambar apa pun yang diunggah (potret/lanskap/persegi)
                akan menyesuaikan ke ukuran kotak ini (ditampilkan utuh, tidak dipotong). Kalau dihapus, kembali
                ke judul teks polos. Maks. 4&nbsp;MB, format JPG/PNG/WEBP.
            </p>

            <div class="rounded-xl overflow-hidden border border-gray-200 bg-gray-50 relative h-40 mb-5 flex items-center justify-center">
                {{-- Pratinjau: gambar baru yang dipilih (jika ada), kalau tidak gambar tersimpan saat ini —
                     kotak berukuran TETAP (h-40) + object-contain, supaya pratinjau ini sama persis dengan
                     tampilan sungguhan di Dashboard Publik (kotaknya tidak berubah ukuran per gambar). --}}
                <img x-show="preview" :src="preview" x-cloak class="w-full h-full object-contain p-3">
                <img x-show="!preview" src="{{ $pengaturan->latar_belakang_url }}"
                     class="w-full h-full object-contain p-3" @if(!$pengaturan->latar_belakang_url) x-cloak @endif>
                <div x-show="!preview && !@js((bool) $pengaturan->latar_belakang_url)"
                     class="flex items-center justify-center text-gray-400 text-sm">
                    <i class="bi bi-image text-2xl mr-2"></i> Belum ada gambar latar — judul teks akan tampil
                </div>
            </div>

            <div class="mb-2">
                <label class="form-label">Pilih Gambar Baru</label>
                <input type="file" name="latar_belakang" accept="image/png,image/jpeg,image/webp"
                       class="form-input @error('latar_belakang') border-red-400 @enderror"
                       @change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null">
                @error('latar_belakang') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            @if ($pengaturan->latar_belakang_url)
                <label class="flex items-center gap-2 text-sm text-gray-600 mt-3">
                    <input type="checkbox" name="hapus_latar" value="1" class="rounded border-gray-300">
                    Hapus gambar latar (kembali ke tampilan polos)
                </label>
            @endif

            <hr class="my-6 border-gray-100">

            <h2 class="text-lg font-bold text-gray-900 mb-1">Logo Aplikasi</h2>
            <p class="text-sm text-gray-500 mb-6">
                Tampil di navbar (semua halaman publik) dan di kiri masthead Dashboard Publik, menggantikan lencana
                bulat "DC". Disarankan gambar persegi, maks. 2&nbsp;MB, format JPG/PNG/WEBP.
            </p>

            <div class="flex items-center gap-4 mb-5">
                <div class="w-16 h-16 rounded-full overflow-hidden border border-gray-200 bg-gray-900 relative flex-shrink-0">
                    <img x-show="previewLogo" :src="previewLogo" x-cloak class="w-full h-full object-cover">
                    <img x-show="!previewLogo" src="{{ $pengaturan->logo_url }}"
                         class="w-full h-full object-cover" @if(!$pengaturan->logo_url) x-cloak @endif>
                    <div x-show="!previewLogo && !@js((bool) $pengaturan->logo_url)"
                         class="absolute inset-0 flex items-center justify-center text-gray-400">
                        <i class="bi bi-image"></i>
                    </div>
                </div>
                <div class="flex-1">
                    <input type="file" name="logo" accept="image/png,image/jpeg,image/webp"
                           class="form-input @error('logo') border-red-400 @enderror"
                           @change="previewLogo = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null">
                    @error('logo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    @if ($pengaturan->logo_url)
                        <label class="flex items-center gap-2 text-sm text-gray-600 mt-2">
                            <input type="checkbox" name="hapus_logo" value="1" class="rounded border-gray-300">
                            Hapus logo (kembali ke lencana "DC")
                        </label>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-2 mt-6 pt-5 border-t border-gray-100">
                <button type="submit" class="btn-primary"><i class="bi bi-check-lg"></i> Simpan Perubahan</button>
                <a href="{{ route('dashboard.publik') }}" target="_blank" class="btn-secondary">
                    <i class="bi bi-box-arrow-up-right"></i> Lihat Dashboard Publik
                </a>
            </div>
        </form>
    </div>

</x-layouts.app>
