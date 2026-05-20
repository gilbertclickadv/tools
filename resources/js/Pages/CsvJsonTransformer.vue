<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

// ─── State ────────────────────────────────────────────────────────────
const mode            = ref('csv-to-json');
const inputText       = ref('');
const outputText      = ref('');
const errorMessage    = ref('');
const delimiter       = ref('auto');
const firstRowHeaders = ref(true);
const indentSize      = ref('2');
const copied          = ref(false);

const linesCount      = ref(0);
const sizeBytes       = ref(0);
const recordsCount    = ref(0);
const processingTime  = ref('—');

const toastVisible    = ref(false);
const toastMessage    = ref('');
const toastType       = ref('success');

const fileInputRef    = ref(null);

// ─── Toast ────────────────────────────────────────────────────────────
const showToast = (msg, type = 'success') => {
  toastMessage.value = msg;
  toastType.value    = type;
  toastVisible.value = true;
  setTimeout(() => { toastVisible.value = false; }, 2600);
};

// ─── CSV Parser ───────────────────────────────────────────────────────
const parseCSV = (text, delim, withHeaders) => {
  if (!text.trim()) return [];
  let d = delim;
  if (d === 'auto') {
    const c = (text.match(/,/g) || []).length;
    const s = (text.match(/;/g) || []).length;
    const t = (text.match(/\t/g) || []).length;
    d = t > c && t > s ? '\t' : s > c ? ';' : ',';
  }
  const lines = []; let row = [], inQ = false, cell = '';
  for (let i = 0; i < text.length; i++) {
    const ch = text[i], nx = text[i + 1];
    if (ch === '"') {
      if (inQ && nx === '"') { cell += '"'; i++; } else inQ = !inQ;
    } else if (ch === d && !inQ) {
      row.push(cell.trim()); cell = '';
    } else if ((ch === '\r' || ch === '\n') && !inQ) {
      if (ch === '\r' && nx === '\n') i++;
      row.push(cell.trim());
      if (row.some(v => v !== '')) lines.push(row);
      row = []; cell = '';
    } else cell += ch;
  }
  if (cell !== '' || row.length > 0) { row.push(cell.trim()); if (row.some(v => v !== '')) lines.push(row); }
  if (!lines.length) return [];
  if (withHeaders) {
    const headers = lines[0].map(h => h.replace(/^"|"$/g, '').trim() || 'col');
    return lines.slice(1).map(vals => {
      const obj = {};
      headers.forEach((h, i) => {
        let v = vals[i] ?? '';
        if (v.toLowerCase() === 'true') v = true;
        else if (v.toLowerCase() === 'false') v = false;
        else if (v.toLowerCase() === 'null') v = null;
        else if (!isNaN(v) && v.trim() !== '') v = Number(v);
        obj[h] = v;
      });
      return obj;
    });
  }
  return lines;
};

// ─── JSON → CSV ───────────────────────────────────────────────────────
const jsonToCsv = (arr, delim) => {
  if (!Array.isArray(arr) || !arr.length) return '';
  const d   = delim === 'auto' ? ',' : delim;
  const esc = v => {
    const s = String(v ?? '');
    return (s.includes(d) || s.includes('"') || s.includes('\n'))
      ? `"${s.replace(/"/g, '""')}"` : s;
  };
  const keys = [...new Set(arr.flatMap(r => typeof r === 'object' && r ? Object.keys(r) : []))];
  if (!keys.length) return arr.map(r => esc(r)).join('\n');
  return [
    keys.map(esc).join(d),
    ...arr.map(r => keys.map(k => {
      const v = r[k];
      return esc(typeof v === 'object' && v ? JSON.stringify(v) : v);
    }).join(d)),
  ].join('\n');
};

