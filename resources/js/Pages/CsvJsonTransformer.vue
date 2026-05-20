<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

// ─── State ───────────────────────────────────────────────────────────
const mode             = ref('csv-to-json');
const inputText        = ref('');
const outputText       = ref('');
const errorMessage     = ref('');
const delimiter        = ref('auto');
const firstRowHeaders  = ref(true);
const indentSize       = ref('2');
const copied           = ref(false);

const linesCount       = ref(0);
const sizeBytes        = ref(0);
const recordsCount     = ref(0);
const processingTime   = ref('—');

const toastVisible     = ref(false);
const toastMessage     = ref('');
const toastType        = ref('success');

const fileInputRef     = ref(null);

// ─── Toast ───────────────────────────────────────────────────────────
const showToast = (msg, type = 'success') => {
    toastMessage.value = msg;
    toastType.value    = type;
    toastVisible.value = true;
    setTimeout(() => { toastVisible.value = false; }, 2600);
};

// ─── CSV Parser ──────────────────────────────────────────────────────
const parseCSV = (text, delim, withHeaders) => {
    if (!text.trim()) return [];
    let d = delim;
    if (d === 'auto') {
        const c=(text.match(/,/g)||[]).length, s=(text.match(/;/g)||[]).length, t=(text.match(/\t/g)||[]).length;
        d = t>c&&t>s ? '\t' : s>c ? ';' : ',';
    }
    const lines=[]; let row=[], inQ=false, cell='';
    for (let i=0;i<text.length;i++) {
        const ch=text[i], nx=text[i+1];
        if (ch==='"') {
            if (inQ&&nx==='"') { cell+='"'; i++; } else inQ=!inQ;
        } else if (ch===d&&!inQ) {
            row.push(cell.trim()); cell='';
        } else if ((ch==='\r'||ch==='\n')&&!inQ) {
            if (ch==='\r'&&nx==='\n') i++;
            row.push(cell.trim());
            if (row.some(v=>v!=='')) lines.push(row);
            row=[]; cell='';
        } else cell+=ch;
    }
    if (cell!==''||row.length>0) { row.push(cell.trim()); if(row.some(v=>v!=='')) lines.push(row); }
    if (!lines.length) return [];
    if (withHeaders) {
        const headers=lines[0].map(h=>h.replace(/^"|"$/g,'').trim()||'col');
        return lines.slice(1).map(vals=>{
            const obj={};
            headers.forEach((h,i)=>{
                let v=vals[i]??'';
                if (v.toLowerCase()==='true') v=true;
                else if (v.toLowerCase()==='false') v=false;
                else if (v.toLowerCase()==='null') v=null;
                else if (!isNaN(v)&&v.trim()!=='') v=Number(v);
                obj[h]=v;
            });
            return obj;
        });
    }
    return lines;
};

// ─── JSON → CSV ──────────────────────────────────────────────────────
const jsonToCsv = (arr, delim) => {
    if (!Array.isArray(arr)||!arr.length) return '';
    const d = delim==='auto'?',':delim;
    const esc = v => { const s=String(v??''); return (s.includes(d)||s.includes('"')||s.includes('\n')) ? `"${s.replace(/"/g,'""')}"` : s; };
    const keys=[...new Set(arr.flatMap(r=>typeof r==='object'&&r?Object.keys(r):[]))];
    if (!keys.length) return arr.map(r=>esc(r)).join('\n');
    return [keys.map(esc).join(d), ...arr.map(r=>keys.map(k=>{ const v=r[k]; return esc(typeof v==='object'&&v?JSON.stringify(v):v); }).join(d))].join('\n');
};

// ─── Run conversion ──────────────────────────────────────────────────
const runConversion = () => {
    errorMessage.value = '';
    outputText.value   = '';
    const raw = inputText.value;
    if (!raw.trim()) { linesCount.value=0; sizeBytes.value=0; recordsCount.value=0; processingTime.value='—'; return; }
    localStorage.setItem(`fm_csvjson_${mode.value}`, raw);
    const t0 = performance.now();
    try {
        linesCount.value  = raw.split(/\r?\n/).length;
        sizeBytes.value   = new Blob([raw]).size;
        if (mode.value==='csv-to-json') {
            const parsed = parseCSV(raw, delimiter.value, firstRowHeaders.value);
            recordsCount.value = parsed.length;
            const sp = indentSize.value==='compact' ? undefined : indentSize.value==='tab' ? '\t' : parseInt(indentSize.value);
            outputText.value = JSON.stringify(parsed, null, sp);
        } else {
            const parsed = JSON.parse(raw);
            const arr = Array.isArray(parsed)?parsed:[parsed];
            recordsCount.value = arr.length;
            outputText.value = jsonToCsv(arr, delimiter.value);
        }
        processingTime.value = `${(performance.now()-t0).toFixed(2)}ms`;
    } catch(e) {
        errorMessage.value   = e.message||'Parse error — check your input format.';
        processingTime.value = '—';
    }
};

