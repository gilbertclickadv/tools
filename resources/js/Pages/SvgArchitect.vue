<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

// ─── State ───────────────────────────────────────────────────────────
const inputText = ref('');
const outputText = ref('');
const errorMessage = ref('');
const copied = ref(false);

// Viewport controls
const showGrid = ref(true);
const showAxes = ref(true);
const showBounds = ref(true);
const viewBoxMode = ref('auto'); // 'auto', '24x24', '100x100', '512x512'

// Interactive Pan & Zoom state
const panX = ref(0);
const panY = ref(0);
const zoomScale = ref(1);
const isPanning = ref(false);
const startDragX = ref(0);
const startDragY = ref(0);
const startPanX = ref(0);
const startPanY = ref(0);
const svgRef = ref(null);

// Telemetry & stats
const pathLength = ref(0);
const cmdCount = ref(0);
const rawSizeBytes = ref(0);
const optimizedSizeBytes = ref(0);
const processingTime = ref('—');

// Toast notifications
const toastVisible = ref(false);
const toastMessage = ref('');
const toastType = ref('success');

// Transformation state inputs
const scaleFactor = ref('1.2');
const shiftX = ref('10');
const shiftY = ref('10');
const rotateAngle = ref('45');
const decimalPrecision = ref('2'); // 'auto', '0', '1', '2', '3', '4'
const coordinateMode = ref('no-change'); // 'no-change', 'absolute', 'relative'

// Framework wrapping export template
const exportFormat = ref('raw'); // 'raw', 'svg', 'react', 'vue'

const showToast = (msg, type = 'success') => {
    toastMessage.value = msg;
    toastType.value = type;
    toastVisible.value = true;
    setTimeout(() => { toastVisible.value = false; }, 2600);
};

// ─── SVG Path Parser & Tokenizer ──────────────────────────────────────
const tokenizePath = (d) => {
    if (!d) return [];
    // Matches command keys (a-z) or numbers (including scientific, signs, decimals)
    const regex = /([a-df-zS-Z])|(-?\d*\.?\d+(?:[eE][-+]?\d+)?)/gi;
    const tokens = [];
    let match;
    while ((match = regex.exec(d)) !== null) {
        if (match[1]) {
            tokens.push({ type: 'command', value: match[1] });
        } else if (match[2]) {
            tokens.push({ type: 'number', value: parseFloat(match[2]) });
        }
    }
    return tokens;
};

const getExpectedArgs = (cmd) => {
    const lower = cmd.toLowerCase();
    if (lower === 'z') return 0;
    if (lower === 'h' || lower === 'v') return 1;
    if (lower === 'm' || lower === 'l' || lower === 't') return 2;
    if (lower === 's' || lower === 'q') return 4;
    if (lower === 'c') return 6;
    if (lower === 'a') return 7;
    return 0;
};

const parseTokens = (tokens) => {
    const commands = [];
    let currentCmd = null;
    let expectedArgsCount = 0;
    let accumulatedArgs = [];

    for (let i = 0; i < tokens.length; i++) {
        const token = tokens[i];
        if (token.type === 'command') {
            if (currentCmd && (accumulatedArgs.length > 0 || getExpectedArgs(currentCmd) === 0)) {
                commands.push({ cmd: currentCmd, args: accumulatedArgs });
            }
            currentCmd = token.value;
            expectedArgsCount = getExpectedArgs(currentCmd);
            accumulatedArgs = [];
            if (expectedArgsCount === 0) {
                commands.push({ cmd: currentCmd, args: [] });
                currentCmd = null;
            }
        } else if (token.type === 'number') {
            if (!currentCmd) {
                if (commands.length === 0) {
                    currentCmd = 'M';
                    expectedArgsCount = 2;
                } else {
                    const lastCmd = commands[commands.length - 1].cmd;
                    if (lastCmd === 'Z' || lastCmd === 'z') {
                        currentCmd = (lastCmd === 'Z' ? 'M' : 'm');
                    } else {
                        currentCmd = (lastCmd === 'M' ? 'L' : lastCmd === 'm' ? 'l' : lastCmd);
                    }
                    expectedArgsCount = getExpectedArgs(currentCmd);
                }
            }
            accumulatedArgs.push(token.value);
            if (accumulatedArgs.length === expectedArgsCount) {
                commands.push({ cmd: currentCmd, args: accumulatedArgs });
                accumulatedArgs = [];
                if (currentCmd === 'M') {
                    currentCmd = 'L';
                    expectedArgsCount = 2;
                } else if (currentCmd === 'm') {
                    currentCmd = 'l';
                    expectedArgsCount = 2;
                }
            }
        }
    }
    if (currentCmd && accumulatedArgs.length > 0) {
        commands.push({ cmd: currentCmd, args: accumulatedArgs });
    }
    return commands;
};

// ─── Safe Argument Utility ───────────────────────────────────────────
const getSafeArgs = (cmd, args, cursorX, cursorY) => {
    const expected = getExpectedArgs(cmd);
    const safe = [];
    const isRelative = (cmd === cmd.toLowerCase());
    const upper = cmd.toUpperCase();

    for (let i = 0; i < expected; i++) {
        let val = args[i];
        if (val === undefined || val === null || isNaN(val)) {
            if (isRelative) {
                safe.push(0);
            } else {
                if (upper === 'H') {
                    safe.push(cursorX);
                } else if (upper === 'V') {
                    safe.push(cursorY);
                } else if (upper === 'M' || upper === 'L' || upper === 'T') {
                    safe.push(i === 0 ? cursorX : cursorY);
                } else if (upper === 'C') {
                    safe.push(i % 2 === 0 ? cursorX : cursorY);
                } else if (upper === 'S' || upper === 'Q') {
                    safe.push(i % 2 === 0 ? cursorX : cursorY);
                } else if (upper === 'A') {
                    if (i === 5) safe.push(cursorX);
                    else if (i === 6) safe.push(cursorY);
                    else safe.push(0);
                } else {
                    safe.push(0);
                }
            }
        } else {
            safe.push(val);
        }
    }
    return safe;
};

