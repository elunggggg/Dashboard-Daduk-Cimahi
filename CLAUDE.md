# Dashboard Data Agregat Penduduk Kota Cimahi

## Tentang Project
Sistem dashboard berbasis web untuk menyajikan data kependudukan agregat Kota Cimahi. Dibangun untuk Dinas Kependudukan dan Pencatatan Sipil (Disdukcapil) Kota Cimahi, Bidang PIAK. Sistem ini akan benar-benar dipakai oleh instansi.

## Tim Pengembang
- Faza Mukti Muhammad (2350231007)
- Khaerul Mutakin (2350231024)
- Program Studi Sistem Informasi, Fakultas Sains dan Informatika, UNJANI

## Tech Stack (jangan diganti)
- Backend: Laravel 10 (PHP 8.3)
- Frontend: Blade + Alpine.js (TANPA Livewire)
- Styling: Tailwind CSS
- Database: MySQL (star schema)
- Grafik: Chart.js
- Peta: Leaflet.js + GeoJSON
- Import Excel: maatwebsite/excel
- Export PDF: barryvdh/laravel-dompdf
- Auth: Laravel Breeze
- Deployment: Docker container

## Aktor
1. Publik — akses dashboard tanpa login. Bisa lihat semua data, filter, bandingkan periode, ekspor Excel/PDF.
2. Petugas — login required. Bisa import data Excel, kelola wilayah, metadata, pengguna, audit log, backup.

## Struktur Database (Star Schema)
- data_agregat (fact table): id, id_wilayah, id_waktu, id_kategori, jumlah, id_import, created_by, updated_by
- dim_wilayah: id, kode_kemendagri, kode_kecamatan, nama_kelurahan, nama_kecamatan, luas_wilayah_km2
- dim_waktu: id, semester, tahun
- dim_kategori: id, jenis_indikator, label
- alias_wilayah: id, id_wilayah, nama_alias
- users: id, nama, email, password, role
- import_excels: id, nama_file, path_file, semester, tahun, status_import, jumlah_baris_masuk, catatan_hasil, tanggal_import, diimpor_oleh
- konfigurasi_imports: id, nama_profil, nama_sheet, jenis_indikator, label, teks_header, offset_kolom, teks_header_wilayah, aktif
- audit_logs: id, user_id, aksi, tabel_terkait, data_sebelum, data_sesudah, ip_address, waktu
- metadata: id, id_kategori, nama_indikator, definisi, satuan, sumber_data, periode_data
- backups: id, tanggal_backup, lokasi_file, ukuran_file, status_backup, dijalankan_oleh

## Wilayah Kota Cimahi
3 kecamatan, 15 kelurahan:
- Cimahi Selatan (32.77.01): Melong, Cibeureum, Utama, Leuwigajah, Cibeber
- Cimahi Tengah (32.77.02): Baros, Cigugur Tengah, Karangmekar, Setiamanah, Padasuka, Cimahi
- Cimahi Utara (32.77.03): Pasirkaliki, Cibabat, Citeureup, Cipageran

## File Data Sumber
- storage/app/data-sumber/*.xlsx — file Excel DKB dari Disdukcapil
- JANGAN commit ke git — sudah di .gitignore
- Format: 60+ sheet, header bertingkat, baris subtotal kecamatan, tabel pivot kedua di beberapa sheet

## Aturan Import Excel
- Cari kolom lewat TEKS HEADER, bukan indeks hardcoded
- Cocokkan wilayah lewat NAMA, bukan posisi baris
- Berhenti setelah 15 kelurahan unik ditemukan
- Validasi header sekali per sheet, bukan per baris
- Sel error (#REF! dll) diperlakukan sebagai NULL, jangan crash
- Deteksi periode: scan judul sheet → fallback nama file → fallback input manual

## Tampilan Beranda
Tampilan beranda sudah dibuat sendiri oleh tim. Jangan ubah layout yang sudah ada. Perbaiki dan lengkapi saja yang kurang. Ikuti pola dan gaya visual yang sudah ada di Blade view.

## Kebutuhan
- 61 KF (KF-01 s.d. KF-61) — lihat dokumen SRG di docs/
- 40 KNF (KNF-01 s.d. KNF-40) — lihat dokumen SRG di docs/

## Dokumen Acuan (di folder docs/)
- SRG, BRD, SRS
- Diagram UML: menyusul (belum dibuat)

## Default Behavior
- Dashboard selalu tampilkan semester TERBARU dari database
- Dropdown filter hanya tampilkan periode yang ada datanya
- Cache query dashboard, flush saat import berhasil
- Setiap perubahan data tercatat di audit log
- Angka pakai format ribuan dengan titik (584.820)
- Persentase pakai 2 desimal (49.95%)

## Progress Terakhir
(Belum ada — project baru dimulai)
