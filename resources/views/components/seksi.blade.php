{{--
    Pembungkus satu bagian halaman publik.

    <x-seksi kunci="anak" halaman="demografi" judul="Jumlah Anak (0-14 Tahun)"
             lebar="sepertiga" :urutan="30"> ...isi kartu... </x-seksi>

    - `halaman` di sini = halaman ASAL (tempat bagian ini dideklarasikan). Halaman
      TUJUAN sesungguhnya diambil dari DB (bisa dipindah Petugas lewat menu
      "Bagian Dashboard").
    - Pada halaman ber-grid cair (Demografi/Sosial, lewat DashboardHalaman yang
      memanggil setHalamanAktif()): bagian hanya dirender kalau halaman DB-nya
      == halaman yang sedang dibuka DAN tampil, lalu dibungkus .seksi-item dengan
      `order` + lebar dari DB supaya sisanya mengalir otomatis.
    - Pada halaman lain (mis. Mobilitas, yang tidak memakai grid cair): cukup
      dihormati status tampil-nya; tidak ada pembungkus tambahan.
--}}
@props(['kunci', 'halaman', 'judul' => null, 'urutan' => 0, 'lebar' => 'sepertiga'])

@php
    $__reg   = app(\App\Services\SeksiDashboardRegistry::class);
    $__seksi = $__reg->daftar($kunci, $halaman, $judul ?? $kunci, (int) $urutan, $lebar);
    $__halamanAktif = $__reg->halamanAktif();
    $__lebar = in_array($__seksi->lebar, ['sepertiga','separuh','penuh'], true) ? $__seksi->lebar : 'sepertiga';
@endphp

@if ($__halamanAktif !== null)
    {{-- Halaman grid cair (Demografi/Sosial) --}}
    @if ($__seksi->tampil && $__seksi->halaman === $__halamanAktif)
        <div class="seksi-item seksi-w-{{ $__lebar }}" style="order: {{ (int) $__seksi->urutan }}">
            {{ $slot }}
        </div>
    @endif
@else
    {{-- Halaman lain (mis. Mobilitas) — hormati status tampil saja --}}
    @if ($__seksi->tampil)
        {{ $slot }}
    @endif
@endif