watch([inputText,mode,delimiter,firstRowHeaders,indentSize], runConversion);

// ─── Samples ─────────────────────────────────────────────────────────
const SAMPLE_CSV = `id,name,email,role,active,score
1,Alice Vance,alice@dev.io,Admin,true,98.5
2,Bob Miller,bob@dev.io,Engineer,true,87
3,Charlie Rose,charlie@dev.io,Designer,false,72
4,Diana Stark,diana@dev.io,Security,true,95
5,Evan Green,evan@dev.io,PM,true,91.2`;

const SAMPLE_JSON = JSON.stringify([
    {id:1,name:"Alice Vance",email:"alice@dev.io",role:"Admin",active:true,score:98.5},
    {id:2,name:"Bob Miller",email:"bob@dev.io",role:"Engineer",active:true,score:87},
    {id:3,name:"Charlie Rose",email:"charlie@dev.io",role:"Designer",active:false,score:72},
    {id:4,name:"Diana Stark",email:"diana@dev.io",role:"Security",active:true,score:95},
],null,2);

const loadSample = () => {
    inputText.value = mode.value==='csv-to-json' ? SAMPLE_CSV : SAMPLE_JSON;
    showToast('Sample data loaded');
};

const clearAll = () => {
    inputText.value=''; outputText.value=''; errorMessage.value='';
    localStorage.removeItem(`fm_csvjson_${mode.value}`);
    showToast('Workspace cleared');
};

const copyOutput = async () => {
    if (!outputText.value) return;
    await navigator.clipboard.writeText(outputText.value);
    copied.value=true; showToast('Copied to clipboard');
    setTimeout(()=>{ copied.value=false; }, 2000);
};

const downloadOutput = () => {
    if (!outputText.value) return;
    const ext = mode.value==='csv-to-json'?'json':'csv';
    const mime= mode.value==='csv-to-json'?'application/json':'text/csv';
    const url = URL.createObjectURL(new Blob([outputText.value],{type:mime}));
    const a   = Object.assign(document.createElement('a'),{href:url,download:`fluxmedia_${Date.now()}.${ext}`});
    document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(url);
    showToast(`Downloaded .${ext} file`);
};

const swapSides = () => {
    if (!outputText.value||errorMessage.value) return;
    const temp=outputText.value;
    mode.value = mode.value==='csv-to-json'?'json-to-csv':'csv-to-json';
    inputText.value=temp;
    showToast('Input & output swapped');
};

const handleFile = e => {
    const file=e.target.files[0]; if(!file) return;
    const reader=new FileReader();
    reader.onload=ev=>{ inputText.value=ev.target.result; showToast(`Loaded ${file.name}`); };
    reader.readAsText(file);
    e.target.value='';
};

const fmtSize = b => {
    if (!b) return '0 B';
    const k=1024, u=['B','KB','MB'];
    const i=Math.floor(Math.log(b)/Math.log(k));
    return `${(b/Math.pow(k,i)).toFixed(2)} ${u[i]}`;
};

// ─── Syntax highlight (JSON only) ────────────────────────────────────
const highlightedOutput = computed(() => {
    if (!outputText.value || mode.value==='json-to-csv') return null;
    return outputText.value
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, match => {
            if (/^"/.test(match)) {
                return /:$/.test(match) ? `<span class="json-key">${match}</span>` : `<span class="json-str">${match}</span>`;
            }
            if (/true|false/.test(match)) return `<span class="json-bool">${match}</span>`;
            if (/null/.test(match)) return `<span class="json-null">${match}</span>`;
            return `<span class="json-num">${match}</span>`;
        });
});