// ─── Run conversion ───────────────────────────────────────────────────
const runConversion = () => {
  errorMessage.value = '';
  outputText.value   = '';
  const raw = inputText.value;
  if (!raw.trim()) {
    linesCount.value = 0; sizeBytes.value = 0;
    recordsCount.value = 0; processingTime.value = '—';
    return;
  }
  localStorage.setItem(`fm_csvjson_${mode.value}`, raw);
  const t0 = performance.now();
  try {
    linesCount.value = raw.split(/\r?\n/).length;
    sizeBytes.value  = new Blob([raw]).size;
    if (mode.value === 'csv-to-json') {
      const parsed       = parseCSV(raw, delimiter.value, firstRowHeaders.value);
      recordsCount.value = parsed.length;
      const sp           = indentSize.value === 'compact' ? undefined
        : indentSize.value === 'tab' ? '\t'
        : parseInt(indentSize.value);
      outputText.value = JSON.stringify(parsed, null, sp);
    } else {
      const parsed       = JSON.parse(raw);
      const arr          = Array.isArray(parsed) ? parsed : [parsed];
      recordsCount.value = arr.length;
      outputText.value   = jsonToCsv(arr, delimiter.value);
    }
    processingTime.value = `${(performance.now() - t0).toFixed(2)}ms`;
  } catch (e) {
    errorMessage.value   = e.message || 'Parse error — check your input format.';
    processingTime.value = '—';
  }
};

watch([inputText, mode, delimiter, firstRowHeaders, indentSize], runConversion);

// ─── Samples ──────────────────────────────────────────────────────────
const SAMPLE_CSV = `id,name,email,role,active,score
1,Alice Vance,alice@dev.io,Admin,true,98.5
2,Bob Miller,bob@dev.io,Engineer,true,87
3,Charlie Rose,charlie@dev.io,Designer,false,72
4,Diana Stark,diana@dev.io,Security,true,95
5,Evan Green,evan@dev.io,PM,true,91.2`;

const SAMPLE_JSON = JSON.stringify([
  { id: 1, name: 'Alice Vance',  email: 'alice@dev.io',   role: 'Admin',    active: true,  score: 98.5 },
  { id: 2, name: 'Bob Miller',   email: 'bob@dev.io',     role: 'Engineer', active: true,  score: 87   },
  { id: 3, name: 'Charlie Rose', email: 'charlie@dev.io', role: 'Designer', active: false, score: 72   },
  { id: 4, name: 'Diana Stark',  email: 'diana@dev.io',   role: 'Security', active: true,  score: 95   },
], null, 2);

const loadSample = () => {
  inputText.value = mode.value === 'csv-to-json' ? SAMPLE_CSV : SAMPLE_JSON;
  showToast('Sample data loaded');
};
const clearAll = () => {
  inputText.value = ''; outputText.value = ''; errorMessage.value = '';
  localStorage.removeItem(`fm_csvjson_${mode.value}`);
  showToast('Workspace cleared');
};
const copyOutput = async () => {
  if (!outputText.value) return;
  await navigator.clipboard.writeText(outputText.value);
  copied.value = true; showToast('Copied to clipboard');
  setTimeout(() => { copied.value = false; }, 2000);
};
const downloadOutput = () => {
  if (!outputText.value) return;
  const ext  = mode.value === 'csv-to-json' ? 'json' : 'csv';
  const mime = mode.value === 'csv-to-json' ? 'application/json' : 'text/csv';
  const url  = URL.createObjectURL(new Blob([outputText.value], { type: mime }));
  const a    = Object.assign(document.createElement('a'), { href: url, download: `fluxmedia_${Date.now()}.${ext}` });
  document.body.appendChild(a); a.click(); document.body.removeChild(a);
  URL.revokeObjectURL(url);
  showToast(`Downloaded .${ext} file`);
};
const swapSides = () => {
  if (!outputText.value || errorMessage.value) return;
  const temp      = outputText.value;
  mode.value      = mode.value === 'csv-to-json' ? 'json-to-csv' : 'csv-to-json';
  inputText.value = temp;
  showToast('Input & output swapped');
};
const handleFile = e => {
  const file = e.target.files[0]; if (!file) return;
  const reader = new FileReader();
  reader.onload = ev => { inputText.value = ev.target.result; showToast(`Loaded ${file.name}`); };
  reader.readAsText(file);
  e.target.value = '';
};
const fmtSize = b => {
  if (!b) return '0 B';
  const k = 1024, u = ['B', 'KB', 'MB'];
  const i = Math.floor(Math.log(b) / Math.log(k));
  return `${(b / Math.pow(k, i)).toFixed(2)} ${u[i]}`;
};