// ─── Absolute / Relative Converters ───────────────────────────────────
const convertToAbsolute = (commands) => {
    let cursorX = 0;
    let cursorY = 0;
    let startX = 0;
    let startY = 0;

    return commands.map(c => {
        const cmd = c.cmd;
        const lowerCmd = cmd.toLowerCase();
        const isRelative = (cmd === lowerCmd);
        const upperCmd = cmd.toUpperCase();

        const args = getSafeArgs(cmd, c.args || [], cursorX, cursorY);

        if (upperCmd === 'M') {
            if (isRelative) {
                cursorX += args[0];
                cursorY += args[1];
            } else {
                cursorX = args[0];
                cursorY = args[1];
            }
            startX = cursorX;
            startY = cursorY;
            return { cmd: 'M', args: [cursorX, cursorY] };
        }

        if (upperCmd === 'L') {
            if (isRelative) {
                cursorX += args[0];
                cursorY += args[1];
            } else {
                cursorX = args[0];
                cursorY = args[1];
            }
            return { cmd: 'L', args: [cursorX, cursorY] };
        }

        if (upperCmd === 'H') {
            if (isRelative) {
                cursorX += args[0];
            } else {
                cursorX = args[0];
            }
            return { cmd: 'H', args: [cursorX] };
        }

        if (upperCmd === 'V') {
            if (isRelative) {
                cursorY += args[0];
            } else {
                cursorY = args[0];
            }
            return { cmd: 'V', args: [cursorY] };
        }

        if (upperCmd === 'C') {
            let x1, y1, x2, y2, x, y;
            if (isRelative) {
                x1 = cursorX + args[0];
                y1 = cursorY + args[1];
                x2 = cursorX + args[2];
                y2 = cursorY + args[3];
                x = cursorX + args[4];
                y = cursorY + args[5];
            } else {
                x1 = args[0]; y1 = args[1];
                x2 = args[2]; y2 = args[3];
                x = args[4]; y = args[5];
            }
            cursorX = x;
            cursorY = y;
            return { cmd: 'C', args: [x1, y1, x2, y2, x, y] };
        }

        if (upperCmd === 'S') {
            let x2, y2, x, y;
            if (isRelative) {
                x2 = cursorX + args[0];
                y2 = cursorY + args[1];
                x = cursorX + args[2];
                y = cursorY + args[3];
            } else {
                x2 = args[0]; y2 = args[1];
                x = args[2]; y = args[3];
            }
            cursorX = x;
            cursorY = y;
            return { cmd: 'S', args: [x2, y2, x, y] };
        }

        if (upperCmd === 'Q') {
            let x1, y1, x, y;
            if (isRelative) {
                x1 = cursorX + args[0];
                y1 = cursorY + args[1];
                x = cursorX + args[2];
                y = cursorY + args[3];
            } else {
                x1 = args[0]; y1 = args[1];
                x = args[2]; y = args[3];
            }
            cursorX = x;
            cursorY = y;
            return { cmd: 'Q', args: [x1, y1, x, y] };
        }

        if (upperCmd === 'T') {
            let x, y;
            if (isRelative) {
                x = cursorX + args[0];
                y = cursorY + args[1];
            } else {
                x = args[0]; y = args[1];
            }
            cursorX = x;
            cursorY = y;
            return { cmd: 'T', args: [x, y] };
        }

        if (upperCmd === 'A') {
            let rx = args[0], ry = args[1], xar = args[2], laf = args[3], sf = args[4], x, y;
            if (isRelative) {
                x = cursorX + args[5];
                y = cursorY + args[6];
            } else {
                x = args[5]; y = args[6];
            }
            cursorX = x;
            cursorY = y;
            return { cmd: 'A', args: [rx, ry, xar, laf, sf, x, y] };
        }

        if (upperCmd === 'Z') {
            cursorX = startX;
            cursorY = startY;
            return { cmd: 'Z', args: [] };
        }

        return { cmd, args };
    });
};

const convertToRelative = (absoluteCommands) => {
    let cursorX = 0;
    let cursorY = 0;
    let startX = 0;
    let startY = 0;

    return absoluteCommands.map(c => {
        const cmd = c.cmd;
        const args = getSafeArgs(cmd, c.args || [], cursorX, cursorY);
        if (cmd === 'M') {
            const rx = args[0] - cursorX;
            const ry = args[1] - cursorY;
            cursorX = args[0];
            cursorY = args[1];
            startX = cursorX;
            startY = cursorY;
            return { cmd: 'm', args: [rx, ry] };
        }
        if (cmd === 'L') {
            const rx = args[0] - cursorX;
            const ry = args[1] - cursorY;
            cursorX = args[0];
            cursorY = args[1];
            return { cmd: 'l', args: [rx, ry] };
        }
        if (cmd === 'H') {
            const rx = args[0] - cursorX;
            cursorX = args[0];
            return { cmd: 'h', args: [rx] };
        }
        if (cmd === 'V') {
            const ry = args[0] - cursorY;
            cursorY = args[0];
            return { cmd: 'v', args: [ry] };
        }
        if (cmd === 'C') {
            const rx1 = args[0] - cursorX;
            const ry1 = args[1] - cursorY;
            const rx2 = args[2] - cursorX;
            const ry2 = args[3] - cursorY;
            const rx = args[4] - cursorX;
            const ry = args[5] - cursorY;
            cursorX = args[4];
            cursorY = args[5];
            return { cmd: 'c', args: [rx1, ry1, rx2, ry2, rx, ry] };
        }
        if (cmd === 'S') {
            const rx2 = args[0] - cursorX;
            const ry2 = args[1] - cursorY;
            const rx = args[2] - cursorX;
            const ry = args[3] - cursorY;
            cursorX = args[2];
            cursorY = args[3];
            return { cmd: 's', args: [rx2, ry2, rx, ry] };
        }
        if (cmd === 'Q') {
            const rx1 = args[0] - cursorX;
            const ry1 = args[1] - cursorY;
            const rx = args[2] - cursorX;
            const ry = args[3] - cursorY;
            cursorX = args[2];
            cursorY = args[3];
            return { cmd: 'q', args: [rx1, ry1, rx, ry] };
        }
        if (cmd === 'T') {
            const rx = args[0] - cursorX;
            const ry = args[1] - cursorY;
            cursorX = args[0];
            cursorY = args[1];
            return { cmd: 't', args: [rx, ry] };
        }
        if (cmd === 'A') {
            const rx = args[5] - cursorX;
            const ry = args[6] - cursorY;
            cursorX = args[5];
            cursorY = args[6];
            return { cmd: 'a', args: [args[0], args[1], args[2], args[3], args[4], rx, ry] };
        }
        if (cmd === 'Z') {
            cursorX = startX;
            cursorY = startY;
            return { cmd: 'z', args: [] };
        }
        return c;
    });
};

// ─── Geometric Telemetry ──────────────────────────────────────────────
const computeBoundingBox = (absoluteCommands) => {
    let minX = Infinity;
    let minY = Infinity;
    let maxX = -Infinity;
    let maxY = -Infinity;

    absoluteCommands.forEach(c => {
        const cmd = c.cmd;
        const args = c.args;
        if (cmd === 'M' || cmd === 'L' || cmd === 'T') {
            if (!isNaN(args[0]) && isFinite(args[0]) && !isNaN(args[1]) && isFinite(args[1])) {
                minX = Math.min(minX, args[0]);
                maxX = Math.max(maxX, args[0]);
                minY = Math.min(minY, args[1]);
                maxY = Math.max(maxY, args[1]);
            }
        } else if (cmd === 'H') {
            if (!isNaN(args[0]) && isFinite(args[0])) {
                minX = Math.min(minX, args[0]);
                maxX = Math.max(maxX, args[0]);
            }
        } else if (cmd === 'V') {
            if (!isNaN(args[0]) && isFinite(args[0])) {
                minY = Math.min(minY, args[0]);
                maxY = Math.max(maxY, args[0]);
            }
        } else if (cmd === 'C') {
            const xs = [args[0], args[2], args[4]].filter(v => !isNaN(v) && isFinite(v));
            const ys = [args[1], args[3], args[5]].filter(v => !isNaN(v) && isFinite(v));
            if (xs.length > 0) {
                minX = Math.min(minX, ...xs);
                maxX = Math.max(maxX, ...xs);
            }
            if (ys.length > 0) {
                minY = Math.min(minY, ...ys);
                maxY = Math.max(maxY, ...ys);
            }
        } else if (cmd === 'S' || cmd === 'Q') {
            const xs = [args[0], args[2]].filter(v => !isNaN(v) && isFinite(v));
            const ys = [args[1], args[3]].filter(v => !isNaN(v) && isFinite(v));
            if (xs.length > 0) {
                minX = Math.min(minX, ...xs);
                maxX = Math.max(maxX, ...xs);
            }
            if (ys.length > 0) {
                minY = Math.min(minY, ...ys);
                maxY = Math.max(maxY, ...ys);
            }
        } else if (cmd === 'A') {
            if (!isNaN(args[5]) && isFinite(args[5]) && !isNaN(args[6]) && isFinite(args[6])) {
                minX = Math.min(minX, args[5]);
                maxX = Math.max(maxX, args[5]);
                minY = Math.min(minY, args[6]);
                maxY = Math.max(maxY, args[6]);
            }
        }
    });

    if (minX === Infinity || minY === Infinity || maxX === -Infinity || maxY === -Infinity || isNaN(minX) || isNaN(minY) || isNaN(maxX) || isNaN(maxY)) {
        return { minX: 0, minY: 0, maxX: 24, maxY: 24, width: 24, height: 24 };
    }

    return {
        minX,
        minY,
        maxX,
        maxY,
        width: maxX - minX,
        height: maxY - minY
    };
};