// ─── Lifecycle ───────────────────────────────────────────────────────
let ldScript=null;
onMounted(()=>{
    ldScript=document.createElement('script');
    ldScript.type='application/ld+json';
    ldScript.textContent=JSON.stringify({
        '@context':'https://schema.org','@type':'WebApplication',
        name:'FluxMedia CSV ↔ JSON Converter',
        url:'https://fluxmedia.space/tools/csv-json',
        description:'Free browser-based CSV to JSON and JSON to CSV converter. Instant, private, no uploads.',
        applicationCategory:'DeveloperApplication',operatingSystem:'Web',
        offers:{'@type':'Offer',price:'0',priceCurrency:'USD'}
    });
    document.head.appendChild(ldScript);
    const saved=localStorage.getItem(`fm_csvjson_${mode.value}`);
    if (saved) { inputText.value=saved; }
});
onUnmounted(()=>ldScript?.remove());
</script>

<template>
    <PublicLayout>
        <Head>
            <title>CSV ↔ JSON Converter — Free, Private & Instant | FluxMedia</title>
            <meta name="description" content="Convert CSV to JSON or JSON to CSV instantly in your browser. 100% private — no data leaves your device. Supports auto-detect delimiter, custom indent, download, and file upload." />
            <meta name="keywords" content="csv to json, json to csv, csv converter, json converter, online csv parser, browser json formatter, free data transformer, spreadsheet to json" />
            <meta name="robots" content="index, follow" />
            <link rel="canonical" href="https://fluxmedia.space/tools/csv-json" />
            <meta property="og:type" content="website" />
            <meta property="og:title" content="CSV ↔ JSON Converter — Free & Private | FluxMedia" />
            <meta property="og:description" content="Instant browser-side CSV↔JSON conversion. No uploads, no accounts, no limits." />
            <meta property="og:image" content="https://fluxmedia.space/assets/images/fluxmedia_og.webp" />
            <meta property="og:image:width" content="1200" /><meta property="og:image:height" content="630" />
            <meta property="og:url" content="https://fluxmedia.space/tools/csv-json" />
            <meta property="og:site_name" content="FluxMedia" />
            <meta name="twitter:card" content="summary_large_image" />
            <meta name="twitter:title" content="CSV ↔ JSON Converter — FluxMedia" />
            <meta name="twitter:description" content="Free, instant, private CSV↔JSON conversion in your browser." />
            <meta name="twitter:image" content="https://fluxmedia.space/assets/images/fluxmedia_og.webp" />
        </Head>

        <!-- ░░ BACKGROUND ░░ -->
        <div class="fixed inset-0 -z-10 bg-[#07090f]" aria-hidden="true">
            <!-- scanline texture -->
            <div class="absolute inset-0 opacity-[0.025]"
                 style="background-image:repeating-linear-gradient(0deg,transparent,transparent 2px,rgba(255,255,255,0.1) 2px,rgba(255,255,255,0.1) 3px);"></div>
            <!-- subtle grid -->
            <div class="absolute inset-0 opacity-[0.04]"
                 style="background-image:linear-gradient(rgba(99,102,241,0.4) 1px,transparent 1px),linear-gradient(90deg,rgba(99,102,241,0.4) 1px,transparent 1px);background-size:48px 48px;"></div>
            <!-- corner glows -->
            <div class="absolute top-0 left-0 w-[600px] h-[400px] bg-indigo-600/10 blur-[160px] rounded-full -translate-x-1/3 -translate-y-1/3"></div>
            <div class="absolute bottom-0 right-0 w-[500px] h-[400px] bg-violet-600/8 blur-[140px] rounded-full translate-x-1/3 translate-y-1/3"></div>
        </div>

        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-8 relative z-10">

            <!-- ░░ HEADER ░░ -->
            <header class="mb-8">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">

                    <!-- Left: title -->
                    <div class="space-y-1">
                        <div class="flex items-center gap-3 mb-3">
                            <span class="font-mono text-xs text-gray-500 tracking-wider uppercase">fluxmedia / tools /</span>
                            <span class="font-mono text-xs text-indigo-400 font-semibold tracking-wider">csv-json</span>
                        </div>
                        <h1 class="font-mono text-2xl sm:text-3xl font-black text-white tracking-tight leading-none">
                            CSV <span class="text-indigo-400">↔</span> JSON
                            <span class="font-thin text-gray-500 ml-2 text-xl">Transformer</span>
                        </h1>
                        <p class="text-xs sm:text-sm text-gray-400 font-mono mt-1">
                            browser-native · zero uploads · instant · private
                        </p>
                    </div>

                    <!-- Right: mode switcher + actions -->
                    <div class="flex flex-wrap items-center gap-3">

                        <!-- Mode toggle — pill style -->
                        <div class="flex items-center bg-[#0d111c]/90 border border-white/[0.08] rounded-xl p-1 font-mono shadow-inner shadow-black/40">
                            <button @click="mode='csv-to-json'"
                                    :class="['px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wider transition-all duration-200',
                                        mode==='csv-to-json'?'bg-indigo-600/30 text-white border border-indigo-500/30 shadow-md shadow-indigo-600/10':'text-gray-400 hover:text-gray-200 hover:bg-white/[0.03]']">
                                CSV → JSON
                            </button>
                            <button @click="mode='json-to-csv'"
                                    :class="['px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wider transition-all duration-200',
                                        mode==='json-to-csv'?'bg-indigo-600/30 text-white border border-indigo-500/30 shadow-md shadow-indigo-600/10':'text-gray-400 hover:text-gray-200 hover:bg-white/[0.03]']">
                                JSON → CSV
                            </button>
                        </div>

                        <!-- Action buttons -->
                        <label class="btn-ghost cursor-pointer flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            Upload
                            <input type="file" accept=".csv,.json,.txt" @change="handleFile" class="hidden" ref="fileInputRef"/>
                        </label>
                        <button @click="loadSample" class="btn-ghost">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            Sample
                        </button>
                        <button @click="clearAll" class="btn-danger">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Clear
                        </button>

                    </div>
                </div>

                <!-- Config bar -->
                <div class="mt-5 flex flex-wrap items-center gap-6 bg-[#0d111c] border border-white/[0.06] rounded-xl px-5 py-4 shadow-lg shadow-black/25">
                    <div class="flex items-center gap-2.5">
                        <span class="font-mono text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-gray-400">Delimiter</span>
                        <select v-model="delimiter" class="config-select">
                            <option value="auto">auto-detect</option>
                            <option value=",">comma  ,</option>
                            <option value=";">semicolon  ;</option>
                            <option value="&#9;">tab  \t</option>
                        </select>
                    </div>

                    <div class="hidden sm:block w-px h-5 bg-white/[0.08]"></div>

                    <div v-if="mode==='csv-to-json'" class="flex items-center gap-2.5">
                        <span class="font-mono text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-gray-400">Indent</span>
                        <select v-model="indentSize" class="config-select">
                            <option value="2">2 spaces</option>
                            <option value="4">4 spaces</option>
                            <option value="tab">tab</option>
                            <option value="compact">compact</option>
                        </select>
                    </div>

                    <div v-if="mode==='csv-to-json'" class="hidden sm:block w-px h-5 bg-white/[0.08]"></div>

                    <div v-if="mode==='csv-to-json'" class="flex items-center gap-3 select-none">
                        <button @click="firstRowHeaders=!firstRowHeaders" 
                                class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                :class="firstRowHeaders ? 'bg-indigo-600' : 'bg-white/[0.1]'"
                                aria-label="Toggle first row as headers">
                            <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"
                                  :class="firstRowHeaders ? 'translate-x-4' : 'translate-x-0'"></span>
                        </button>
                        <span class="font-mono text-xs text-gray-400 cursor-pointer" @click="firstRowHeaders=!firstRowHeaders">first row = headers</span>
                    </div>

                    <!-- Stats strip -->
                    <div class="ml-auto flex items-center gap-6">
                        <div v-for="s in [
                            {label:'size',  val:fmtSize(sizeBytes)},
                            {label:'lines', val:linesCount||'—'},
                            {label:'records',val:recordsCount||'—'},
                            {label:'speed', val:processingTime, accent:true},
                        ]" :key="s.label" class="text-center sm:text-left">
                            <p class="font-mono text-[9px] sm:text-[10px] font-semibold uppercase tracking-wider text-gray-500 leading-none mb-1">{{ s.label }}</p>
                            <p :class="['font-mono text-xs sm:text-sm font-bold', s.accent?'text-emerald-400':'text-gray-200']">{{ s.val }}</p>
                        </div>
                    </div>
                </div>
            </header>

            <!-- ░░ EDITOR PANES ░░ -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 min-h-[550px] lg:h-[650px] mt-6">

                <!-- INPUT PANE -->
                <div class="editor-pane flex flex-col">
                    <!-- Pane titlebar -->
                    <div class="pane-bar">
                        <div class="flex items-center gap-2">
                            <span class="dot bg-rose-500/80"></span>
                            <span class="dot bg-amber-500/80"></span>
                            <span class="dot bg-emerald-500/80"></span>
                        </div>
                        <span class="font-mono text-xs text-gray-400 tracking-wider ml-1">
                            {{ mode==='csv-to-json' ? 'input.csv' : 'input.json' }}
                        </span>
                        <div class="ml-auto flex items-center gap-1.5">
                            <span v-if="inputText" class="font-mono text-xs text-gray-500">{{ linesCount }} lines</span>
                        </div>
                    </div>

                    <!-- Textarea -->
                    <div class="relative flex-1 overflow-hidden">
                        <textarea
                            v-model="inputText"
                            spellcheck="false"
                            :placeholder="mode==='csv-to-json'
                                ? 'id,name,email\n1,Alice,alice@example.com\n2,Bob,bob@example.com'
                                : '[{\n  &quot;id&quot;: 1,\n  &quot;name&quot;: &quot;Alice&quot;\n}]'"
                            class="editor-textarea"
                            aria-label="Input data"
                        ></textarea>
                    </div>
                </div>

                <!-- OUTPUT PANE -->
                <div class="editor-pane flex flex-col">
                    <!-- Pane titlebar -->
                    <div class="pane-bar">
                        <div class="flex items-center gap-2">
                            <span class="dot" :class="errorMessage?'bg-rose-500':'bg-indigo-500/80 animate-pulse'"></span>
                            <span class="dot bg-white/10"></span>
                            <span class="dot bg-white/10"></span>
                        </div>
                        <span class="font-mono text-xs tracking-wider ml-1" :class="errorMessage?'text-rose-500':'text-gray-400'">
                            {{ errorMessage ? 'error' : mode==='csv-to-json' ? 'output.json' : 'output.csv' }}
                        </span>
                        <!-- Output actions -->
                        <div v-if="outputText && !errorMessage" class="ml-auto flex items-center gap-2">
                            <button @click="swapSides" class="pane-action" title="Swap input/output and flip mode">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                                swap
                            </button>
                            <button @click="copyOutput" :class="['pane-action', copied?'text-emerald-400 hover:text-emerald-300 hover:border-emerald-500/30 hover:bg-emerald-500/5 border-emerald-500/20':'']">
                                <svg v-if="copied" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                {{ copied?'copied':'copy' }}
                            </button>
                            <button @click="downloadOutput" class="pane-action pane-action-accent">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                download
                            </button>
                        </div>
                    </div>

                    <!-- Output content -->
                    <div class="relative flex-1 overflow-hidden">

                        <!-- Error state -->
                        <div v-if="errorMessage" class="absolute inset-0 flex flex-col items-start justify-start p-5 gap-3 overflow-auto">
                            <div class="flex items-center gap-2 text-rose-400 font-mono text-xs font-bold uppercase tracking-wider">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Parse Error
                            </div>
                            <pre class="font-mono text-xs text-rose-300/90 whitespace-pre-wrap leading-relaxed bg-rose-500/5 border border-rose-500/10 rounded-lg p-4 w-full">{{ errorMessage }}</pre>
                            <p class="font-mono text-xs text-gray-400 mt-2">
                                Tip: {{ mode==='csv-to-json' ? 'Make sure values with commas are quoted. e.g.  "Smith, John"' : 'Ensure your JSON is a valid array [] or object {}.' }}
                            </p>
                        </div>

                        <!-- Empty state -->
                        <div v-else-if="!outputText" class="absolute inset-0 flex flex-col items-center justify-center gap-3 p-8">
                            <div class="w-16 h-16 rounded-2xl border border-white/[0.08] bg-white/[0.02] flex items-center justify-center shadow-lg shadow-black/20">
                                <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <p class="font-mono text-xs text-gray-400 text-center leading-relaxed">
                                Output renders here<br>
                                <span class="text-gray-500">← paste data on the left</span>
                            </p>
                        </div>

                        <!-- Syntax-highlighted JSON output -->
                        <div v-else-if="mode==='csv-to-json' && highlightedOutput"
                             class="absolute inset-0 overflow-auto editor-code-scroll">
                            <pre class="editor-pre" v-html="highlightedOutput"></pre>
                        </div>

                        <!-- Plain CSV output -->
                        <textarea v-else
                                  readonly
                                  :value="outputText"
                                  class="editor-textarea text-emerald-300/90"
                                  aria-label="Transformed output"
                        ></textarea>

                    </div>
                </div>

            </div>

            <!-- ░░ FOOTER INFO ░░ -->
            <footer class="mt-6 flex flex-wrap items-center justify-between gap-4 border-t border-white/[0.04] pt-5">
                <p class="font-mono text-xs text-gray-500">
                    All processing runs locally in your browser tab. No data is transmitted to any server.
                </p>
                <div class="flex items-center gap-4">
                    <span class="font-mono text-xs text-gray-600 uppercase tracking-widest">FluxMedia · csv-json v2.0</span>
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                </div>
            </footer>

        </div>

        <!-- ░░ TOAST ░░ -->
        <Transition name="toast">
            <div v-if="toastVisible"
                 :class="['fixed bottom-6 right-6 z-50 flex items-center gap-3 px-4 py-3 rounded-xl border font-mono text-[11px] font-bold shadow-2xl backdrop-blur-md',
                     toastType==='success'
                         ? 'bg-[#0a0f0a]/95 border-emerald-500/30 text-emerald-300'
                         : 'bg-[#0f0a0a]/95 border-rose-500/30 text-rose-300']">
                <span class="w-1.5 h-1.5 rounded-full animate-ping"
                      :class="toastType==='success'?'bg-emerald-400':'bg-rose-400'"></span>
                {{ toastMessage }}
            </div>
        </Transition>

    </PublicLayout>
