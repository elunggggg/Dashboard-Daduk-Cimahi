{{-- Wadah tunggal semua bagian Demografi + Sosial. Kedua partial dirender di
     sini; masing-masing <x-seksi> memutuskan sendiri apakah ia tampil pada
     halaman yang sedang dibuka ($halamanAktif) — jadi bagian bisa "pindah"
     antar halaman hanya dengan mengubah kolom `halaman` di DB.

     .seksi-grid = flex-wrap: urutan diatur lewat CSS `order` dan lebar lewat
     kelas seksi-w-* (dari nilai DB), sehingga menyembunyikan / memindah /
     memperkecil satu bagian membuat sisanya mengalir mengisi ruang. --}}
<div class="seksi-grid">
    @include('demografi._konten')
    @include('sosial._konten')
</div>
