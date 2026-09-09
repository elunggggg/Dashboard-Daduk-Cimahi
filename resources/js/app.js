import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import axios from 'axios';
import { WARNA, KATEGORI, warnaKategori } from './chart-colors';

window.Alpine = Alpine;
window.Chart = Chart;
window.axios = axios;

// Terdaftar global tapi mati secara default — hanya tampil di grafik yang
// secara eksplisit mengaktifkan plugins.datalabels, supaya halaman lain yang
// belum disiapkan untuk label angka tidak ikut berubah.
Chart.register(ChartDataLabels);
Chart.defaults.set('plugins.datalabels', { display: false });

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// ── Utility ─────────────────────────────────────────────────────────────────

export function formatAngka(n) {
    return new Intl.NumberFormat('id-ID').format(n ?? 0);
}

export function formatPersen(n) {
    return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(n ?? 0) + '%';
}

// Tooltip standar seluruh dashboard: "Label: 523.841 (89,57%)". `getTotal`
// boleh angka langsung atau fungsi (dipanggil tiap kali tooltip dirender,
// berguna kalau totalnya berubah karena filter).
export function tooltipPersenLabel(getTotal) {
    return (ctx) => {
        const total = typeof getTotal === 'function' ? getTotal() : getTotal;
        const val = ctx.raw ?? 0;
        const label = ctx.label ?? ctx.dataset?.label ?? '';
        if (!total) return `${label}: ${formatAngka(val)}`;
        return `${label}: ${formatAngka(val)} (${formatPersen((val / total) * 100)})`;
    };
}

// ── Plugin: angka besar di tengah donut ─────────────────────────────────────
// Mati secara default (sama pola dengan datalabels) — aktifkan per chart lewat
// options.plugins.centerText = { display: true, text, subtext }.
export const centerTextPlugin = {
    id: 'centerText',
    afterDraw(chart) {
        const opts = chart.config.options?.plugins?.centerText;
        if (!opts || !opts.display) return;
        const { ctx, chartArea } = chart;
        if (!chartArea) return;
        const cx = (chartArea.left + chartArea.right) / 2;
        const cy = (chartArea.top + chartArea.bottom) / 2;
        ctx.save();
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.font = `800 ${opts.fontSize ?? 22}px Inter, system-ui, sans-serif`;
        ctx.fillStyle = opts.color ?? WARNA.teksUtama;
        ctx.fillText(opts.text ?? '', cx, opts.subtext ? cy - 10 : cy);
        if (opts.subtext) {
            ctx.font = '600 11px Inter, system-ui, sans-serif';
            ctx.fillStyle = WARNA.teksSekunder;
            ctx.fillText(opts.subtext, cx, cy + 14);
        }
        ctx.restore();
    },
};
Chart.register(centerTextPlugin);
Chart.defaults.set('plugins.centerText', { display: false });

// Diekspos ke window supaya <script> inline per halaman (classic script,
// bukan ES module — lihat aturan DOMContentLoaded di CLAUDE.md/memori) bisa
// memakai satu sumber warna & formatter yang sama, bukan menyalin sendiri.
window.DadukColors = WARNA;
window.DadukKategori = KATEGORI;
window.DadukWarnaKategori = warnaKategori;
window.formatAngka = formatAngka;
window.formatPersen = formatPersen;
window.tooltipPersenLabel = tooltipPersenLabel;

// ── Chart helpers (opsional, dipakai kalau sebuah halaman mau membangun
// chart standar tanpa menulis ulang seluruh options) ────────────────────────

export function makeBarChart(canvasId, labels, datasets, opts = {}) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return null;

    return new Chart(canvas, {
        type: 'bar',
        data: { labels, datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 8, usePointStyle: true, padding: 16, font: { size: 11 } } },
                tooltip: { callbacks: { label: tooltipPersenLabel(() => datasets[0]?.data?.reduce((a, b) => a + b, 0) ?? 0) } },
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                y: { beginAtZero: true, grid: { color: '#f0f0f0' }, ticks: { font: { size: 10 }, callback: (v) => v >= 1000 ? `${v / 1000}k` : v } },
            },
            ...opts,
        },
    });
}

export function makeLineChart(canvasId, labels, datasets, opts = {}) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return null;

    return new Chart(canvas, {
        type: 'line',
        data: { labels, datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 8, usePointStyle: true, padding: 16, font: { size: 11 } } },
                tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label ?? ctx.label}: ${formatAngka(ctx.raw)}` } },
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                y: { beginAtZero: false, grid: { color: '#f0f0f0' }, ticks: { font: { size: 10 }, callback: (v) => v >= 1000 ? `${v / 1000}k` : v } },
            },
            ...opts,
        },
    });
}

export function makeDoughnutChart(canvasId, labels, values, opts = {}) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return null;

    const total = values.reduce((a, b) => a + b, 0);

    return new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{ data: values, backgroundColor: KATEGORI, borderWidth: 0, hoverOffset: 4 }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 8, usePointStyle: true, padding: 12, font: { size: 10 } } },
                tooltip: { callbacks: { label: tooltipPersenLabel(total) } },
                centerText: { display: true, text: formatAngka(total) },
            },
            ...opts,
        },
    });
}

// Dijalankan PALING AKHIR: initTree Alpine bersifat sinkron, jadi begitu
// Alpine.start() dipanggil, semua `x-data` (mis. mobilitasApp()) + method
// init()-nya langsung dievaluasi. Kalau start di atas, `window.DadukColors`
// & `window.formatAngka` di bawah belum sempat di-assign → init() yang
// memakainya melempar TypeError, komponen gagal mount, dan blok
// `x-show="!loading" x-cloak` tidak pernah dibuka (data "tidak muncul").
Alpine.start();
