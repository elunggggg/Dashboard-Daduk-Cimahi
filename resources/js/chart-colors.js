// Satu-satunya sumber warna untuk Chart.js — HARUS sinkron dengan
// config/dashboard-colors.php (dua file terpisah karena Blade/PHP dan
// Chart.js/JS tidak bisa berbagi satu file konfigurasi langsung).
export const WARNA = {
    laki: '#3B82F6',
    perempuan: '#EC4899',
    total: '#0D9488',
    positif: '#10B981',
    negatif: '#EF4444',
    background: '#F8FAFC',
    card: '#FFFFFF',
    header: '#1E3A5F',
    teksUtama: '#1E293B',
    teksSekunder: '#64748B',
};

// 10 warna kategori — dipakai bergiliran (modulo panjang array) untuk chart
// dengan banyak kategori (agama, pekerjaan, pendidikan, golongan darah, dll).
export const KATEGORI = [
    '#3B82F6', '#EC4899', '#F59E0B', '#10B981', '#8B5CF6',
    '#EF4444', '#06B6D4', '#F97316', '#6366F1', '#84CC16',
];

export function warnaKategori(index) {
    return KATEGORI[index % KATEGORI.length];
}