const boundingBox = computed(() => {
    if (!inputText.value.trim()) {
        return { minX: 0, minY: 0, maxX: 24, maxY: 24, width: 24, height: 24 };
    }
    try {
        const tokens = tokenizePath(extractPathData(inputText.value));
        const parsed = parseTokens(tokens);
        const absolute = convertToAbsolute(parsed);
        return computeBoundingBox(absolute);
    } catch (e) {
        return { minX: 0, minY: 0, maxX: 24, maxY: 24, width: 24, height: 24 };
    }
});

const renderViewBox = computed(() => {
    if (viewBoxMode.value === '24x24') return '0 0 24 24';
    if (viewBoxMode.value === '100x100') return '0 0 100 100';
    if (viewBoxMode.value === '512x512') return '0 0 512 512';
    
    // Auto mode: Bounding box with 10% padding
    const box = boundingBox.value;
    const padX = Math.max(2, box.width * 0.1);
    const padY = Math.max(2, box.height * 0.1);
    return `${(box.minX - padX).toFixed(1)} ${(box.minY - padY).toFixed(1)} ${(box.width + 2 * padX).toFixed(1)} ${(box.height + 2 * padY).toFixed(1)}`;
});

// Helper: extracts path "d" content from full SVG strings if pasted
const extractPathData = (raw) => {
    if (!raw) return '';
    const trimmed = raw.trim();
    if (trimmed.startsWith('<')) {
        const match = trimmed.match(/d\s*=\s*["']([^"']+)["']/i);
        if (match && match[1]) return match[1];
    }
    return trimmed;
};

// ─── Format path to String with precision controls ───────────────────
const formatPathString = (commands, precision) => {
    const fmtNum = (val) => {
        if (val === undefined || val === null || isNaN(val)) return 0;
        if (precision === 'auto') {
            const s = String(val);
            return s.length > 8 ? parseFloat(val.toFixed(4)) : val;
        }
        const p = parseInt(precision);
        return parseFloat(val.toFixed(p));
    };

    let result = '';
    let lastCmd = null;

    for (let i = 0; i < commands.length; i++) {
        const c = commands[i];
        const cmd = c.cmd;
        const args = c.args;

        if (args.length === 0) {
            result += (result ? ' ' : '') + cmd;
            lastCmd = cmd;
            continue;
        }

        let formattedArgs;
        if (cmd.toUpperCase() === 'A') {
            // rx ry x-axis-rotation large-arc sweep-flag x y
            // Arc flags are not rounded
            formattedArgs = `${fmtNum(args[0])} ${fmtNum(args[1])} ${args[2]} ${args[3]} ${args[4]} ${fmtNum(args[5])} ${fmtNum(args[6])}`;
        } else {
            formattedArgs = args.map(fmtNum).join(' ');
        }

        // Collapse consecutive matching command letters (except M/m which implicitly become L/l for subsequent coordinates)
        if (lastCmd === cmd && cmd.toLowerCase() !== 'm') {
            result += ' ' + formattedArgs;
        } else {
            result += (result ? ' ' : '') + cmd + ' ' + formattedArgs;
            lastCmd = cmd;
        }
    }

    return result.replace(/\s+/g, ' ').trim();
};

