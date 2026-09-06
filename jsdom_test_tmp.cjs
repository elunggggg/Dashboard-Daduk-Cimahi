const { JSDOM } = require('./node_modules/jsdom');
const fs = require('fs');

const html = fs.readFileSync('./mini_tmp.html', 'utf8');

(async () => {
  const dom = new JSDOM(html, {
    runScripts: 'outside-only',
    resources: undefined,
    url: 'http://127.0.0.1/',
    pretendToBeVisual: true, // needed for canvas layout/getBoundingClientRect etc
  });
  const { window } = dom;

  const calls = [];
  const DATASETS = {
    jenis_kelamin: { labels: ['Laki-laki', 'Perempuan'], nilai1: [1000, 900], nilai2: [1100, 950] },
    pekerjaan: {
      labels: ['Petani', 'Nelayan', 'Pedagang', 'PNS', 'Buruh', 'Wiraswasta', 'Guru', 'Dokter', 'TNI/Polri', 'Pelajar', 'Lainnya'],
      nilai1: [10, 20, 30, 5, 15, 25, 8, 3, 4, 40, 12],
      nilai2: [12, 22, 33, 6, 18, 28, 9, 4, 5, 42, 14],
    },
  };
  window.fetch = async (url) => {
    calls.push(url);
    const u = new URL(url, 'http://127.0.0.1/');
    const indikator = u.searchParams.get('jenis_indikator');
    const d = DATASETS[indikator] ?? { labels: [], nilai1: [], nilai2: [] };
    return { json: async () => ({ ...d, periode1: 'S1 2025', periode2: 'S2 2025' }) };
  };

  global.window = window;
  global.document = window.document;
  global.MutationObserver = window.MutationObserver;
  global.Element = window.Element;
  global.Node = window.Node;
  global.HTMLElement = window.HTMLElement;
  global.HTMLCanvasElement = window.HTMLCanvasElement;
  global.customElements = window.customElements;
  global.Event = window.Event;
  global.CustomEvent = window.CustomEvent;
  global.ShadowRoot = window.ShadowRoot;
  global.DocumentFragment = window.DocumentFragment;
  global.navigator = window.navigator;
  global.requestAnimationFrame = (cb) => setTimeout(cb, 16);
  global.cancelAnimationFrame = (id) => clearTimeout(id);
  window.requestAnimationFrame = global.requestAnimationFrame;
  window.cancelAnimationFrame = global.cancelAnimationFrame;

  // REAL Chart.js (bukan stub). Plugin datalabels DILEWATI di test ini saja
  // (masalah loader ESM/CJS murni terbatas pada skrip verifikasi Node, TIDAK
  // relevan dengan aplikasi sungguhan yang memuatnya lewat Vite) — Chart.js
  // aman mengabaikan namespace opsi plugin yang pluginnya tidak terdaftar.
  const { Chart, registerables } = require('./node_modules/chart.js/dist/chart.cjs');
  Chart.register(...registerables);
  Chart.defaults.set('plugins.datalabels', { display: false });
  window.Chart = Chart;

  const AlpineModule = require('./node_modules/alpinejs/dist/module.cjs.js');
  const Alpine = AlpineModule.default || AlpineModule;
  window.Alpine = Alpine;

  const scripts = [...dom.window.document.querySelectorAll('script:not([src]):not([type="module"])')];
  for (const s of scripts) window.eval(s.textContent);

  global.dashboardApp = window.dashboardApp;
  global.perbandinganBox = window.perbandinganBox;
  global.fmt = window.fmt;

  const uncaughtErrors = [];
  window.addEventListener('error', (e) => uncaughtErrors.push(e.error || e.message));
  window.addEventListener('unhandledrejection', (e) => uncaughtErrors.push(e.reason));
  process.on('unhandledRejection', (reason) => uncaughtErrors.push(reason));

  Alpine.start();
  await new Promise((r) => setTimeout(r, 400));

  console.log('=== SETELAH LOAD AWAL (kategori: jenis_kelamin, REAL Chart.js) ===');
  console.log('fetch calls:', calls.length);
  console.log('uncaught errors:', uncaughtErrors.length, uncaughtErrors.map((e) => String(e && e.message || e)));

  const selIndikator = window.document.querySelector('select[x-model="kategori"]');
  if (!selIndikator) { console.log('SELECT TIDAK DITEMUKAN'); process.exit(1); }

  console.log('\n=== GANTI KE "pekerjaan" (11 kategori, jauh beda dari 2) ===');
  selIndikator.value = 'pekerjaan';
  selIndikator.dispatchEvent(new window.Event('change', { bubbles: true }));
  await new Promise((r) => setTimeout(r, 400));
  console.log('fetch calls total:', calls.length);
  console.log('uncaught errors setelah ganti:', uncaughtErrors.length, uncaughtErrors.map((e) => String(e && e.message || e)));

  console.log('\n=== GANTI BALIK KE "jenis_kelamin" (11 -> 2 kategori) ===');
  selIndikator.value = 'jenis_kelamin';
  selIndikator.dispatchEvent(new window.Event('change', { bubbles: true }));
  await new Promise((r) => setTimeout(r, 400));
  console.log('fetch calls total:', calls.length);
  console.log('uncaught errors setelah ganti balik:', uncaughtErrors.length, uncaughtErrors.map((e) => String(e && e.message || e)));

  const rows = window.document.querySelectorAll('.divide-y > div');
  console.log('\nJumlah baris tabel akhir (harus 2, sesuai jenis_kelamin):', rows.length);
  if (rows[0]) console.log('Baris pertama:', rows[0].textContent.replace(/\s+/g, ' ').trim());

  console.log('\n=== HASIL AKHIR:', uncaughtErrors.length === 0 ? 'BERSIH, TIDAK ADA ERROR' : 'MASIH ADA ERROR', '===');
  process.exit(uncaughtErrors.length === 0 ? 0 : 1);
})().catch((e) => { console.error('FATAL:', e); process.exit(1); });