// ─── JSON syntax highlight ────────────────────────────────────────────
const highlightedOutput = computed(() => {
  if (!outputText.value || mode.value === 'json-to-csv') return null;
  return outputText.value
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(
      /("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g,
      match => {
        if (/^"/.test(match)) return /:$/.test(match) ? `<span class="jk">${match}</span>` : `<span class="js">${match}</span>`;
        if (/true|false/.test(match)) return `<span class="jb">${match}</span>`;
        if (/null/.test(match)) return `<span class="jn">${match}</span>`;
        return `<span class="jnum">${match}</span>`;
      },
    );
});

// ─── Lifecycle ────────────────────────────────────────────────────────
let ldScript = null;
onMounted(() => {
  ldScript = document.createElement('script');
  ldScript.type = 'application/ld+json';
  ldScript.textContent = JSON.stringify({
    '@context': 'https://schema.org', '@type': 'WebApplication',
    name: 'FluxMedia CSV ↔ JSON Converter',
    url: 'https://fluxmedia.space/tools/csv-json',
    description: 'Free browser-based CSV to JSON and JSON to CSV converter.',
    applicationCategory: 'DeveloperApplication', operatingSystem: 'Web',
    offers: { '@type': 'Offer', price: '0', priceCurrency: 'USD' },
  });
  document.head.appendChild(ldScript);
  const saved = localStorage.getItem(`fm_csvjson_${mode.value}`);
  if (saved) inputText.value = saved;
});
onUnmounted(() => ldScript?.remove());
</script>

<template>
  <PublicLayout>
    <Head>
      <title>CSV ↔ JSON Converter — Free, Private & Instant | FluxMedia</title>
      <meta name="description" content="Convert CSV to JSON or JSON to CSV instantly in your browser. 100% private — no data leaves your device." />
      <meta name="robots" content="index, follow" />
      <link rel="canonical" href="https://fluxmedia.space/tools/csv-json" />
      <meta property="og:type" content="website" />
      <meta property="og:title" content="CSV ↔ JSON Converter — Free & Private | FluxMedia" />
      <meta property="og:description" content="Instant browser-side CSV↔JSON conversion. No uploads, no accounts, no limits." />
      <meta property="og:image" content="https://fluxmedia.space/assets/images/fluxmedia_og.webp" />
      <meta property="og:url" content="https://fluxmedia.space/tools/csv-json" />
      <meta property="og:site_name" content="FluxMedia" />
      <meta name="twitter:card" content="summary_large_image" />
      <meta name="twitter:title" content="CSV ↔ JSON Converter — FluxMedia" />
      <meta name="twitter:image" content="https://fluxmedia.space/assets/images/fluxmedia_og.webp" />
    </Head>

    <!-- Background -->
    <div class="fixed inset-0 -z-10 bg-[#07090f]" aria-hidden="true">
      <div class="absolute inset-0 opacity-[0.025]"
           style="background-image:repeating-linear-gradient(0deg,transparent,transparent 2px,rgba(255,255,255,0.1) 2px,rgba(255,255,255,0.1) 3px)"></div>
      <div class="absolute inset-0 opacity-[0.04]"
           style="background-image:linear-gradient(rgba(99,102,241,0.4) 1px,transparent 1px),linear-gradient(90deg,rgba(99,102,241,0.4) 1px,transparent 1px);background-size:48px 48px"></div>
      <div class="absolute top-0 left-0 w-[600px] h-[400px] bg-indigo-600/10 blur-[160px] rounded-full -translate-x-1/3 -translate-y-1/3"></div>
      <div class="absolute bottom-0 right-0 w-[500px] h-[400px] bg-violet-600/[0.08] blur-[140px] rounded-full translate-x-1/3 translate-y-1/3"></div>
    </div>

    <div class="relative z-10 max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 flex flex-col gap-5">

      <!-- ░░ HEADER ░░ -->
      <header class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">

        <!-- Title block -->
        <div>
          <div class="flex items-center gap-2 mb-2">
            <span class="font-mono text-[11px] text-white/30 tracking-widest uppercase">fluxmedia / tools /</span>
            <span class="font-mono text-[11px] text-indigo-400 font-semibold tracking-widest uppercase">csv-json</span>
          </div>
          <h1 class="font-mono font-black text-white tracking-tight leading-none flex flex-wrap items-baseline gap-2 text-2xl sm:text-3xl">
            CSV
            <span class="text-indigo-400">↔</span>
            JSON
            <span class="font-light text-white/40 text-xl sm:text-2xl">Transformer</span>
          </h1>
          <p class="font-mono text-[11px] text-white/30 mt-2 tracking-wider">
            browser-native · zero uploads · instant · private
          </p>
        </div>

        <!-- Mode toggle + actions -->
        <div class="flex flex-wrap items-center gap-3">

          <!-- Mode toggle -->
          <div class="flex items-center bg-white/[0.04] border border-white/[0.08] rounded-xl p-1 gap-1">
            <button
              @click="mode = 'csv-to-json'"
              :class="[
                'px-4 py-2.5 rounded-lg font-mono text-[11px] font-bold uppercase tracking-wider transition-all duration-200',
                mode === 'csv-to-json'
                  ? 'bg-indigo-600/25 text-white border border-indigo-500/40 shadow shadow-indigo-600/10'
                  : 'text-white/40 hover:text-white/70 hover:bg-white/[0.04]'
              ]"
            >CSV → JSON</button>
            <button
              @click="mode = 'json-to-csv'"
              :class="[
                'px-4 py-2.5 rounded-lg font-mono text-[11px] font-bold uppercase tracking-wider transition-all duration-200',
                mode === 'json-to-csv'
                  ? 'bg-indigo-600/25 text-white border border-indigo-500/40 shadow shadow-indigo-600/10'
                  : 'text-white/40 hover:text-white/70 hover:bg-white/[0.04]'
              ]"
            >JSON → CSV</button>
          </div>

          <!-- Action buttons -->
          <label class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white/[0.05] border border-white/[0.1] text-white/60 hover:text-white hover:bg-white/[0.09] hover:border-white/20 font-mono text-[11px] font-semibold uppercase tracking-wider transition-all cursor-pointer active:scale-95">
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
              <path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg>
            Upload
            <input type="file" accept=".csv,.json,.txt" @change="handleFile" class="sr-only" ref="fileInputRef" />
          </label>

          <button
            @click="loadSample"
            class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white/[0.05] border border-white/[0.1] text-white/60 hover:text-white hover:bg-white/[0.09] hover:border-white/20 font-mono text-[11px] font-semibold uppercase tracking-wider transition-all active:scale-95"
          >
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
              <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            Sample
          </button>

          <button
            @click="clearAll"
            class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-red-500/[0.07] border border-red-500/25 text-red-300/80 hover:text-red-200 hover:bg-red-500/15 hover:border-red-500/40 font-mono text-[11px] font-semibold uppercase tracking-wider transition-all active:scale-95"
          >
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
              <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            Clear
          </button>
        </div>
      </header>

      <!-- ░░ CONFIG + STATS BAR ░░ -->
      <div class="bg-[#0d111c] border border-white/[0.07] rounded-2xl overflow-hidden">

        <!-- Config row -->
        <div class="flex flex-wrap items-stretch divide-x divide-white/[0.06]">

          <!-- Delimiter -->
          <div class="flex flex-col gap-1.5 px-5 py-4 min-w-[160px]">
            <label for="sel-delimiter" class="font-mono text-[10px] font-semibold uppercase tracking-widest text-white/30">
              Delimiter
            </label>
            <div class="relative flex items-center">
              <select
                id="sel-delimiter"
                v-model="delimiter"
                class="appearance-none bg-transparent border-0 font-mono text-sm font-semibold text-white/85 cursor-pointer focus:outline-none focus:ring-0 pr-6 w-full"
              >
                <option value="auto">auto-detect</option>
                <option value=",">comma  ,</option>
                <option value=";">semicolon  ;</option>
                <option value="&#9;">tab  \t</option>
              </select>
              <svg class="absolute right-0 w-3.5 h-3.5 text-white/30 pointer-events-none shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="M6 9l6 6 6-6"/>
              </svg>
            </div>
          </div>

          <!-- Indent (csv-to-json only) -->
          <div v-if="mode === 'csv-to-json'" class="flex flex-col gap-1.5 px-5 py-4 min-w-[160px]">
            <label for="sel-indent" class="font-mono text-[10px] font-semibold uppercase tracking-widest text-white/30">
              JSON indent
            </label>
            <div class="relative flex items-center">
              <select
                id="sel-indent"
                v-model="indentSize"
                class="appearance-none bg-transparent border-0 font-mono text-sm font-semibold text-white/85 cursor-pointer focus:outline-none focus:ring-0 pr-6 w-full"
              >
                <option value="2">2 spaces</option>
                <option value="4">4 spaces</option>
                <option value="tab">tab</option>
                <option value="compact">compact</option>
              </select>
              <svg class="absolute right-0 w-3.5 h-3.5 text-white/30 pointer-events-none shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="M6 9l6 6 6-6"/>
              </svg>
            </div>
          </div>

          <!-- Headers toggle (csv-to-json only) -->
          <div v-if="mode === 'csv-to-json'" class="flex flex-col gap-1.5 px-5 py-4">
            <span class="font-mono text-[10px] font-semibold uppercase tracking-widest text-white/30" id="hdr-toggle-label">
              First row = headers
            </span>
            <div class="flex items-center gap-3 h-[22px]">
              <button
                @click="firstRowHeaders = !firstRowHeaders"
                :class="[
                  'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none',
                  firstRowHeaders ? 'bg-indigo-600' : 'bg-white/15'
                ]"
                role="switch"
                :aria-checked="firstRowHeaders"
                aria-labelledby="hdr-toggle-label"
              >
                <span
                  :class="[
                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200',
                    firstRowHeaders ? 'translate-x-5' : 'translate-x-0'
                  ]"
                ></span>
              </button>
              <span class="font-mono text-sm font-semibold" :class="firstRowHeaders ? 'text-white/75' : 'text-white/30'">
                {{ firstRowHeaders ? 'on' : 'off' }}
              </span>
            </div>
          </div>

          <!-- Stats strip — pushed to the right -->
          <div class="ml-auto flex divide-x divide-white/[0.06]">
            <div
              v-for="s in [
                { label: 'size',    val: fmtSize(sizeBytes),    accent: false },
                { label: 'lines',   val: linesCount  || '—',    accent: false },
                { label: 'records', val: recordsCount || '—',   accent: false },
                { label: 'speed',   val: processingTime,        accent: true  },
              ]"
              :key="s.label"
              class="flex flex-col items-center justify-center gap-1.5 px-5 py-4 min-w-[80px]"
            >
              <span class="font-mono text-[9px] font-semibold uppercase tracking-widest text-white/25 leading-none">
                {{ s.label }}
              </span>
              <span
                :class="[
                  'font-mono text-sm font-bold leading-none',
                  s.accent ? 'text-emerald-400' : 'text-white/80'
                ]"
              >{{ s.val }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ░░ EDITOR PANES ░░ -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-5" style="min-height:560px">

        <!-- INPUT PANE -->
        <div class="flex flex-col rounded-2xl border border-white/[0.08] bg-[#0b0e18] overflow-hidden shadow-xl shadow-black/30" style="min-height:420px">

          <!-- Titlebar -->
          <div class="flex items-center gap-2.5 px-4 py-3 bg-[#090c14] border-b border-white/[0.06] shrink-0">
            <div class="flex items-center gap-1.5">
              <span class="w-3 h-3 rounded-full bg-rose-500/75"></span>
              <span class="w-3 h-3 rounded-full bg-amber-400/75"></span>
              <span class="w-3 h-3 rounded-full bg-emerald-500/75"></span>
            </div>
            <span class="font-mono text-xs text-white/40 tracking-wide ml-1 flex-1">
              {{ mode === 'csv-to-json' ? 'input.csv' : 'input.json' }}
            </span>
            <span v-if="inputText" class="font-mono text-[11px] text-white/25">
              {{ linesCount }} lines
            </span>
          </div>

          <!-- Textarea -->
          <div class="flex-1 relative min-h-0">
            <textarea
              v-model="inputText"
              spellcheck="false"
              class="absolute inset-0 w-full h-full bg-transparent border-0 resize-none focus:outline-none focus:ring-0 font-mono text-sm text-gray-200 leading-relaxed p-5 placeholder-white/20"
              style="tab-size:4"
              :placeholder="mode === 'csv-to-json'
                ? 'id,name,email\n1,Alice,alice@example.com\n2,Bob,bob@example.com'
                : '[{\n  &quot;id&quot;: 1,\n  &quot;name&quot;: &quot;Alice&quot;\n}]'"
              aria-label="Input data"
            ></textarea>
          </div>
        </div>

        <!-- OUTPUT PANE -->
        <div class="flex flex-col rounded-2xl border border-white/[0.08] bg-[#0b0e18] overflow-hidden shadow-xl shadow-black/30" style="min-height:420px">

          <!-- Titlebar -->
          <div class="flex items-center gap-2.5 px-4 py-3 bg-[#090c14] border-b border-white/[0.06] shrink-0 flex-wrap gap-y-2">
            <div class="flex items-center gap-1.5">
              <span
                class="w-3 h-3 rounded-full"
                :class="errorMessage ? 'bg-rose-500' : 'bg-indigo-500/80 animate-pulse'"
              ></span>
              <span class="w-3 h-3 rounded-full bg-white/10"></span>
              <span class="w-3 h-3 rounded-full bg-white/10"></span>
            </div>
            <span
              class="font-mono text-xs tracking-wide ml-1"
              :class="errorMessage ? 'text-rose-400' : 'text-white/40'"
            >
              {{ errorMessage ? 'error' : mode === 'csv-to-json' ? 'output.json' : 'output.csv' }}
            </span>

            <!-- Output actions -->
            <div v-if="outputText && !errorMessage" class="ml-auto flex items-center gap-1.5 flex-wrap">
              <button
                @click="swapSides"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-mono text-[11px] font-semibold text-white/40 hover:text-white hover:bg-white/[0.06] border border-transparent hover:border-white/[0.08] transition-all active:scale-95"
              >
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <path d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                </svg>
                swap
              </button>

              <button
                @click="copyOutput"
                :class="[
                  'flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-mono text-[11px] font-semibold border transition-all active:scale-95',
                  copied
                    ? 'text-emerald-400 border-emerald-500/25 hover:bg-emerald-500/[0.07]'
                    : 'text-white/40 border-transparent hover:text-white hover:bg-white/[0.06] hover:border-white/[0.08]'
                ]"
              >
                <svg v-if="copied" class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <path d="M5 13l4 4L19 7"/>
                </svg>
                <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <path d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
                {{ copied ? 'copied' : 'copy' }}
              </button>

              <button
                @click="downloadOutput"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-mono text-[11px] font-semibold text-indigo-400 border border-indigo-500/20 hover:bg-indigo-500/10 hover:border-indigo-500/35 hover:text-indigo-300 transition-all active:scale-95"
              >
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                download
              </button>
            </div>
          </div>

          <!-- Output body -->
          <div class="flex-1 relative min-h-0">

            <!-- Error state -->
            <div v-if="errorMessage" class="absolute inset-0 overflow-auto p-5 flex flex-col gap-3">
              <div class="flex items-center gap-2 font-mono text-[11px] font-bold uppercase tracking-wider text-rose-400">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Parse Error
              </div>
              <pre class="font-mono text-xs text-rose-300/85 whitespace-pre-wrap leading-relaxed bg-rose-500/[0.06] border border-rose-500/15 rounded-xl p-4">{{ errorMessage }}</pre>
              <p class="font-mono text-xs text-white/30 leading-relaxed">
                <span class="text-white/45 font-semibold">Tip:</span>
                {{ mode === 'csv-to-json'
                  ? 'Values with commas must be quoted, e.g. "Smith, John"'
                  : 'Ensure your JSON is a valid array [] or object {}.' }}
              </p>
            </div>

            <!-- Empty state -->
            <div v-else-if="!outputText" class="absolute inset-0 flex flex-col items-center justify-center gap-3 p-8">
              <div class="w-14 h-14 rounded-2xl border border-white/[0.07] bg-white/[0.025] flex items-center justify-center">
                <svg class="w-7 h-7 text-white/20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <path d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
              </div>
              <p class="font-mono text-xs text-white/25 text-center leading-relaxed">
                Output renders here<br>
                <span class="text-white/15">← paste data on the left</span>
              </p>
            </div>

            <!-- Highlighted JSON -->
            <div
              v-else-if="mode === 'csv-to-json' && highlightedOutput"
              class="absolute inset-0 overflow-auto output-scroll"
            >
              <pre class="font-mono text-sm text-gray-200 leading-relaxed p-5 whitespace-pre overflow-visible m-0" style="min-width:max-content" v-html="highlightedOutput"></pre>
            </div>

            <!-- Plain CSV output -->
            <textarea
              v-else
              readonly
              :value="outputText"
              class="absolute inset-0 w-full h-full bg-transparent border-0 resize-none focus:outline-none focus:ring-0 font-mono text-sm text-emerald-300/90 leading-relaxed p-5"
              style="tab-size:4"
              aria-label="Transformed output"
            ></textarea>

          </div>
        </div>
      </div>

      <!-- ░░ FOOTER ░░ -->
      <footer class="flex flex-wrap items-center justify-between gap-3 border-t border-white/[0.05] pt-5">
        <p class="font-mono text-[11px] text-white/25 leading-relaxed">
          All processing runs locally in your browser tab. No data is transmitted to any server.
        </p>
        <div class="flex items-center gap-2.5">
          <span class="font-mono text-[11px] text-white/20 uppercase tracking-widest">FluxMedia · csv-json v2.0</span>
          <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
        </div>
      </footer>

    </div>

    <!-- ░░ TOAST ░░ -->
    <Transition name="toast">
      <div
        v-if="toastVisible"
        :class="[
          'fixed bottom-6 right-6 z-50 flex items-center gap-3 px-4 py-3 rounded-xl border font-mono text-[11px] font-bold shadow-2xl backdrop-blur-md',
          toastType === 'success'
            ? 'bg-[#060e06]/95 border-emerald-500/30 text-emerald-300'
            : 'bg-[#0e0606]/95 border-rose-500/30 text-rose-300'
        ]"
        role="status"
        aria-live="polite"
      >
        <span
          class="w-1.5 h-1.5 rounded-full animate-ping"
          :class="toastType === 'success' ? 'bg-emerald-400' : 'bg-rose-400'"
        ></span>
        {{ toastMessage }}
      </div>
    </Transition>

  </PublicLayout>
</template>

<style scoped>
/* select option bg — can't be done via Tailwind */
select option { background: #0d111c; color: #e5e7eb; }

/* custom scrollbar on both panes */
textarea::-webkit-scrollbar,
.output-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
textarea::-webkit-scrollbar-track,
.output-scroll::-webkit-scrollbar-track { background: transparent; }
textarea::-webkit-scrollbar-thumb,
.output-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 9999px; }
textarea::-webkit-scrollbar-thumb:hover,
.output-scroll::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,.18); }

/* JSON syntax highlight colours */
:deep(.jk)   { color: #818cf8; font-weight: 500; }   /* key   */
:deep(.js)   { color: #34d399; }                      /* string */
:deep(.jnum) { color: #fbbf24; }                      /* number */
:deep(.jb)   { color: #f472b6; }                      /* bool   */
:deep(.jn)   { color: #9ca3af; }                      /* null   */

/* toast animation */
.toast-enter-active, .toast-leave-active { transition: all .25s cubic-bezier(.16,1,.3,1); }
.toast-enter-from, .toast-leave-to       { opacity: 0; transform: translateY(10px) scale(.95); }
</style>