// ─── Main Pipeline Processor ──────────────────────────────────────────
const runOptimization = () => {
    errorMessage.value = '';
    outputText.value = '';
    const raw = inputText.value;

    if (!raw.trim()) {
        rawSizeBytes.value = 0;
        optimizedSizeBytes.value = 0;
        cmdCount.value = 0;
        pathLength.value = 0;
        processingTime.value = '—';
        return;
    }

    const t0 = performance.now();
    try {
        rawSizeBytes.value = new Blob([raw]).size;
        const pathData = extractPathData(raw);
        
        // Tokenize and Parse
        const tokens = tokenizePath(pathData);
        let parsed = parseTokens(tokens);
        cmdCount.value = parsed.length;

        // Convert absolute or relative
        if (coordinateMode.value === 'absolute') {
            parsed = convertToAbsolute(parsed);
        } else if (coordinateMode.value === 'relative') {
            const absolute = convertToAbsolute(parsed);
            parsed = convertToRelative(absolute);
        }

        // Output path string formatted with target decimal resolution
        const finalPath = formatPathString(parsed, decimalPrecision.value);
        pathLength.value = finalPath.length;

        // Wrap according to template select
        if (exportFormat.value === 'raw') {
            outputText.value = finalPath;
        } else if (exportFormat.value === 'svg') {
            const box = boundingBox.value;
            const w = Math.ceil(box.width);
            const h = Math.ceil(box.height);
            outputText.value = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${w} ${h}" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">\n  <path d="${finalPath}" />\n</svg>`;
        } else if (exportFormat.value === 'react') {
            const box = boundingBox.value;
            const w = Math.ceil(box.width);
            const h = Math.ceil(box.height);
            outputText.value = `import React from 'react';\n\nexport const MyIcon = (props) => (\n  <svg\n    xmlns="http://www.w3.org/2000/svg"\n    viewBox="0 0 ${w} ${h}"\n    fill="none"\n    stroke="currentColor"\n    strokeWidth={2}\n    strokeLinecap="round"\n    strokeLinejoin="round"\n    {...props}\n  >\n    <path d="${finalPath}" />\n  </svg>\n);`;
        } else if (exportFormat.value === 'vue') {
            const box = boundingBox.value;
            const w = Math.ceil(box.width);
            const h = Math.ceil(box.height);
            outputText.value = `<template>\n  <svg\n    xmlns="http://www.w3.org/2000/svg"\n    viewBox="0 0 ${w} ${h}"\n    fill="none"\n    stroke="currentColor"\n    stroke-width="2"\n    stroke-linecap="round"\n    stroke-linejoin="round"\n  >\n    <path d="${finalPath}" />\n  </svg>\n</template>`;
        }

        optimizedSizeBytes.value = new Blob([outputText.value]).size;
        processingTime.value = `${(performance.now() - t0).toFixed(2)}ms`;
        localStorage.setItem('fm_svg_architect_input', raw);
    } catch (e) {
        errorMessage.value = e.message || 'Error parsing SVG path parameters.';
        processingTime.value = '—';
    }
};

watch([inputText, decimalPrecision, coordinateMode, exportFormat], runOptimization);

// ─── Direct Vector Mutations ──────────────────────────────────────────
const applyMutation = (mutationType) => {
    const raw = inputText.value;
    if (!raw.trim()) return;

    try {
        const pathData = extractPathData(raw);
        const tokens = tokenizePath(pathData);
        const parsed = parseTokens(tokens);
        
        // Convert to absolute coordinates to guarantee mathematically correct rotation/mirroring
        let absolute = convertToAbsolute(parsed);
        const box = computeBoundingBox(absolute);
        const cx = box.minX + box.width / 2;
        const cy = box.minY + box.height / 2;

        if (mutationType === 'scale') {
            const factor = parseFloat(scaleFactor.value);
            if (isNaN(factor) || factor <= 0) {
                showToast('Scale factor must be a valid positive number', 'error');
                return;
            }
            absolute = absolute.map(c => {
                const cmd = c.cmd;
                const args = [...c.args];
                if (cmd === 'M' || cmd === 'L' || cmd === 'T') {
                    args[0] = cx + (args[0] - cx) * factor;
                    args[1] = cy + (args[1] - cy) * factor;
                } else if (cmd === 'H') {
                    args[0] = cx + (args[0] - cx) * factor;
                } else if (cmd === 'V') {
                    args[0] = cy + (args[0] - cy) * factor;
                } else if (cmd === 'C') {
                    args[0] = cx + (args[0] - cx) * factor;
                    args[1] = cy + (args[1] - cy) * factor;
                    args[2] = cx + (args[2] - cx) * factor;
                    args[3] = cy + (args[3] - cy) * factor;
                    args[4] = cx + (args[4] - cx) * factor;
                    args[5] = cy + (args[5] - cy) * factor;
                } else if (cmd === 'S' || cmd === 'Q') {
                    args[0] = cx + (args[0] - cx) * factor;
                    args[1] = cy + (args[1] - cy) * factor;
                    args[2] = cx + (args[2] - cx) * factor;
                    args[3] = cy + (args[3] - cy) * factor;
                } else if (cmd === 'A') {
                    args[0] = args[0] * factor;
                    args[1] = args[1] * factor;
                    args[5] = cx + (args[5] - cx) * factor;
                    args[6] = cy + (args[6] - cy) * factor;
                }
                return { cmd, args };
            });
            showToast(`Scaled shape uniformly by ${factor}x`);
        } else if (mutationType === 'translate') {
            const dx = parseFloat(shiftX.value);
            const dy = parseFloat(shiftY.value);
            if (isNaN(dx) || isNaN(dy)) {
                showToast('Translate offset coordinates must be valid numbers', 'error');
                return;
            }
            absolute = absolute.map(c => {
                const cmd = c.cmd;
                const args = [...c.args];
                if (cmd === 'M' || cmd === 'L' || cmd === 'T') {
                    args[0] += dx;
                    args[1] += dy;
                } else if (cmd === 'H') {
                    args[0] += dx;
                } else if (cmd === 'V') {
                    args[0] += dy;
                } else if (cmd === 'C') {
                    args[0] += dx; args[1] += dy;
                    args[2] += dx; args[3] += dy;
                    args[4] += dx; args[5] += dy;
                } else if (cmd === 'S' || cmd === 'Q') {
                    args[0] += dx; args[1] += dy;
                    args[2] += dx; args[3] += dy;
                } else if (cmd === 'A') {
                    args[5] += dx;
                    args[6] += dy;
                }
                return { cmd, args };
            });
            showToast(`Shifted coordinates offset [X: ${dx}, Y: ${dy}]`);
        } else if (mutationType === 'rotate') {
            const angle = parseFloat(rotateAngle.value);
            if (isNaN(angle)) {
                showToast('Rotate angle must be a valid number', 'error');
                return;
            }
            const rad = (angle * Math.PI) / 180;
            const cos = Math.cos(rad);
            const sin = Math.sin(rad);

            const rot = (x, y) => {
                const dx = x - cx;
                const dy = y - cy;
                return [cx + dx * cos - dy * sin, cy + dx * sin + dy * cos];
            };

            let curX = 0;
            let curY = 0;
            let startX = 0;
            let startY = 0;

            absolute = absolute.map(c => {
                let cmd = c.cmd;
                let args = [...c.args];

                // Track original coordinates before rotation
                let x = curX;
                let y = curY;

                if (cmd === 'M' || cmd === 'L' || cmd === 'T') {
                    x = args[0];
                    y = args[1];
                } else if (cmd === 'H') {
                    x = args[0];
                    y = curY;
                } else if (cmd === 'V') {
                    x = curX;
                    y = args[0];
                } else if (cmd === 'C') {
                    x = args[4];
                    y = args[5];
                } else if (cmd === 'S' || cmd === 'Q') {
                    x = args[2];
                    y = args[3];
                } else if (cmd === 'A') {
                    x = args[5];
                    y = args[6];
                } else if (cmd === 'Z') {
                    x = startX;
                    y = startY;
                }

                // Apply rotation
                if (cmd === 'M' || cmd === 'L' || cmd === 'T') {
                    const [nx, ny] = rot(args[0], args[1]);
                    args[0] = nx; args[1] = ny;
                } else if (cmd === 'H') {
                    const [nx, ny] = rot(args[0], curY);
                    cmd = 'L';
                    args = [nx, ny];
                } else if (cmd === 'V') {
                    const [nx, ny] = rot(curX, args[0]);
                    cmd = 'L';
                    args = [nx, ny];
                } else if (cmd === 'C') {
                    const [nx1, ny1] = rot(args[0], args[1]);
                    const [nx2, ny2] = rot(args[2], args[3]);
                    const [nx, ny] = rot(args[4], args[5]);
                    args[0] = nx1; args[1] = ny1;
                    args[2] = nx2; args[3] = ny2;
                    args[4] = nx; args[5] = ny;
                } else if (cmd === 'S' || cmd === 'Q') {
                    const [nx1, ny1] = rot(args[0], args[1]);
                    const [nx, ny] = rot(args[2], args[3]);
                    args[0] = nx1; args[1] = ny1;
                    args[2] = nx; args[3] = ny;
                } else if (cmd === 'A') {
                    const [nx, ny] = rot(args[5], args[6]);
                    args[2] = (args[2] + angle) % 360;
                    args[5] = nx; args[6] = ny;
                }

                // Update original coordinate pointers for the next iteration
                curX = x;
                curY = y;
                if (c.cmd === 'M') {
                    startX = x;
                    startY = y;
                }

                return { cmd, args };
            });
            showToast(`Rotated coordinates around center by ${angle}°`);
        } else if (mutationType === 'flip-h') {
            absolute = absolute.map(c => {
                const cmd = c.cmd;
                const args = [...c.args];
                if (cmd === 'M' || cmd === 'L' || cmd === 'T') {
                    args[0] = cx - (args[0] - cx);
                } else if (cmd === 'H') {
                    args[0] = cx - (args[0] - cx);
                } else if (cmd === 'C') {
                    args[0] = cx - (args[0] - cx);
                    args[2] = cx - (args[2] - cx);
                    args[4] = cx - (args[4] - cx);
                } else if (cmd === 'S' || cmd === 'Q') {
                    args[0] = cx - (args[0] - cx);
                    args[2] = cx - (args[2] - cx);
                } else if (cmd === 'A') {
                    args[5] = cx - (args[5] - cx);
                    args[4] = parseFloat(args[4]) === 1 ? 0 : 1; // Flip sweep flag
                }
                return { cmd, args };
            });
            showToast('Mirrored path shape horizontally');
        } else if (mutationType === 'flip-v') {
            absolute = absolute.map(c => {
                const cmd = c.cmd;
                const args = [...c.args];
                if (cmd === 'M' || cmd === 'L' || cmd === 'T') {
                    args[1] = cy - (args[1] - cy);
                } else if (cmd === 'V') {
                    args[0] = cy - (args[0] - cy);
                } else if (cmd === 'C') {
                    args[1] = cy - (args[1] - cy);
                    args[3] = cy - (args[3] - cy);
                    args[5] = cy - (args[5] - cy);
                } else if (cmd === 'S' || cmd === 'Q') {
                    args[1] = cy - (args[1] - cy);
                    args[3] = cy - (args[3] - cy);
                } else if (cmd === 'A') {
                    args[6] = cy - (args[6] - cy);
                    args[4] = parseFloat(args[4]) === 1 ? 0 : 1; // Flip sweep flag
                }
                return { cmd, args };
            });
            showToast('Mirrored path shape vertically');
        }

        // Output formatting: check if relative coordinates were originally preferred
        let outputCmds = absolute;
        if (coordinateMode.value === 'relative') {
            outputCmds = convertToRelative(absolute);
        }

        // Round decimals immediately to prevent long coordinate strings in input box
        inputText.value = formatPathString(outputCmds, decimalPrecision.value);
    } catch (e) {
        showToast('Error applying matrix transformation: ' + e.message, 'error');
    }
};

// ─── Default Sample Path ──────────────────────────────────────────────
const SAMPLE_CLOUD = "M 48 20 C 35.85 20 26 29.85 26 42 C 26 43.34 26.12 44.66 26.34 45.94 C 16.98 48.06 10 56.45 10 66.5 C 10 78.37 19.63 88 31.5 88 L 68.5 88 C 80.37 88 90 78.37 90 66.5 C 90 55.45 81.65 46.46 71.09 45.16 C 70.36 30.98 58.74 20 48 20 Z";

const loadSample = () => {
    inputText.value = SAMPLE_CLOUD;
    showToast('Loaded standard cloud vector coordinates');
};

const clearAll = () => {
    inputText.value = '';
    outputText.value = '';
    errorMessage.value = '';
    localStorage.removeItem('fm_svg_architect_input');
    showToast('Canvas & path cleared');
};

const copyOutput = async () => {
    if (!outputText.value) return;
    await navigator.clipboard.writeText(outputText.value);
    copied.value = true;
    showToast('Copied to clipboard');
    setTimeout(() => { copied.value = false; }, 2000);
};

const downloadOutput = () => {
    if (!outputText.value) return;

    let downloadContent = outputText.value;
    let ext = 'svg';
    let mime = 'image/svg+xml';

    if (exportFormat.value === 'raw') {
        const box = boundingBox.value;
        const w = Math.ceil(box.width);
        const h = Math.ceil(box.height);
        downloadContent = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${w} ${h}" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">\n  <path d="${outputText.value}" />\n</svg>`;
    } else if (exportFormat.value === 'react') {
        ext = 'jsx';
        mime = 'text/plain';
    } else if (exportFormat.value === 'vue') {
        ext = 'vue';
        mime = 'text/plain';
    }

    const url = URL.createObjectURL(new Blob([downloadContent], { type: mime }));
    const a = Object.assign(document.createElement('a'), {
        href: url,
        download: `fluxmedia_vector_${Date.now()}.${ext}`
    });
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    showToast(`Downloaded .${ext} file`);
};

// ─── Lifecycle ───────────────────────────────────────────────────────
let ldScript = null;
onMounted(() => {
    ldScript = document.createElement('script');
    ldScript.type = 'application/ld+json';
    ldScript.textContent = JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'WebApplication',
        name: 'FluxMedia SVG Path Architect & Optimizer',
        url: 'https://fluxmedia.space/tools/svg-architect',
        description: 'Advanced developer utility to parse, precision-round, visualize, and scale SVG path coordinates instantly.',
        applicationCategory: 'DeveloperApplication',
        operatingSystem: 'Web',
        offers: { '@type': 'Offer', price: '0', priceCurrency: 'USD' }
    });
    document.head.appendChild(ldScript);
    const saved = localStorage.getItem('fm_svg_architect_input');
    if (saved) {
        inputText.value = saved;
    } else {
        loadSample();
    }
});
onUnmounted(() => ldScript?.remove());

