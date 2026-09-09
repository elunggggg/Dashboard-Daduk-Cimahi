{{--
    Pembungkus satu bagian halaman publik.

    <x-seksi kunci="anak" halaman="demografi" judul="Jumlah Anak (0-14 Tahun)"
             lebar="sepertiga" :urutan="30"> ...isi kartu... </x-seksi>

    - `halaman` = halaman ASAL (tempat bagian dideklarasikan). Halaman TUJUAN
      diambil dari DB (bisa dipindah Petugas lewat menu "Bagian Dashboard").
    - Halaman ber-grid cair (Demografi/Sosial/Mobilitas + zona Dashboard):
      dirender kalau `tampil && halaman==halamanAktif`, dibungkus .seksi-item
      (order + lebar dari DB).
    - `:bungkus="false"` → tetap dihormati `tampil`-nya & dicek halamannya, TAPI
      tanpa pembungkus .seksi-item. Untuk bagian yang punya tata letak sendiri
      (mis. blok bawaan Dashboard Publik yang digerakkan Alpine sendiri) —
      cuma butuh tombol tampil/sembunyi di menu "Bagian Dashboard".
--}}
@props(['kunci', 'halaman', 'judul' => null, 'urutan' => 0, 'lebar' => 'sepertiga', 'bungkus' => true])

@php
    $__reg   = app(\App\Services\SeksiDashboardRegistry::class);
    $__seksi = $__reg->daftar($kunci, $halaman, $judul ?? $kunci, (int) $urutan, $lebar);
    $__halamanAktif = $__reg->halamanAktif();
    $__lebar = in_array($__seksi->lebar, ['sepertiga','separuh','penuh'], true) ? $__seksi->lebar : 'sepertiga';
    // Di halaman grid cair, cek juga halaman DB-nya; di luar itu cukup `tampil`.
    $__tampil = $__seksi->tampil
        && ($__halamanAktif === null || $__seksi->halaman === $__halamanAktif);
@endphp

@if ($__tampil)
    @if ($bungkus && $__halamanAktif !== null)
        <div class="seksi-item seksi-w-{{ $__lebar }}" style="order: {{ (int) $__seksi->urutan }}">
            {{ $slot }}
        </div>
    @else
        {{ $slot }}
    @endif
@endif
