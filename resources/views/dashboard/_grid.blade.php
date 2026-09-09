{{-- Wadah tunggal semua bagian Demografi + Sosial + Mobilitas. Ketiga partial
     dirender di sini; masing-masing <x-seksi> memutuskan sendiri apakah ia
     tampil pada halaman yang sedang dibuka ($halamanAktif) — jadi bagian bisa
     "pindah" antar halaman (Dashboard / Demografi / Sosial / Mobilitas) hanya
     dengan mengubah kolom `halaman` di DB.

     .seksi-grid = flex-wrap: urutan lewat CSS `order`, lebar lewat kelas
     seksi-w-* (dari nilai DB) → sembunyikan / pindah / perkecil satu bagian =
     sisanya mengalir mengisi ruang. --}}
<div class="seksi-grid">
    @include('dashboard._konten_kota')
    @include('demografi._konten')
    @include('sosial._konten')
    @include('mobilitas._konten')
    @include('dashboard._konten_perbandingan')
</div>