// Interactive Pan & Zoom Handlers
const startPan = (e) => {
    if (e.button !== 0) return; // Left-click drag only
    isPanning.value = true;
    startDragX.value = e.clientX;
    startDragY.value = e.clientY;
    startPanX.value = panX.value;
    startPanY.value = panY.value;
};

const onPan = (e) => {
    if (!isPanning.value || !svgRef.value) return;
    const rect = svgRef.value.getBoundingClientRect();
    const viewBoxParts = renderViewBox.value.split(' ').map(parseFloat);
    const vbWidth = viewBoxParts[2];
    const vbHeight = viewBoxParts[3];
    
    // Scale factor: viewBox units per screen pixel
    const scaleX = vbWidth / rect.width;
    const scaleY = vbHeight / rect.height;
    
    const dx = e.clientX - startDragX.value;
    const dy = e.clientY - startDragY.value;
    
    // Divide drag offsets by zoomScale so translation tracks perfectly with 1:1 speed at all zoom depths
    panX.value = startPanX.value + (dx * scaleX) / zoomScale.value;
    panY.value = startPanY.value + (dy * scaleY) / zoomScale.value;
};

const endPan = () => {
    isPanning.value = false;
};

const onWheel = (e) => {
    e.preventDefault();
    const zoomFactor = 1.15;
    let newScale = zoomScale.value;
    if (e.deltaY < 0) {
        newScale = Math.min(40, zoomScale.value * zoomFactor);
    } else {
        newScale = Math.max(0.1, zoomScale.value / zoomFactor);
    }
    zoomScale.value = newScale;
};

const resetView = () => {
    panX.value = 0;
    panY.value = 0;
    zoomScale.value = 1;
};

// Reset pan/zoom automatically whenever the input path coordinates or viewport viewBox modes update
watch([inputText, viewBoxMode], () => {
    resetView();
});
</script>