</template>

<style scoped>
/* ── Button variants ── */
.btn-ghost {
    @apply flex items-center gap-1.5 px-4 py-2 rounded-xl bg-white/[0.04] border border-white/[0.08] text-gray-300 hover:text-white hover:border-white/20 hover:bg-white/[0.08] text-xs font-mono font-semibold uppercase tracking-wider transition-all active:scale-95 cursor-pointer shadow-sm;
}
.btn-danger {
    @apply flex items-center gap-1.5 px-4 py-2 rounded-xl bg-rose-500/[0.08] border border-rose-500/25 text-rose-300 hover:text-rose-100 hover:bg-rose-500/20 hover:border-rose-500/40 text-xs font-mono font-semibold uppercase tracking-wider transition-all active:scale-95 shadow-sm;
}

/* ── Config select ── */
.config-select {
    @apply bg-transparent border-0 text-gray-200 font-mono text-xs font-semibold focus:ring-0 focus:outline-none cursor-pointer hover:text-white transition-colors py-0 px-2;
}
.config-select option {
    background: #0d111c;
    color: #d1d5db;
}

/* ── Editor pane ── */
.editor-pane {
    @apply rounded-2xl border border-white/[0.07] bg-[#0b0e18] overflow-hidden shadow-lg shadow-black/30;
}
.pane-bar {
    @apply flex items-center gap-2.5 px-4 py-3.5 border-b border-white/[0.06] bg-[#090c14] flex-shrink-0;
}
.dot {
    @apply w-2.5 h-2.5 rounded-full;
}
.pane-action {
    @apply flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-mono text-[11px] font-semibold uppercase tracking-wider text-gray-400 hover:text-white hover:bg-white/[0.06] transition-all active:scale-95 border border-transparent hover:border-white/[0.08];
}
.pane-action-accent {
    @apply text-indigo-400 hover:text-indigo-200 hover:bg-indigo-500/10 hover:border-indigo-500/20;
}

/* ── Editor textarea ── */
.editor-textarea {
    @apply w-full h-full bg-transparent border-0 resize-none focus:outline-none focus:ring-0 font-mono text-sm text-gray-200 leading-relaxed p-5;
    tab-size: 4;
}
.editor-textarea::placeholder {
    color: rgba(255, 255, 255, 0.22);
    font-style: italic;
}
.editor-code-scroll {
    @apply font-mono;
}
.editor-pre {
    @apply text-sm leading-relaxed p-5 text-gray-200 whitespace-pre overflow-visible m-0;
    min-width: max-content;
}

/* Custom scrollbar */
.editor-textarea::-webkit-scrollbar,
.editor-code-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
.editor-textarea::-webkit-scrollbar-track,
.editor-code-scroll::-webkit-scrollbar-track { background: transparent; }
.editor-textarea::-webkit-scrollbar-thumb,
.editor-code-scroll::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 99px; }
.editor-textarea::-webkit-scrollbar-thumb:hover,
.editor-code-scroll::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.18); }

/* ── JSON syntax highlight colors ── */
:deep(.json-key)  { color: #818cf8; font-weight: 500; }
:deep(.json-str)  { color: #34d399; }
:deep(.json-num)  { color: #fbbf24; }
:deep(.json-bool) { color: #f472b6; }
:deep(.json-null) { color: #9ca3af; }

/* ── Toast transition ── */
.toast-enter-active, .toast-leave-active { transition: all 0.25s cubic-bezier(0.16,1,0.3,1); }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(12px) scale(0.95); }
</style>