<?php

/*
 * Satu-satunya sumber kebenaran warna dashboard. Nilai-nilai ini SENGAJA sama
 * persis dengan token warna Tailwind bawaan (blue-500, pink-500, dst) dan
 * dengan resources/js/chart-colors.js — kalau salah satu diubah, ubah semuanya
 * supaya Blade (class Tailwind) dan Chart.js (butuh hex literal) tetap konsisten.
 *
 * Dipakai di Blade untuk kasus yang butuh hex literal (mis. dot warna inline,
 * bukan class Tailwind) — untuk class Tailwind biasa (bg-blue-500, dst) pakai
 * class-nya langsung, tidak perlu lewat config ini.
 */

return [
    'laki' => '#3B82F6',
    'perempuan' => '#EC4899',
    'total' => '#0D9488',
    'positif' => '#10B981',
    'negatif' => '#EF4444',

    'background' => '#F8FAFC',
    'card' => '#FFFFFF',
    'header' => '#1E3A5F',
    'teks_utama' => '#1E293B',
    'teks_sekunder' => '#64748B',

    // 10 warna kategori — dipakai bergiliran untuk chart dengan banyak kategori
    // (agama, pekerjaan, pendidikan, dll).
    'kategori' => [
        '#3B82F6', '#EC4899', '#F59E0B', '#10B981', '#8B5CF6',
        '#EF4444', '#06B6D4', '#F97316', '#6366F1', '#84CC16',
    ],
];