<template>
    <PublicLayout>
        <Head>
            <title>SVG Path Architect & Optimizer — Free & Local | FluxMedia</title>
            <meta name="description" content="Parse, precision-round, visualize, scale, and transform SVG coordinates instantly. 100% browser-safe — zero data uploads. Export to clean SVG, React JSX, or Vue." />
            <meta name="keywords" content="svg path optimizer, svg coordinate editor, svg compressor, round svg decimals, scale svg coordinates, flip svg path, react svg icon wrapper" />
            <meta name="robots" content="index, follow" />
            <link rel="canonical" href="https://fluxmedia.space/tools/svg-architect" />
        </Head>

        <!-- ░░ BACKGROUND ░░ -->
        <div class="fixed inset-0 -z-10 bg-[#07090f]" aria-hidden="true">
            <div class="absolute inset-0 opacity-[0.025]"
                 style="background-image:repeating-linear-gradient(0deg,transparent,transparent 2px,rgba(255,255,255,0.1) 2px,rgba(255,255,255,0.1) 3px);"></div>
            <div class="absolute inset-0 opacity-[0.04]"
                 style="background-image:linear-gradient(rgba(99,102,241,0.4) 1px,transparent 1px),linear-gradient(90deg,rgba(99,102,241,0.4) 1px,transparent 1px);background-size:48px 48px;"></div>
            <div class="absolute top-0 left-0 w-[600px] h-[400px] bg-indigo-600/10 blur-[160px] rounded-full -translate-x-1/3 -translate-y-1/3"></div>
            <div class="absolute bottom-0 right-0 w-[500px] h-[400px] bg-violet-600/8 blur-[140px] rounded-full translate-x-1/3 translate-y-1/3"></div>
        </div>

        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-8 relative z-10">

            <!-- ░░ HEADER ░░ -->
            <header class="mb-8">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <div class="space-y-1">
                        <div class="flex items-center gap-3 mb-3">
                            <span class="font-mono text-xs text-gray-500 tracking-wider uppercase">fluxmedia / tools /</span>
                            <span class="font-mono text-xs text-indigo-400 font-semibold tracking-wider">svg-architect</span>
                        </div>
                        <h1 class="font-mono text-2xl sm:text-3xl font-black text-white tracking-tight leading-none">
                            SVG Path <span class="text-indigo-400">Architect</span>
                            <span class="font-thin text-gray-500 ml-2 text-xl">& Optimizer</span>
                        </h1>
                        <p class="text-xs sm:text-sm text-gray-400 font-mono mt-1">
                            clean coordinates · precision scale · local editor · zero uploads
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button @click="loadSample" class="btn-ghost">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            Sample Cloud
                        </button>
                        <button @click="clearAll" class="btn-danger">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Reset Canvas
                        </button>
                    </div>
                </div>

                <!-- ░░ QUICK CONFIG BAR ░░ -->
                <div class="mt-5 flex flex-wrap items-center gap-6 bg-[#0d111c] border border-white/[0.06] rounded-xl px-5 py-4 shadow-lg shadow-black/25">
                    
                    <!-- Decimal Precision Selector -->
                    <div class="flex items-center gap-2.5">
                        <span class="font-mono text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-gray-400">Decimals</span>
                        <select v-model="decimalPrecision" class="config-select">
                            <option value="auto">auto (default)</option>
                            <option value="0">0 (integers)</option>
                            <option value="1">1 decimal</option>
                            <option value="2">2 decimals</option>
                            <option value="3">3 decimals</option>
                            <option value="4">4 decimals</option>
                        </select>
                    </div>

                    <div class="hidden sm:block w-px h-5 bg-white/[0.08]"></div>

                    <!-- Command Conversion Selector -->
                    <div class="flex items-center gap-2.5">
                        <span class="font-mono text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-gray-400">Commands</span>
                        <select v-model="coordinateMode" class="config-select">
                            <option value="no-change">no conversion</option>
                            <option value="absolute">force absolute (M, L, C)</option>
                            <option value="relative">force relative (m, l, c)</option>
                        </select>
                    </div>

                    <div class="hidden sm:block w-px h-5 bg-white/[0.08]"></div>

                    <!-- Output Format Wrap -->
                    <div class="flex items-center gap-2.5">
                        <span class="font-mono text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-gray-400">Export Wrap</span>
                        <select v-model="exportFormat" class="config-select">
                            <option value="raw">raw path (d="")</option>
                            <option value="svg">standard svg element</option>
                            <option value="react">react jsx component</option>
                            <option value="vue">vue 3 template</option>
                        </select>
                    </div>

                    <!-- Stats Strip -->
                    <div class="ml-auto flex items-center gap-6">
                        <div v-for="s in [
                            { label: 'cmds', val: cmdCount || '—' },
                            { label: 'raw size', val: rawSizeBytes ? `${rawSizeBytes} B` : '—' },
                            { label: 'opt size', val: optimizedSizeBytes ? `${optimizedSizeBytes} B` : '—' },
                            { label: 'speed', val: processingTime, accent: true }
                        ]" :key="s.label" class="text-center sm:text-left">
                            <p class="font-mono text-[9px] sm:text-[10px] font-semibold uppercase tracking-wider text-gray-500 leading-none mb-1">{{ s.label }}</p>
                            <p :class="['font-mono text-xs sm:text-sm font-bold', s.accent ? 'text-emerald-400' : 'text-gray-200']">{{ s.val }}</p>
                        </div>
                    </div>
                </div>
            </header>

            <!-- ░░ EDITOR LAYOUT ░░ -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 mt-6">

                <!-- LEFT COLUMN: INPUTS & GEOMETRIC MODIFIERS (Lg: 5 columns) -->
                <div class="lg:col-span-5 flex flex-col gap-5">
                    
                    <!-- INPUT PATH PANE -->
                    <div class="editor-pane flex flex-col min-h-[220px]">
                        <div class="pane-bar flex items-center justify-between">
                            <span class="font-mono text-xs text-gray-400 tracking-wider">
                                INPUT SVG STRING OR PATH
                            </span>
                            <span v-if="inputText" class="font-mono text-xs text-gray-500">
                                {{ cmdCount }} commands
                            </span>
                        </div>
                        <div class="relative flex-1">
                            <textarea
                                v-model="inputText"
                                spellcheck="false"
                                placeholder="Paste raw path d='...' or full standard <svg>...</svg> vector element"
                                class="editor-textarea h-full min-h-[160px] text-xs leading-relaxed"
                                aria-label="Input path code"
                            ></textarea>
                        </div>
                    </div>

                    <!-- MATRIX & GEOMETRIC MODIFIERS PANEL -->
                    <div class="editor-pane p-5 space-y-5">
                        <div class="border-b border-white/[0.06] pb-3">
                            <h3 class="font-mono text-xs font-bold text-indigo-400 uppercase tracking-widest">
                                Geometrical Transformations
                            </h3>
                            <p class="text-[10px] text-gray-400 mt-1 leading-relaxed">
                                Apply fast coordinate mathematics around path center coordinates
                            </p>
                        </div>

                        <!-- 1. Scale -->
                        <div class="grid grid-cols-12 items-center gap-3">
                            <div class="col-span-4">
                                <label class="font-mono text-xs text-gray-300 font-medium">Scale Uniform</label>
                            </div>
                            <div class="col-span-4">
                                <input v-model="scaleFactor" type="text" class="input-dark font-mono text-xs text-center py-1" placeholder="factor" />
                            </div>
                            <div class="col-span-4">
                                <button @click="applyMutation('scale')" class="btn-action w-full py-1.5 font-bold uppercase tracking-wider text-[10px]">
                                    Apply Scale
                                </button>
                            </div>
                        </div>

                        <!-- 2. Shift / Translate -->
                        <div class="grid grid-cols-12 items-center gap-3">
                            <div class="col-span-4">
                                <label class="font-mono text-xs text-gray-300 font-medium">Shift (X / Y)</label>
                            </div>
                            <div class="col-span-2">
                                <input v-model="shiftX" type="text" class="input-dark font-mono text-xs text-center py-1" placeholder="dX" title="Delta X" />
                            </div>
                            <div class="col-span-2">
                                <input v-model="shiftY" type="text" class="input-dark font-mono text-xs text-center py-1" placeholder="dY" title="Delta Y" />
                            </div>
                            <div class="col-span-4">
                                <button @click="applyMutation('translate')" class="btn-action w-full py-1.5 font-bold uppercase tracking-wider text-[10px]">
                                    Translate
                                </button>
                            </div>
                        </div>

                        <!-- 3. Rotation -->
                        <div class="grid grid-cols-12 items-center gap-3">
                            <div class="col-span-4">
                                <label class="font-mono text-xs text-gray-300 font-medium">Rotate Angle</label>
                            </div>
                            <div class="col-span-4 flex items-center gap-1.5">
                                <input v-model="rotateAngle" type="text" class="input-dark font-mono text-xs text-center py-1 w-full" placeholder="deg" />
                                <span class="font-mono text-xs text-gray-400">°</span>
                            </div>
                            <div class="col-span-4">
                                <button @click="applyMutation('rotate')" class="btn-action w-full py-1.5 font-bold uppercase tracking-wider text-[10px]">
                                    Rotate
                                </button>
                            </div>
                        </div>

                        <!-- 4. Mirroring & Flip triggers -->
                        <div class="border-t border-white/[0.04] pt-4 grid grid-cols-2 gap-3">
                            <button @click="applyMutation('flip-h')" class="btn-mirror py-2 font-bold uppercase tracking-wider text-[10px]">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                Flip Horizontal
                            </button>
                            <button @click="applyMutation('flip-v')" class="btn-mirror py-2 font-bold uppercase tracking-wider text-[10px]">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                                Flip Vertical
                            </button>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: GRID CANVAS & EXPORT PANEL (Lg: 7 columns) -->
                <div class="lg:col-span-7 flex flex-col gap-5">
                    
                    <!-- DYNAMIC INTERACTIVE GRID CANVAS VIEWPORT -->
                    <div class="editor-pane flex flex-col">
                        <!-- Canvas Header and togglers -->
                        <div class="pane-bar flex items-center justify-between">
                            <span class="font-mono text-xs text-gray-400 tracking-wider">
                                INTERACTIVE VIEWPORT CANVAS
                            </span>
                            
                            <!-- Checkbox filters -->
                            <div class="flex items-center gap-4 text-xs font-mono select-none">
                                <label class="flex items-center gap-1.5 cursor-pointer text-gray-400 hover:text-gray-200">
                                    <input type="checkbox" v-model="showGrid" class="checkbox-dark" />
                                    grid
                                </label>
                                <label class="flex items-center gap-1.5 cursor-pointer text-gray-400 hover:text-gray-200">
                                    <input type="checkbox" v-model="showAxes" class="checkbox-dark" />
                                    axes
                                </label>
                                <label class="flex items-center gap-1.5 cursor-pointer text-gray-400 hover:text-gray-200">
                                    <input type="checkbox" v-model="showBounds" class="checkbox-dark" />
                                    bounds
                                </label>
                                <select v-model="viewBoxMode" class="bg-transparent border-0 font-mono text-[11px] font-semibold text-indigo-400 py-0 px-2 cursor-pointer focus:ring-0 focus:outline-none">
                                    <option value="auto">auto zoom</option>
                                    <option value="24x24">24 × 24</option>
                                    <option value="100x100">100 × 100</option>
                                    <option value="512x512">512 × 512</option>
                                </select>
                            </div>
                        </div>

                        <!-- Checkered interactive canvas wrapper -->
                        <div 
                            class="checkered-canvas relative w-full h-[320px] sm:h-[380px] flex items-center justify-center overflow-hidden border-b border-white/[0.04]"
                            :class="isPanning ? 'cursor-grabbing' : 'cursor-grab'"
                            @mousedown="startPan"
                            @mousemove="onPan"
                            @mouseup="endPan"
                            @mouseleave="endPan"
                            @wheel.prevent="onWheel"
                        >
                            <!-- SVG viewport canvas -->
                            <svg
                                v-if="inputText && !errorMessage"
                                ref="svgRef"
                                class="w-full h-full max-h-full select-none"
                                :viewBox="renderViewBox"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <defs>
                                    <!-- Dynamic clean Grid Lines pattern -->
                                    <pattern id="canvas-grid" width="10" height="10" patternUnits="userSpaceOnUse">
                                        <path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="0.3" />
                                    </pattern>
                                </defs>

                                <!-- Zoom/Pan Inner Group -->
                                <g :transform="`translate(${panX}, ${panY}) scale(${zoomScale})`">
                                    <!-- Grid layer -->
                                    <rect v-if="showGrid" x="-2000" y="-2000" width="4000" height="4000" fill="url(#canvas-grid)" />

                                    <!-- Main X / Y Axes lines (with non-scaling stroke for constant pixel sharpness) -->
                                    <g v-if="showAxes" stroke="rgba(99,102,241,0.22)" stroke-width="0.5">
                                        <line x1="-2000" y1="0" x2="4000" y2="0" vector-effect="non-scaling-stroke" />
                                        <line x1="0" y1="-2000" x2="0" y2="4000" vector-effect="non-scaling-stroke" />
                                    </g>

                                    <!-- Bounding Box bounds enclosure (with non-scaling stroke) -->
                                    <rect
                                        v-if="showBounds"
                                        :x="boundingBox.minX"
                                        :y="boundingBox.minY"
                                        :width="boundingBox.width"
                                        :height="boundingBox.height"
                                        fill="none"
                                        stroke="rgba(244,114,182,0.3)"
                                        stroke-dasharray="2,2"
                                        stroke-width="0.5"
                                        vector-effect="non-scaling-stroke"
                                    />

                                    <!-- Rendered active SVG path shape itself (with non-scaling stroke) -->
                                    <path
                                        :d="extractPathData(inputText)"
                                        fill="rgba(129,140,248,0.18)"
                                        stroke="#818cf8"
                                        stroke-width="1.2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        vector-effect="non-scaling-stroke"
                                    />
                                </g>
                            </svg>

                            <!-- Floating Zoom level and Recenter/Reset viewport overlay controls -->
                            <div class="absolute top-3 right-3 flex items-center gap-2 select-none pointer-events-auto">
                                <span class="bg-[#090c14]/85 backdrop-blur-md border border-white/[0.08] font-mono text-[9px] uppercase tracking-wider text-indigo-300 px-2.5 py-1.5 rounded-lg">
                                    Zoom: {{ Math.round(zoomScale * 100) }}%
                                </span>
                                <button 
                                    v-if="panX !== 0 || panY !== 0 || zoomScale !== 1" 
                                    @click.stop="resetView" 
                                    class="bg-indigo-600/85 hover:bg-indigo-600 border border-indigo-400/40 text-white font-mono text-[9px] font-bold uppercase tracking-wider px-2.5 py-1.5 rounded-lg transition-all flex items-center gap-1 shadow-lg shadow-black/40 cursor-pointer"
                                    title="Recenter and reset view parameters"
                                >
                                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89M9 11l3-3 3 3m-3-3v12"/>
                                    </svg>
                                    Reset View
                                </button>
                            </div>

                            <!-- Bounding box visual labels overlay -->
                            <div v-if="showBounds && !errorMessage && inputText" class="absolute bottom-3 left-4 font-mono text-[9px] text-gray-500 space-y-0.5 pointer-events-none select-none bg-black/40 px-2.5 py-1.5 rounded border border-white/[0.04]">
                                <p class="text-[10px] text-gray-400 font-bold">Bounding Telemetry</p>
                                <p>Bounds: [X: {{ boundingBox.minX.toFixed(1) }} to {{ boundingBox.maxX.toFixed(1) }}] [Y: {{ boundingBox.minY.toFixed(1) }} to {{ boundingBox.maxY.toFixed(1) }}]</p>
                                <p>Dimensions: Width: {{ boundingBox.width.toFixed(1) }}px · Height: {{ boundingBox.height.toFixed(1) }}px</p>
                                <p>Center Coordinates: ({{ (boundingBox.minX + boundingBox.width / 2).toFixed(1) }}, {{ (boundingBox.minY + boundingBox.height / 2).toFixed(1) }})</p>
                            </div>

                            <!-- Error indicator -->
                            <div v-if="errorMessage" class="absolute inset-0 flex flex-col items-center justify-center p-6 bg-black/90">
                                <span class="text-rose-400 text-xs font-bold font-mono tracking-widest uppercase mb-2">SVG Parser Error</span>
                                <pre class="font-mono text-xs text-rose-300 text-center max-w-md whitespace-pre-wrap leading-relaxed">{{ errorMessage }}</pre>
                            </div>
                        </div>

                        <!-- EXPORT OUTPUT PANE -->
                        <div class="flex flex-col">
                            <div class="pane-bar flex items-center justify-between">
                                <span class="font-mono text-xs text-gray-400 tracking-wider">
                                    OPTIMIZED EXPORT OUTPUT
                                </span>
                                
                                <div v-if="outputText && !errorMessage" class="flex items-center gap-2">
                                    <button @click="copyOutput" :class="['pane-action', copied ? 'text-emerald-400 hover:text-emerald-300 border-emerald-500/20 bg-emerald-500/5' : '']">
                                        <svg v-if="copied" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        {{ copied ? 'copied' : 'copy code' }}
                                    </button>
                                    <button @click="downloadOutput" class="pane-action pane-action-accent">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                        download
                                    </button>
                                </div>
                            </div>

                            <div class="relative min-h-[140px] max-h-[160px] overflow-hidden">
                                <textarea
                                    readonly
                                    :value="outputText"
                                    class="editor-textarea text-emerald-300/90 text-xs bg-[#090b12]"
                                    aria-label="Optimized coordinates output"
                                    placeholder="Output renders here"
                                ></textarea>
                            </div>
                        </div>

                    </div>

                </div>

            </div>

            <!-- ░░ FOOTER INFO ░░ -->
            <footer class="mt-8 flex flex-wrap items-center justify-between gap-4 border-t border-white/[0.04] pt-5">
                <p class="font-mono text-xs text-gray-500">
                    Calculations occur client-side on float-coordinate indices inside browser memory. 100% offline security.
                </p>
                <div class="flex items-center gap-4">
                    <span class="font-mono text-xs text-gray-600 uppercase tracking-widest">FluxMedia · svg-architect v1.0</span>
                    <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                </div>
            </footer>

        </div>

        <!-- ░░ TOAST ░░ -->
        <Transition name="toast">
            <div v-if="toastVisible"
                 :class="['fixed bottom-6 right-6 z-50 flex items-center gap-3 px-4 py-3 rounded-xl border font-mono text-[11px] font-bold shadow-2xl backdrop-blur-md',
                     toastType === 'success'
                         ? 'bg-[#0a0f0a]/95 border-emerald-500/30 text-emerald-300'
                         : 'bg-[#0f0a0a]/95 border-rose-500/30 text-rose-300']">
                <span class="w-1.5 h-1.5 rounded-full animate-ping"
                      :class="toastType === 'success' ? 'bg-emerald-400' : 'bg-rose-400'"></span>
                {{ toastMessage }}
            </div>
        </Transition>
    </PublicLayout>
</template>

<style scoped>
/* Checkered grid pattern styling */
.checkered-canvas {
    background-color: #0b0e18;
    background-image: 
        linear-gradient(45deg, #090c14 25%, transparent 25%, transparent 75%, #090c14 75%),
        linear-gradient(45deg, #090c14 25%, transparent 25%, transparent 75%, #090c14 75%);
    background-size: 20px 20px;
    background-position: 0 0, 10px 10px;
}

/* ── Form Inputs ── */
.input-dark {
    @apply w-full bg-[#090c14] border border-white/[0.08] rounded-lg text-gray-200 focus:ring-1 focus:ring-indigo-500/40 focus:outline-none transition-all placeholder-gray-600 font-semibold px-2;
}

.checkbox-dark {
    @apply rounded bg-transparent border-white/[0.12] text-indigo-500 focus:ring-0 focus:ring-offset-0 focus:outline-none w-3.5 h-3.5 transition-colors cursor-pointer;
}

/* ── Action triggers ── */
.btn-action {
    @apply flex items-center justify-center rounded-lg bg-indigo-600/25 border border-indigo-500/30 text-indigo-300 hover:text-white hover:bg-indigo-600/45 hover:border-indigo-400 transition-all cursor-pointer shadow-sm;
}

.btn-mirror {
    @apply flex items-center justify-center gap-1.5 rounded-xl bg-white/[0.03] border border-white/[0.08] text-gray-300 hover:text-white hover:bg-white/[0.08] hover:border-white/25 transition-all cursor-pointer shadow-sm;
}

.btn-ghost {
    @apply flex items-center gap-1.5 px-4 py-2 rounded-xl bg-white/[0.04] border border-white/[0.08] text-gray-300 hover:text-white hover:border-white/20 hover:bg-white/[0.08] text-xs font-mono font-semibold uppercase tracking-wider transition-all active:scale-95 cursor-pointer shadow-sm;
}

.btn-danger {
    @apply flex items-center gap-1.5 px-4 py-2 rounded-xl bg-rose-500/[0.08] border border-rose-500/25 text-rose-300 hover:text-rose-100 hover:bg-rose-500/20 hover:border-rose-500/40 text-xs font-mono font-semibold uppercase tracking-wider transition-all active:scale-95 shadow-sm;
}

/* ── Select drop down menus ── */
.config-select {
    @apply bg-transparent border-0 text-gray-200 font-mono text-xs font-semibold focus:ring-0 focus:outline-none cursor-pointer hover:text-white transition-colors py-0 px-2;
}
.config-select option {
    background: #0d111c;
    color: #d1d5db;
}

/* ── Code Scroll editor frames ── */
.editor-pane {
    @apply rounded-2xl border border-white/[0.07] bg-[#0b0e18] overflow-hidden shadow-lg shadow-black/30;
}
.pane-bar {
    @apply flex items-center gap-2.5 px-4 py-3 border-b border-white/[0.06] bg-[#090c14] flex-shrink-0;
}
.pane-action {
    @apply flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-mono text-[11px] font-semibold uppercase tracking-wider text-gray-400 hover:text-white hover:bg-white/[0.06] transition-all active:scale-95 border border-transparent hover:border-white/[0.08];
}
.pane-action-accent {
    @apply text-indigo-400 hover:text-indigo-200 hover:bg-indigo-500/10 hover:border-indigo-500/20;
}

.editor-textarea {
    @apply w-full bg-transparent border-0 resize-none focus:outline-none focus:ring-0 font-mono text-sm text-gray-200 leading-relaxed p-4;
    tab-size: 4;
}
.editor-textarea::placeholder {
    color: rgba(255, 255, 255, 0.22);
    font-style: italic;
}

/* Custom scrollbars */
.editor-textarea::-webkit-scrollbar { width: 6px; height: 6px; }
.editor-textarea::-webkit-scrollbar-track { background: transparent; }
.editor-textarea::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 99px; }
.editor-textarea::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.18); }

/* ── Toast animation ── */
.toast-enter-active, .toast-leave-active { transition: all 0.25s cubic-bezier(0.16,1,0.3,1); }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(12px) scale(0.95); }
</style>
