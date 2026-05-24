<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import axios from 'axios';

const props = defineProps({
    dbSwatches: {
        type: Array,
        default: () => []
    }
});

const page = usePage();
const isLoggedIn = computed(() => !!page.props.auth?.user);
const isVerified = computed(() => !!page.props.auth?.user && !!page.props.auth.user.email_verified_at);

// --- Color State ---
const hue = ref(340); // 0-360
const saturation = ref(85); // 0-100
const lightness = ref(55); // 0-100
const alpha = ref(1.0); // 0.0-1.0

// Active view modes & clipboard notices
const activeTab = ref('harmonies'); // 'harmonies' or 'contrast'
const copiedFormat = ref(''); // formats HEX, RGB, HSL, etc.
const savedColors = ref([]);

// Custom Contrast Checker target colors
const contrastText = ref('#FFFFFF');
const contrastBg = ref('#DF3471'); // initial active HSL color in hex

// --- Color Math Utilities ---
const hslToRgb = (h, s, l) => {
    h /= 360;
    s /= 100;
    l /= 100;
    let r, g, b;
    if (s === 0) {
        r = g = b = l; // achromatic
    } else {
        const hue2rgb = (p, q, t) => {
            if (t < 0) t += 1;
            if (t > 1) t -= 1;
            if (t < 1/6) return p + (q - p) * 6 * t;
            if (t < 1/2) return q;
            if (t < 2/3) return p + (q - p) * (2/3 - t) * 6;
            return p;
        };
        const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
        const p = 2 * l - q;
        r = hue2rgb(p, q, h + 1/3);
        g = hue2rgb(p, q, h);
        b = hue2rgb(p, q, h - 1/3);
    }
    return {
        r: Math.round(r * 255),
        g: Math.round(g * 255),
        b: Math.round(b * 255)
    };
};

const rgbToHsl = (r, g, b) => {
    r /= 255;
    g /= 255;
    b /= 255;
    const max = Math.max(r, g, b);
    const min = Math.min(r, g, b);
    let h, s, l = (max + min) / 2;

    if (max === min) {
        h = s = 0; // achromatic
    } else {
        const d = max - min;
        s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
        switch (max) {
            case r: h = (g - b) / d + (g < b ? 6 : 0); break;
            case g: h = (b - r) / d + 2; break;
            case b: h = (r - g) / d + 4; break;
        }
        h /= 6;
    }
    return {
        h: Math.round(h * 360),
        s: Math.round(s * 100),
        l: Math.round(l * 100)
    };
};

const rgbToHex = (r, g, b) => {
    const toHex = x => {
        const hex = Math.max(0, Math.min(255, x)).toString(16);
        return hex.length === 1 ? '0' + hex : hex;
    };
    return `#${toHex(r)}${toHex(g)}${toHex(b)}`.toUpperCase();
};

const hexToRgb = (hex) => {
    const shorthandRegex = /^#?([a-f\d])([a-f\d])([a-f\d])$/i;
    const fullHex = hex.replace(shorthandRegex, (m, r, g, b) => r + r + g + g + b + b);
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(fullHex);
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
    } : null;
};

const rgbToHsv = (r, g, b) => {
    r /= 255;
    g /= 255;
    b /= 255;
    const max = Math.max(r, g, b);
    const min = Math.min(r, g, b);
    const d = max - min;
    let h, s, v = max;

    s = max === 0 ? 0 : d / max;

    if (max === min) {
        h = 0; // achromatic
    } else {
        switch (max) {
            case r: h = (g - b) / d + (g < b ? 6 : 0); break;
            case g: h = (b - r) / d + 2; break;
            case b: h = (r - g) / d + 4; break;
        }
        h /= 6;
    }
    return {
        h: Math.round(h * 360),
        s: Math.round(s * 100),
        v: Math.round(v * 100)
    };
};

const rgbToCmyk = (r, g, b) => {
    let c = 1 - (r / 255);
    let m = 1 - (g / 255);
    let y = 1 - (b / 255);
    let k = Math.min(c, Math.min(m, y));
    if (k === 1) {
        return { c: 0, m: 0, y: 0, k: 100 };
    }
    c = Math.round(((c - k) / (1 - k)) * 100);
    m = Math.round(((m - k) / (1 - k)) * 100);
    y = Math.round(((y - k) / (1 - k)) * 100);
    k = Math.round(k * 100);
    return { c, m, y, k };
};

const getColorName = (hex) => {
    const rgb = hexToRgb(hex) || { r: 0, g: 0, b: 0 };
    const hsl = rgbToHsl(rgb.r, rgb.g, rgb.b);
    const h = hsl.h;
    const s = hsl.s;
    const l = hsl.l;

    if (s < 8) {
        if (l < 15) return 'Ink Onyx';
        if (l > 85) return 'Snow White';
        if (l < 40) return 'Charcoal';
        if (l > 60) return 'Platinum';
        return 'Slate Grey';
    }

    let modifier = '';
    if (l > 80) {
        if (s > 70) modifier = 'Vibrant Light';
        else modifier = 'Soft Pastel';
    } else if (l < 25) {
        if (s > 75) modifier = 'Deep Velvet';
        else modifier = 'Shadowed';
    } else if (s > 80) {
        modifier = 'Electric';
    } else if (s < 30) {
        modifier = 'Muted';
    } else {
        modifier = 'Classic';
    }

    let base = '';
    if (h >= 345 || h < 15) {
        base = 'Crimson';
    } else if (h >= 15 && h < 45) {
        base = 'Amber Coral';
    } else if (h >= 45 && h < 70) {
        base = 'Saffron Gold';
    } else if (h >= 70 && h < 155) {
        base = 'Emerald Sage';
    } else if (h >= 155 && h < 185) {
        base = 'Turquoise Teal';
    } else if (h >= 185 && h < 255) {
        base = 'Sapphire Cobalt';
    } else if (h >= 255 && h < 290) {
        base = 'Amethyst Grape';
    } else if (h >= 290 && h < 345) {
        base = 'Magenta Orchid';
    }

    return `${modifier} ${base}`.trim();
};

// --- Computed Colors ---
const activeRgb = computed(() => hslToRgb(hue.value, saturation.value, lightness.value));
const activeHex = computed(() => rgbToHex(activeRgb.value.r, activeRgb.value.g, activeRgb.value.b));
const activeHslString = computed(() => `hsl(${hue.value}, ${saturation.value}%, ${lightness.value}%)`);
const activeHslaString = computed(() => `hsla(${hue.value}, ${saturation.value}%, ${lightness.value}%, ${alpha.value})`);
const activeRgbString = computed(() => `rgb(${activeRgb.value.r}, ${activeRgb.value.g}, ${activeRgb.value.b})`);
const activeRgbaString = computed(() => `rgba(${activeRgb.value.r}, ${activeRgb.value.g}, ${activeRgb.value.b}, ${alpha.value})`);

const activeHsv = computed(() => rgbToHsv(activeRgb.value.r, activeRgb.value.g, activeRgb.value.b));
const activeCmyk = computed(() => rgbToCmyk(activeRgb.value.r, activeRgb.value.g, activeRgb.value.b));

// Sync active background with active color in HEX format for contrast tab
watch(activeHex, (newHex) => {
    contrastBg.value = newHex;
});

// --- Dynamic Slider Background Gradients ---
const hueTrackGradient = computed(() => {
    return 'linear-gradient(to right, #ff0000 0%, #ffff00 17%, #00ff00 33%, #00ffff 50%, #0000ff 67%, #ff00ff 83%, #ff0000 100%)';
});

const satTrackGradient = computed(() => {
    const minColor = `hsl(${hue.value}, 0%, ${lightness.value}%)`;
    const maxColor = `hsl(${hue.value}, 100%, ${lightness.value}%)`;
    return `linear-gradient(to right, ${minColor}, ${maxColor})`;
});

const lightTrackGradient = computed(() => {
    const color = `hsl(${hue.value}, ${saturation.value}%, 50%)`;
    return `linear-gradient(to right, #000000 0%, ${color} 50%, #ffffff 100%)`;
});

const alphaTrackGradient = computed(() => {
    const baseColor = `rgb(${activeRgb.value.r}, ${activeRgb.value.g}, ${activeRgb.value.b})`;
    return `linear-gradient(to right, rgba(${activeRgb.value.r}, ${activeRgb.value.g}, ${activeRgb.value.b}, 0) 0%, ${baseColor} 100%)`;
});

// --- Color Sync from Typed Values ---
const updateFromHex = (e) => {
    const rgb = hexToRgb(e.target.value);
    if (rgb) {
        const hsl = rgbToHsl(rgb.r, rgb.g, rgb.b);
        hue.value = hsl.h;
        saturation.value = hsl.s;
        lightness.value = hsl.l;
    }
};

const updateFromRgb = (e) => {
    const parts = e.target.value.match(/\d+/g);
    if (parts && parts.length >= 3) {
        const r = Math.min(255, parseInt(parts[0]));
        const g = Math.min(255, parseInt(parts[1]));
        const b = Math.min(255, parseInt(parts[2]));
        const hsl = rgbToHsl(r, g, b);
        hue.value = hsl.h;
        saturation.value = hsl.s;
        lightness.value = hsl.l;
    }
};

const updateFromHsl = (e) => {
    const parts = e.target.value.match(/\d+/g);
    if (parts && parts.length >= 3) {
        hue.value = Math.min(360, parseInt(parts[0]));
        saturation.value = Math.min(100, parseInt(parts[1]));
        lightness.value = Math.min(100, parseInt(parts[2]));
    }
};

// --- Native EyeDropper API (Chrome/Edge/Opera support) ---
const isEyeDropperSupported = ref(false);
onMounted(() => {
    isEyeDropperSupported.value = 'EyeDropper' in window;
});

const triggerEyeDropper = async () => {
    if (!isEyeDropperSupported.value) return;
    try {
        const eyeDropper = new window.EyeDropper();
        const result = await eyeDropper.open();
        if (result?.sRGBHex) {
            const rgb = hexToRgb(result.sRGBHex);
            if (rgb) {
                const hsl = rgbToHsl(rgb.r, rgb.g, rgb.b);
                hue.value = hsl.h;
                saturation.value = hsl.s;
                lightness.value = hsl.l;
                alpha.value = 1.0;
            }
        }
    } catch (e) {
        console.warn('EyeDropper failed or was cancelled:', e);
    }
};

// --- WCAG Contrast Equations ---
const getLuminance = (r, g, b) => {
    const a = [r, g, b].map(v => {
        v /= 255;
        return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
    });
    return a[0] * 0.2126 + a[1] * 0.7152 + a[2] * 0.0722;
};

const getContrastRatio = (c1Hex, c2Hex) => {
    const c1Rgb = hexToRgb(c1Hex) || { r: 255, g: 255, b: 255 };
    const c2Rgb = hexToRgb(c2Hex) || { r: 0, g: 0, b: 0 };
    const l1 = getLuminance(c1Rgb.r, c1Rgb.g, c1Rgb.b);
    const l2 = getLuminance(c2Rgb.r, c2Rgb.g, c2Rgb.b);
    const brightest = Math.max(l1, l2);
    const darkest = Math.min(l1, l2);
    return (brightest + 0.05) / (darkest + 0.05);
};

const activeContrastRatio = computed(() => {
    return Math.round(getContrastRatio(contrastText.value, contrastBg.value) * 100) / 100;
});

// WCAG threshold calculations
const aaNormal = computed(() => activeContrastRatio.value >= 4.5);
const aaLarge = computed(() => activeContrastRatio.value >= 3.0);
const aaaNormal = computed(() => activeContrastRatio.value >= 7.0);
const aaaLarge = computed(() => activeContrastRatio.value >= 4.5);

const applyContrastColor = (target) => {
    if (target === 'text') {
        contrastText.value = activeHex.value;
    } else {
        contrastBg.value = activeHex.value;
    }
};

const swapContrastColors = () => {
    const temp = contrastText.value;
    contrastText.value = contrastBg.value;
    contrastBg.value = temp;
};

// --- Color Harmony Palettes Generator ---
const makeHslHex = (h, s, l) => {
    const rgb = hslToRgb((h + 360) % 360, Math.max(0, Math.min(100, s)), Math.max(0, Math.min(100, l)));
    return rgbToHex(rgb.r, rgb.g, rgb.b);
};

const harmonies = computed(() => {
    const h = hue.value;
    const s = saturation.value;
    const l = lightness.value;

    return [
        {
            name: 'Complementary',
            desc: 'Direct opposite color on the color wheel. Delivers extreme visual contrast.',
            colors: [
                { hex: activeHex.value, label: 'Base' },
                { hex: makeHslHex(h + 180, s, l), label: 'Complement' }
            ]
        },
        {
            name: 'Analogous',
            desc: 'Three colors adjacent to each other. Peaceful and highly harmonious.',
            colors: [
                { hex: makeHslHex(h - 30, s, l), label: 'Warm' },
                { hex: activeHex.value, label: 'Base' },
                { hex: makeHslHex(h + 30, s, l), label: 'Cool' }
            ]
        },
        {
            name: 'Monochromatic',
            desc: 'Same hue with varying levels of saturation and brightness.',
            colors: [
                { hex: makeHslHex(h, s - 30, l - 15), label: 'Dark' },
                { hex: makeHslHex(h, s - 15, l - 5), label: 'Medium' },
                { hex: activeHex.value, label: 'Base' },
                { hex: makeHslHex(h, s + 10, l + 15), label: 'Light' }
            ]
        },
        {
            name: 'Triadic',
            desc: 'Three colors spaced equally at 120-degree intervals.',
            colors: [
                { hex: activeHex.value, label: 'Base' },
                { hex: makeHslHex(h + 120, s, l), label: 'Triad 1' },
                { hex: makeHslHex(h + 240, s, l), label: 'Triad 2' }
            ]
        },
        {
            name: 'Tetradic',
            desc: 'Four colors arranged in a double-complementary rectangle structure.',
            colors: [
                { hex: activeHex.value, label: 'Base' },
                { hex: makeHslHex(h + 60, s, l), label: 'Color 2' },
                { hex: makeHslHex(h + 180, s, l), label: 'Color 3' },
                { hex: makeHslHex(h + 240, s, l), label: 'Color 4' }
            ]
        },
        {
            name: 'Split Complementary',
            desc: 'Base color paired with the two adjacent colors of its complement.',
            colors: [
                { hex: makeHslHex(h - 150, s, l), label: 'Split 1' },
                { hex: activeHex.value, label: 'Base' },
                { hex: makeHslHex(h + 150, s, l), label: 'Split 2' }
            ]
        }
    ];
});

// --- Saved Colors (LocalStorage Swatches) ---
const vFocus = {
    mounted: (el) => el.focus()
};

const editingIndex = ref(null);
const editingName = ref('');

const startEditingName = (index, currentName) => {
    editingIndex.value = index;
    editingName.value = currentName;
};

const saveCustomName = async (index) => {
    if (editingIndex.value === index) {
        const trimmed = editingName.value.trim();
        if (trimmed) {
            const oldName = savedColors.value[index].name;
            savedColors.value[index].name = trimmed;
            
            if (isVerified.value) {
                try {
                    const response = await axios.put('/tools/color-picker/swatches', {
                        hex: savedColors.value[index].hex,
                        name: trimmed
                    });
                    if (!response.data.success) {
                        savedColors.value[index].name = oldName; // Rollback
                    }
                } catch (err) {
                    console.error('Failed to rename swatch in DB:', err);
                    savedColors.value[index].name = oldName; // Rollback
                }
            } else {
                localStorage.setItem('fluxmedia_saved_colors', JSON.stringify(savedColors.value));
            }
        }
        editingIndex.value = null;
    }
};

const cancelEditingName = () => {
    editingIndex.value = null;
};

const saveActiveColor = async () => {
    const hexVal = activeHex.value;
    const exists = savedColors.value.some(c => c.hex.toUpperCase() === hexVal.toUpperCase());
    if (!exists) {
        const nameVal = getColorName(hexVal);
        
        if (isVerified.value) {
            try {
                const response = await axios.post('/tools/color-picker/swatches', {
                    hex: hexVal,
                    name: nameVal
                });
                if (response.data.success) {
                    savedColors.value.unshift(response.data.swatch);
                    if (savedColors.value.length > 24) {
                        savedColors.value.pop();
                    }
                }
            } catch (err) {
                console.error('Failed to save swatch to DB:', err);
            }
        } else {
            savedColors.value.unshift({
                hex: hexVal,
                name: nameVal
            });
            if (savedColors.value.length > 24) {
                savedColors.value.pop();
            }
            localStorage.setItem('fluxmedia_saved_colors', JSON.stringify(savedColors.value));
        }
    }
};

const removeSavedColor = async (hex) => {
    if (isVerified.value) {
        try {
            const response = await axios.delete('/tools/color-picker/swatches', {
                data: { hex: hex }
            });
            if (response.data.success) {
                savedColors.value = savedColors.value.filter(c => c.hex !== hex);
            }
        } catch (err) {
            console.error('Failed to delete swatch from DB:', err);
        }
    } else {
        savedColors.value = savedColors.value.filter(c => c.hex !== hex);
        localStorage.setItem('fluxmedia_saved_colors', JSON.stringify(savedColors.value));
    }
};

const selectSavedColor = (hex) => {
    const rgb = hexToRgb(hex);
    if (rgb) {
        const hsl = rgbToHsl(rgb.r, rgb.g, rgb.b);
        hue.value = hsl.h;
        saturation.value = hsl.s;
        lightness.value = hsl.l;
    }
};

const clearAllSwatches = async () => {
    if (isVerified.value) {
        try {
            const response = await axios.delete('/tools/color-picker/swatches/clear');
            if (response.data.success) {
                savedColors.value = [];
            }
        } catch (err) {
            console.error('Failed to clear swatches from DB:', err);
        }
    } else {
        savedColors.value = [];
        localStorage.removeItem('fluxmedia_saved_colors');
    }
};

// --- Copy Mechanics ---
const copyText = async (text, formatName) => {
    try {
        await navigator.clipboard.writeText(text);
        copiedFormat.value = formatName;
        setTimeout(() => {
            if (copiedFormat.value === formatName) copiedFormat.value = '';
        }, 1500);
    } catch (e) {
        console.error('Failed to copy to clipboard', e);
    }
};

// --- Programmatic JSON-LD Injection ---
const seoScripts = [];
onMounted(() => {
    // Read saved colors from localStorage
    const saved = localStorage.getItem('fluxmedia_saved_colors');
    if (isVerified.value) {
        if (saved) {
            try {
                const parsed = JSON.parse(saved);
                if (parsed.length > 0) {
                    const localSwatches = parsed.map(item => {
                        if (typeof item === 'string') {
                            return { hex: item, name: getColorName(item) };
                        }
                        return { hex: item.hex, name: item.name || getColorName(item.hex) };
                    });

                    // Bulk sync local storage swatches to DB
                    axios.post('/tools/color-picker/swatches/sync', { swatches: localSwatches })
                        .then(response => {
                            if (response.data.success) {
                                savedColors.value = response.data.swatches;
                                localStorage.removeItem('fluxmedia_saved_colors');
                            }
                        })
                        .catch(err => {
                            console.error('Failed to sync swatches to database:', err);
                            savedColors.value = props.dbSwatches;
                        });
                } else {
                    savedColors.value = props.dbSwatches;
                }
            } catch (e) {
                console.error('Failed to parse local colors:', e);
                savedColors.value = props.dbSwatches;
            }
        } else {
            savedColors.value = props.dbSwatches;
        }
    } else {
        // Read saved colors from localStorage (Guest user)
        if (saved) {
            try {
                const parsed = JSON.parse(saved);
                savedColors.value = parsed.map(item => {
                    if (typeof item === 'string') {
                        return { hex: item, name: getColorName(item) };
                    }
                    return { hex: item.hex, name: item.name || getColorName(item.hex) };
                });
            } catch (e) {
                console.error('Failed to parse saved colors:', e);
            }
        }
    }

    const schemas = [
        {
            '@context': 'https://schema.org',
            '@type': 'WebApplication',
            'name': 'Color Picker & Palette Designer — Free Online Tool',
            'url': 'https://fluxmedia.space/tools/color-picker',
            'description': 'Free online color picker and palette designer. Pick colors, convert between HEX, RGB, HSL, HSV, and CMYK formats. Calculate WCAG contrast ratios and generate complementary color harmonies.',
            'applicationCategory': 'DesignApplication',
            'operatingSystem': 'Web, Windows, macOS, Linux, Android, iOS',
            'offers': { '@type': 'Offer', 'price': '0', 'priceCurrency': 'USD' },
            'featureList': ['HEX/RGB/HSL/HSV/CMYK Converter', 'WCAG Contrast Checker', 'Color Harmony Generator (Complementary, Analogous, Triadic)', 'Screen Eyedropper API', 'Local & Cloud Swatch Saving'],
        },
        {
            '@context': 'https://schema.org',
            '@type': 'HowTo',
            'name': 'How to Pick Colors and Check Contrast Online',
            'description': 'Step-by-step guide to using FluxMedia Color Picker to select colors and check WCAG accessibility.',
            'totalTime': 'PT1M',
            'step': [
                { '@type': 'HowToStep', 'position': 1, 'name': 'Select a Color', 'text': 'Use the hue, saturation, and lightness sliders to pick a color, or type a HEX/RGB value directly.' },
                { '@type': 'HowToStep', 'position': 2, 'name': 'View Conversions', 'text': 'Instantly see and copy your color converted into HEX, RGB, HSL, HSV, and CMYK formats.' },
                { '@type': 'HowToStep', 'position': 3, 'name': 'Check WCAG Contrast', 'text': 'Click the WCAG Contrast tab to verify accessibility ratios for text and background colors.' },
                { '@type': 'HowToStep', 'position': 4, 'name': 'Generate Harmonies', 'text': 'Switch to the Color Harmonies tab to discover complementary, analogous, and triadic palettes based on your selected color.' },
            ],
        },
        {
            '@context': 'https://schema.org',
            '@type': 'FAQPage',
            'mainEntity': [
                { '@type': 'Question', 'name': 'Can I pick a color from my screen?', 'acceptedAnswer': { '@type': 'Answer', 'text': 'Yes! If you are using a supported browser (like Chrome or Edge), click the "Screen Eyedropper" button to select any color visible on your monitor.' } },
                { '@type': 'Question', 'name': 'Are my color swatches saved?', 'acceptedAnswer': { '@type': 'Answer', 'text': 'Yes. Guests have their swatches saved to browser local storage. Logged-in users will have their swatches securely synced to the cloud.' } },
                { '@type': 'Question', 'name': 'What is the WCAG Contrast Checker?', 'acceptedAnswer': { '@type': 'Answer', 'text': 'The WCAG checker calculates the visual contrast ratio between a text color and background color to ensure your design meets web accessibility standards (AA or AAA).' } },
            ],
        },
        {
            '@context': 'https://schema.org',
            '@type': 'BreadcrumbList',
            'itemListElement': [
                { '@type': 'ListItem', 'position': 1, 'name': 'Home', 'item': 'https://fluxmedia.space' },
                { '@type': 'ListItem', 'position': 2, 'name': 'Color Picker', 'item': 'https://fluxmedia.space/tools/color-picker' },
            ],
        },
    ];
    schemas.forEach(schema => {
        const s = document.createElement('script');
        s.type = 'application/ld+json';
        s.textContent = JSON.stringify(schema);
        document.head.appendChild(s);
        seoScripts.push(s);
    });
});

onUnmounted(() => seoScripts.forEach(s => s.remove()));
</script>

<template>
    <PublicLayout>
        <Head>
            <title>Free Online Color Picker & WCAG Contrast Checker | FluxMedia</title>

            <!-- Primary SEO -->
            <meta name="description" content="Pick, convert, and design professional palettes. Supports HEX, RGB, HSL, HSV, and CMYK formats. Built-in WCAG 2.1 contrast ratios and dynamic harmonizer." />
            <meta name="keywords" content="color picker, color converter, rgb to hex, hex to hsl, contrast checker, wcag ratio, color harmony, complementary colors, design palette generator, eyedropper tool" />
            <meta name="author" content="FluxMedia" />
            <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />
            <meta name="googlebot" content="index, follow, max-snippet:-1, max-image-preview:large" />
            <link rel="canonical" href="https://fluxmedia.space/tools/color-picker" />

            <!-- Open Graph / Facebook -->
            <meta property="og:type" content="website" />
            <meta property="og:title" content="Free Online Color Picker & WCAG Contrast Checker | FluxMedia" />
            <meta property="og:description" content="Pick colors, convert formats instantly, calculate WCAG contrast ratios, and design gorgeous color wheels." />
            <meta property="og:image" content="https://fluxmedia.space/assets/images/fluxmedia_main.webp" />
            <meta property="og:url" content="https://fluxmedia.space/tools/color-picker" />
            <meta property="og:site_name" content="FluxMedia" />

            <!-- Twitter Card -->
            <meta name="twitter:card" content="summary_large_image" />
            <meta name="twitter:site" content="@fluxmedia" />
            <meta name="twitter:creator" content="@fluxmedia" />
            <meta name="twitter:title" content="Free Online Color Picker & WCAG Contrast Checker" />
            <meta name="twitter:description" content="Pick colors, convert formats instantly, calculate WCAG contrast ratios, and design gorgeous color wheels." />
            <meta name="twitter:image" content="https://fluxmedia.space/assets/images/fluxmedia_main.webp" />
        </Head>

        <!-- Ambient Glow Backdrops -->
        <div class="fixed inset-0 overflow-hidden pointer-events-none -z-10 bg-[#0B0F19]">
            <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] rounded-full bg-rose-600/10 blur-[120px]"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] rounded-full bg-pink-600/10 blur-[120px]"></div>
            <div class="absolute top-[35%] left-[55%] w-[30%] h-[30%] rounded-full bg-indigo-600/5 blur-[100px]"></div>
        </div>

        <!-- Header -->
        <div class="relative pt-8 pb-4 text-center px-4 overflow-hidden z-10">
            <div class="absolute inset-0 bg-[url('/assets/images/grid-pattern.svg')] opacity-5 mask-image-gradient-b"></div>
            <div class="relative max-w-3xl mx-auto space-y-3">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-gray-800/50 border border-gray-700/50 backdrop-blur-md">
                    <span class="w-2 h-2 rounded-full bg-rose-400 animate-pulse"></span>
                    <span class="text-xs font-semibold text-gray-300 uppercase tracking-wider">Designers & Developers</span>
                </div>
                
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white leading-tight">
                    Color Picker & <span class="bg-gradient-to-r from-rose-400 via-pink-400 to-indigo-400 bg-clip-text text-transparent">Palette Architect</span>
                </h1>
                <p class="text-xs text-gray-400 max-w-xl mx-auto leading-relaxed">
                    A beautiful, client-side toolkit to pick, convert, analyze contrast ratios, and compose stunning color systems instantly inside your browser.
                </p>
            </div>
        </div>

        <!-- Tool Container -->
        <div class="mx-auto max-w-7xl px-4 pb-24 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                
                <!-- LEFT COLUMN: Picker, sliders & conversions (7 cols) -->
                <div class="lg:col-span-7 space-y-8">
                    <div class="bg-[#121826]/80 backdrop-blur-xl rounded-3xl border border-gray-800 shadow-2xl p-6 sm:p-8 space-y-6">
                        
                        <!-- Top header control with EyeDropper -->
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-black text-white uppercase tracking-wider flex items-center gap-2">
                                <span class="w-2 h-4 bg-rose-500 rounded-sm"></span>
                                Selector Canvas
                            </h3>

                            <button 
                                v-if="isEyeDropperSupported"
                                @click="triggerEyeDropper"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-300 text-xs font-bold hover:bg-rose-500/20 active:scale-95 transition-all"
                                title="Pick any color from your entire screen"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 21a3 3 0 003-3v-4.5a3 3 0 00-3-3h-1.5V9a3 3 0 00-3-3H12V4.5A1.5 1.5 0 0010.5 3h-3A1.5 1.5 0 006 4.5V6H4.5A1.5 1.5 0 003 7.5v3A1.5 1.5 0 004.5 12H6v1.5a3 3 0 003 3h3v1.5a3 3 0 003 3h4.5z"/>
                                </svg>
                                Screen Eyedropper
                            </button>
                        </div>

                        <!-- Massive Swatch Display -->
                        <div class="relative rounded-2xl border border-gray-800 overflow-hidden shadow-inner aspect-[21/9] flex items-center justify-center bg-[url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2216%22 height=%2216%22 viewBox=%220 0 8 8%22><rect width=%224%22 height=%224%22 fill=%22%23242B3D%22/><rect x=%224%22 y=%224%22 width=%224%22 height=%224%22 fill=%22%23242B3D%22/><rect x=%224%22 width=%224%22 height=%224%22 fill=%22%23121826%22/><rect y=%224%22 width=%224%22 height=%224%22 fill=%22%23121826%22/></svg>')]">
                            <div 
                                class="absolute inset-0 transition-colors duration-150"
                                :style="{ backgroundColor: activeHslaString }"
                            ></div>
                            
                            <div class="relative z-10 flex flex-col items-center gap-2 bg-black/60 backdrop-blur-md rounded-2xl px-6 py-3 border border-white/10 select-none">
                                <span class="text-2xl font-black text-white tracking-wider font-mono">{{ activeHex }}</span>
                                <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">{{ activeHslaString }}</span>
                            </div>
                        </div>

                        <!-- Sliders Panel -->
                        <div class="space-y-5">
                            
                            <!-- Hue Slider -->
                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-xs font-bold text-gray-400 uppercase tracking-widest">
                                    <span>Hue</span>
                                    <span class="text-rose-400 font-mono">{{ hue }}°</span>
                                </div>
                                <div class="relative h-6 rounded-xl overflow-hidden border border-gray-850">
                                    <div class="absolute inset-0" :style="{ background: hueTrackGradient }"></div>
                                    <input 
                                        type="range" min="0" max="360" v-model="hue"
                                        class="absolute inset-0 w-full h-full opacity-0 cursor-ew-resize"
                                    />
                                    <div 
                                        class="absolute top-0 bottom-0 w-2 bg-white border border-black shadow-md rounded-md pointer-events-none -translate-x-1/2 transition-all duration-75"
                                        :style="{ left: `${(hue / 360) * 100}%` }"
                                    ></div>
                                </div>
                            </div>

                            <!-- Saturation Slider -->
                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-xs font-bold text-gray-400 uppercase tracking-widest">
                                    <span>Saturation</span>
                                    <span class="text-pink-400 font-mono">{{ saturation }}%</span>
                                </div>
                                <div class="relative h-6 rounded-xl overflow-hidden border border-gray-850">
                                    <div class="absolute inset-0" :style="{ background: satTrackGradient }"></div>
                                    <input 
                                        type="range" min="0" max="100" v-model="saturation"
                                        class="absolute inset-0 w-full h-full opacity-0 cursor-ew-resize"
                                    />
                                    <div 
                                        class="absolute top-0 bottom-0 w-2 bg-white border border-black shadow-md rounded-md pointer-events-none -translate-x-1/2 transition-all duration-75"
                                        :style="{ left: `${saturation}%` }"
                                    ></div>
                                </div>
                            </div>

                            <!-- Lightness Slider -->
                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-xs font-bold text-gray-400 uppercase tracking-widest">
                                    <span>Lightness</span>
                                    <span class="text-purple-400 font-mono">{{ lightness }}%</span>
                                </div>
                                <div class="relative h-6 rounded-xl overflow-hidden border border-gray-850">
                                    <div class="absolute inset-0" :style="{ background: lightTrackGradient }"></div>
                                    <input 
                                        type="range" min="0" max="100" v-model="lightness"
                                        class="absolute inset-0 w-full h-full opacity-0 cursor-ew-resize"
                                    />
                                    <div 
                                        class="absolute top-0 bottom-0 w-2 bg-white border border-black shadow-md rounded-md pointer-events-none -translate-x-1/2 transition-all duration-75"
                                        :style="{ left: `${lightness}%` }"
                                    ></div>
                                </div>
                            </div>

                            <!-- Opacity Slider -->
                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-xs font-bold text-gray-400 uppercase tracking-widest">
                                    <span>Opacity (Alpha)</span>
                                    <span class="text-indigo-400 font-mono">{{ Math.round(alpha * 100) }}%</span>
                                </div>
                                <div class="relative h-6 rounded-xl overflow-hidden border border-gray-850 bg-[url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2216%22 height=%2216%22 viewBox=%220 0 8 8%22><rect width=%224%22 height=%224%22 fill=%22%23242B3D%22/><rect x=%224%22 y=%224%22 width=%224%22 height=%224%22 fill=%22%23242B3D%22/><rect x=%224%22 width=%224%22 height=%224%22 fill=%22%23121826%22/><rect y=%224%22 width=%224%22 height=%224%22 fill=%22%23121826%22/></svg>')]">
                                    <div class="absolute inset-0" :style="{ background: alphaTrackGradient }"></div>
                                    <input 
                                        type="range" min="0" max="1" step="0.01" v-model="alpha"
                                        class="absolute inset-0 w-full h-full opacity-0 cursor-ew-resize"
                                    />
                                    <div 
                                        class="absolute top-0 bottom-0 w-2 bg-white border border-black shadow-md rounded-md pointer-events-none -translate-x-1/2 transition-all duration-75"
                                        :style="{ left: `${alpha * 100}%` }"
                                    ></div>
                                </div>
                            </div>

                        </div>

                        <!-- Action bar (Save swatch) -->
                        <div class="pt-4 border-t border-gray-800/80 flex items-center justify-between">
                            <span class="text-xs text-gray-500 font-bold uppercase tracking-wider">Persist to collection</span>
                            <button
                                @click="saveActiveColor"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-rose-500 hover:bg-rose-600 text-white text-xs font-black uppercase tracking-wider shadow-lg shadow-rose-500/10 active:scale-95 transition-all"
                            >
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                </svg>
                                Add to Swatches
                            </button>
                        </div>

                    </div>

                    <!-- Color Conversion Grid -->
                    <div class="bg-[#121826]/80 backdrop-blur-xl rounded-3xl border border-gray-800 shadow-2xl p-6 sm:p-8 space-y-5">
                        <h3 class="text-sm font-black text-white uppercase tracking-wider flex items-center gap-2">
                            <span class="w-2 h-4 bg-pink-500 rounded-sm"></span>
                            Multi-Format Converter
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            
                            <!-- HEX -->
                            <div class="bg-[#0B0F19] rounded-2xl border border-gray-800/60 p-4 flex flex-col justify-between group hover:border-gray-700 transition-colors">
                                <span class="text-[10px] font-black text-rose-400 uppercase tracking-widest mb-2">Hex Format</span>
                                <div class="flex items-center justify-between gap-3">
                                    <input 
                                        type="text" :value="activeHex" @input="updateFromHex"
                                        class="bg-transparent border-0 p-0 text-white font-mono font-bold text-sm w-full focus:ring-0 focus:outline-none"
                                    />
                                    <button 
                                        @click="copyText(activeHex, 'HEX')"
                                        class="text-gray-500 hover:text-white transition-colors p-1"
                                        title="Copy HEX Code"
                                    >
                                        <svg v-if="copiedFormat === 'HEX'" class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- RGB -->
                            <div class="bg-[#0B0F19] rounded-2xl border border-gray-800/60 p-4 flex flex-col justify-between group hover:border-gray-700 transition-colors">
                                <span class="text-[10px] font-black text-pink-400 uppercase tracking-widest mb-2">RGB Format</span>
                                <div class="flex items-center justify-between gap-3">
                                    <input 
                                        type="text" :value="activeRgbString" @input="updateFromRgb"
                                        class="bg-transparent border-0 p-0 text-white font-mono font-bold text-sm w-full focus:ring-0 focus:outline-none"
                                    />
                                    <button 
                                        @click="copyText(activeRgbaString, 'RGB')"
                                        class="text-gray-500 hover:text-white transition-colors p-1"
                                        title="Copy RGBA CSS"
                                    >
                                        <svg v-if="copiedFormat === 'RGB'" class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- HSL -->
                            <div class="bg-[#0B0F19] rounded-2xl border border-gray-800/60 p-4 flex flex-col justify-between group hover:border-gray-700 transition-colors">
                                <span class="text-[10px] font-black text-purple-400 uppercase tracking-widest mb-2">HSL Format</span>
                                <div class="flex items-center justify-between gap-3">
                                    <input 
                                        type="text" :value="activeHslString" @input="updateFromHsl"
                                        class="bg-transparent border-0 p-0 text-white font-mono font-bold text-sm w-full focus:ring-0 focus:outline-none"
                                    />
                                    <button 
                                        @click="copyText(activeHslaString, 'HSL')"
                                        class="text-gray-500 hover:text-white transition-colors p-1"
                                        title="Copy HSLA CSS"
                                    >
                                        <svg v-if="copiedFormat === 'HSL'" class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- HSV -->
                            <div class="bg-[#0B0F19] rounded-2xl border border-gray-800/60 p-4 flex flex-col justify-between group hover:border-gray-700 transition-colors">
                                <span class="text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-2">HSV Format</span>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-white font-mono font-bold text-sm select-all">hsv({{ activeHsv.h }}, {{ activeHsv.s }}%, {{ activeHsv.v }}%)</span>
                                    <button 
                                        @click="copyText(`hsv(${activeHsv.h}, ${activeHsv.s}%, ${activeHsv.v}%)`, 'HSV')"
                                        class="text-gray-500 hover:text-white transition-colors p-1"
                                        title="Copy HSV Code"
                                    >
                                        <svg v-if="copiedFormat === 'HSV'" class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- CMYK -->
                            <div class="sm:col-span-2 bg-[#0B0F19] rounded-2xl border border-gray-800/60 p-4 flex flex-col justify-between group hover:border-gray-700 transition-colors">
                                <span class="text-[10px] font-black text-rose-300 uppercase tracking-widest mb-2">CMYK Format (Print Engine)</span>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-white font-mono font-bold text-sm select-all">cmyk({{ activeCmyk.c }}%, {{ activeCmyk.m }}%, {{ activeCmyk.y }}%, {{ activeCmyk.k }}%)</span>
                                    <button 
                                        @click="copyText(`cmyk(${activeCmyk.c}%, ${activeCmyk.m}%, ${activeCmyk.y}%, ${activeCmyk.k}%)`, 'CMYK')"
                                        class="text-gray-500 hover:text-white transition-colors p-1"
                                        title="Copy CMYK Code"
                                    >
                                        <svg v-if="copiedFormat === 'CMYK'" class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>

                <!-- RIGHT COLUMN: Palettes, WCAG Contrast, Favorites Swatches (5 cols) -->
                <div class="lg:col-span-5 space-y-8">
                    
                    <!-- Main operational panels -->                    <div class="bg-[#121826]/80 backdrop-blur-xl rounded-3xl border border-gray-800 shadow-2xl flex flex-col">
                        
                        <!-- Tabs -->
                        <div class="flex border-b border-gray-800/80 bg-[#0B0F19] rounded-t-3xl">
                            <button 
                                @click="activeTab = 'harmonies'"
                                :class="[
                                    'flex-1 text-center py-4 text-xs font-black uppercase tracking-wider border-b-2 transition-all rounded-tl-3xl',
                                    activeTab === 'harmonies' ? 'border-rose-500 text-rose-400 bg-white/[0.01]' : 'border-transparent text-gray-500 hover:text-gray-300'
                                ]"
                            >
                                Color Harmonies
                            </button>
                            <button 
                                @click="activeTab = 'contrast'"
                                :class="[
                                    'flex-1 text-center py-4 text-xs font-black uppercase tracking-wider border-b-2 transition-all rounded-tr-3xl',
                                    activeTab === 'contrast' ? 'border-rose-500 text-rose-400 bg-white/[0.01]' : 'border-transparent text-gray-500 hover:text-gray-300'
                                ]"
                            >
                                WCAG Contrast
                            </button>
                        </div>

                        <!-- Tab Content -->
                        <div class="p-6 sm:p-8">
                            
                            <!-- TAB: HARMONIES -->
                            <div v-if="activeTab === 'harmonies'" class="space-y-3">
                                <p class="text-[11px] text-gray-400 leading-relaxed font-semibold">
                                    Below are dynamically computed color harmony sets generated from your active tone. Hover any chip to preview details; click to copy its HEX value.
                                </p>

                                <div class="block">
                                    <div 
                                        v-for="harmony in harmonies" 
                                        :key="harmony.name"
                                        class="flex items-center justify-between bg-[#0B0F19]/40 border border-gray-850/60 rounded-2xl p-3 hover:border-gray-800 transition-all mb-4 last:mb-0"
                                    >
                                        <div class="flex flex-col">
                                            <span class="text-xs font-black text-white uppercase tracking-wider">{{ harmony.name }}</span>
                                            <span class="text-[9px] text-gray-500 font-bold tracking-tight mt-0.5" :title="harmony.desc">
                                                {{ harmony.colors.length }} Colors
                                            </span>
                                        </div>

                                        <!-- Row of tiny color chips -->
                                        <div class="flex items-center gap-2">
                                            <div 
                                                v-for="chip in harmony.colors" 
                                                :key="chip.hex + chip.label"
                                                class="relative group"
                                            >
                                                <!-- Tiny color dot -->
                                                <div 
                                                    @click="copyText(chip.hex, chip.hex)"
                                                    class="w-7 h-7 rounded-full border border-gray-850 cursor-pointer shadow-md active:scale-90 transition-transform duration-150 hover:scale-115 hover:border-white/20"
                                                    :style="{ backgroundColor: chip.hex }"
                                                ></div>

                                                <!-- Hover Tooltip Modal -->
                                                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-28 bg-[#0B0F19] border border-gray-800 rounded-xl p-2.5 shadow-2xl opacity-0 scale-90 group-hover:opacity-100 group-hover:scale-100 pointer-events-none transition-all duration-200 z-50 flex flex-col items-center text-center">
                                                    <!-- Solid Swatch Preview inside Popup -->
                                                    <div class="w-full h-8 rounded-lg mb-2 border border-white/10 shadow-inner" :style="{ backgroundColor: chip.hex }"></div>
                                                    
                                                    <span class="text-[9px] font-black text-rose-400 uppercase tracking-tight">{{ chip.label }}</span>
                                                    <span class="text-xs font-bold text-white font-mono mt-0.5">{{ copiedFormat === chip.hex ? 'Copied!' : chip.hex }}</span>
                                                    <span class="text-[8px] text-gray-500 font-bold uppercase tracking-wider mt-0.5">Click to copy</span>
                                                    
                                                    <!-- Arrow -->
                                                    <div class="absolute top-full left-1/2 -translate-x-1/2 w-1.5 h-1.5 bg-[#0B0F19] border-r border-b border-gray-800 rotate-45 -mt-1"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB: WCAG CONTRAST -->
                            <div v-else class="space-y-4">
                                <p class="text-[11px] text-gray-400 leading-relaxed font-semibold">
                                    Validate accessibly compliant foreground-background color pairings against Web Content Accessibility Guidelines (WCAG 2.1).
                                </p>

                                <!-- Live WCAG Test Window -->
                                <div 
                                    class="rounded-2xl border border-gray-800 p-4 flex flex-col justify-center text-center space-y-2 h-28 transition-colors relative overflow-hidden"
                                    :style="{ backgroundColor: contrastBg, color: contrastText }"
                                >
                                    <div class="relative z-10 space-y-1">
                                        <h4 class="text-lg font-black uppercase tracking-wide">Sample Header</h4>
                                        <p class="text-xs font-semibold max-w-xs mx-auto leading-relaxed">
                                            This is normal body text (14pt / 1rem). Ensure it meets the accessibility thresholds below.
                                        </p>
                                    </div>
                                </div>

                                <!-- Custom Inputs -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="space-y-1.5">
                                        <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Text (FG)</span>
                                        <div class="flex items-center gap-2 bg-[#0B0F19] border border-gray-800 rounded-xl px-3 py-2">
                                            <input type="color" v-model="contrastText" class="w-5 h-5 rounded-md border-0 p-0 cursor-pointer bg-transparent" />
                                            <input type="text" v-model="contrastText" class="bg-transparent border-0 p-0 text-white font-mono font-bold text-xs w-full focus:ring-0 focus:outline-none uppercase" />
                                        </div>
                                    </div>
                                    <div class="space-y-1.5">
                                        <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Background (BG)</span>
                                        <div class="flex items-center gap-2 bg-[#0B0F19] border border-gray-800 rounded-xl px-3 py-2">
                                            <input type="color" v-model="contrastBg" class="w-5 h-5 rounded-md border-0 p-0 cursor-pointer bg-transparent" />
                                            <input type="text" v-model="contrastBg" class="bg-transparent border-0 p-0 text-white font-mono font-bold text-xs w-full focus:ring-0 focus:outline-none uppercase" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Utility operational buttons -->
                                <div class="flex items-center justify-between gap-3">
                                    <button 
                                        @click="swapContrastColors"
                                        class="flex-1 text-center py-2 bg-gray-850 hover:bg-gray-800 border border-gray-800 text-gray-300 text-xs font-black uppercase tracking-wider rounded-2xl active:scale-95 transition-all"
                                    >
                                        Swap Colors
                                    </button>
                                    
                                    <button 
                                        @click="applyContrastColor('bg')"
                                        class="flex-1 text-center py-2 bg-rose-500/10 border border-rose-500/20 hover:bg-rose-500/20 text-rose-300 text-xs font-black uppercase tracking-wider rounded-2xl active:scale-95 transition-all"
                                    >
                                        Use Picked as BG
                                    </button>
                                </div>

                                <!-- Compliance Report Cards -->
                                <div class="pt-3 border-t border-gray-850/80 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-black text-white uppercase tracking-wider">Contrast Ratio</span>
                                        <span class="text-lg font-mono font-black text-white bg-[#0B0F19] border border-gray-800/80 px-3 py-1 rounded-lg">
                                            {{ activeContrastRatio }} : 1
                                        </span>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        
                                        <!-- AA Standard -->
                                        <div class="bg-[#0B0F19] border border-gray-850 p-3 rounded-xl flex flex-col justify-between space-y-1.5">
                                            <span class="text-[9px] font-black text-gray-500 uppercase tracking-widest">AA Standard</span>
                                            
                                            <div class="space-y-0.5">
                                                <div class="flex items-center justify-between text-[11px] font-bold">
                                                    <span class="text-gray-300">Normal</span>
                                                    <span :class="aaNormal ? 'text-emerald-400' : 'text-rose-500'">{{ aaNormal ? 'PASS' : 'FAIL' }}</span>
                                                </div>
                                                <div class="flex items-center justify-between text-[11px] font-bold">
                                                    <span class="text-gray-300">Large (>18pt)</span>
                                                    <span :class="aaLarge ? 'text-emerald-400' : 'text-rose-500'">{{ aaLarge ? 'PASS' : 'FAIL' }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- AAA Standard -->
                                        <div class="bg-[#0B0F19] border border-gray-850 p-3 rounded-xl flex flex-col justify-between space-y-1.5">
                                            <span class="text-[9px] font-black text-gray-500 uppercase tracking-widest">AAA Standard</span>
                                            
                                            <div class="space-y-0.5">
                                                <div class="flex items-center justify-between text-[11px] font-bold">
                                                    <span class="text-gray-300">Normal</span>
                                                    <span :class="aaaNormal ? 'text-emerald-400' : 'text-rose-500'">{{ aaaNormal ? 'PASS' : 'FAIL' }}</span>
                                                </div>
                                                <div class="flex items-center justify-between text-[11px] font-bold">
                                                    <span class="text-gray-300">Large (>18pt)</span>
                                                    <span :class="aaaLarge ? 'text-emerald-400' : 'text-rose-500'">{{ aaaLarge ? 'PASS' : 'FAIL' }}</span>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- SAVED SWATCHES -->
                    <div class="bg-[#121826]/80 backdrop-blur-xl rounded-3xl border border-gray-800 shadow-2xl p-5 sm:p-6 space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-gray-850/80">
                            <h3 class="text-sm font-black text-white uppercase tracking-wider flex items-center gap-2">
                                <span class="w-2 h-4 bg-indigo-500 rounded-sm"></span>
                                Collection Swatches
                            </h3>
                            <div class="flex items-center gap-3">
                                <button 
                                    v-if="savedColors.length > 0"
                                    @click="clearAllSwatches"
                                    class="text-[9px] font-black text-rose-400 hover:text-rose-350 uppercase tracking-wider transition-colors active:scale-95 duration-150"
                                >
                                    Clear All
                                </button>
                                <span class="text-[9px] text-gray-500 font-bold uppercase tracking-wider">{{ savedColors.length }}/24 Saved</span>
                            </div>
                        </div>

                        <div v-if="savedColors.length === 0" class="py-8 text-center text-gray-500 text-xs font-semibold leading-relaxed">
                            No custom colors saved in this session yet. Click "Add to Swatches" above to build your active palette.
                        </div>

                        <div v-else class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <div 
                                v-for="(color, index) in savedColors" 
                                :key="color.hex"
                                class="group relative bg-[#0B0F19]/40 border border-gray-850 rounded-2xl overflow-hidden cursor-pointer hover:border-gray-700 active:scale-95 transition-all duration-300 shadow-md hover:shadow-xl flex flex-col"
                                @click="selectSavedColor(color.hex)"
                            >
                                <!-- Pantone style Color Block (Top portion) -->
                                <div 
                                    class="w-full h-24 relative overflow-hidden transition-all duration-300 group-hover:brightness-105"
                                    :style="{ backgroundColor: color.hex }"
                                >
                                    <!-- Overlay glass shimmer effect -->
                                    <div class="absolute inset-0 bg-gradient-to-tr from-white/0 via-white/5 to-white/10 opacity-60"></div>
                                    
                                    <!-- Action buttons showing on hover (Load, Copy, and Delete Bin) -->
                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-1.5 z-10">
                                        <button 
                                            @click.stop="selectSavedColor(color.hex)"
                                            class="px-2 py-1 rounded bg-white/10 hover:bg-white/20 border border-white/20 text-white text-[9px] font-black uppercase tracking-wider transition-all"
                                            title="Load into picker"
                                        >
                                            Load
                                        </button>
                                        <button 
                                            @click.stop="copyText(color.hex, color.hex)"
                                            class="p-1 rounded bg-[#3B82F6]/20 hover:bg-[#3B82F6]/40 border border-[#3B82F6]/30 text-blue-200 hover:text-white transition-all"
                                            title="Copy HEX code"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                                            </svg>
                                        </button>
                                        <button 
                                            @click.stop="removeSavedColor(color.hex)"
                                            class="p-1 rounded bg-[#EF4444]/20 hover:bg-[#EF4444]/40 border border-[#EF4444]/30 text-rose-200 hover:text-white transition-all"
                                            title="Delete swatch"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Details info (Bottom portion) -->
                                <div class="p-2.5 bg-[#121826]/30 border-t border-gray-850 flex flex-col justify-between flex-grow">
                                    <div class="flex flex-col">
                                        <span class="text-[8px] font-black text-rose-400 uppercase tracking-widest">Color {{ savedColors.length - index }}</span>
                                        
                                        <!-- Rename name field (Themed input box) -->
                                        <div v-if="editingIndex === index" class="mt-0.5">
                                            <input 
                                                v-model="editingName"
                                                v-focus
                                                @click.stop
                                                @keyup.enter="saveCustomName(index)"
                                                @keyup.esc="cancelEditingName"
                                                @blur="saveCustomName(index)"
                                                class="w-full text-[10px] font-extrabold text-white bg-[#0B0F19] border border-gray-850 rounded px-1.5 py-0.5 focus:outline-none focus:border-indigo-500/80 focus:ring-1 focus:ring-indigo-500/30"
                                            />
                                        </div>
                                        <div 
                                            v-else 
                                            @click.stop="startEditingName(index, color.name)"
                                            class="flex items-center gap-1 mt-0.5 cursor-text group/name hover:text-white"
                                            title="Click to rename"
                                        >
                                            <span class="text-[10px] font-extrabold text-gray-300 truncate flex-grow">
                                                {{ color.name || 'Unnamed Color' }}
                                            </span>
                                            <svg class="w-2.5 h-2.5 text-gray-500 opacity-0 group-hover/name:opacity-100 hover:text-indigo-400 transition-opacity flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between mt-2 pt-1.5 border-t border-gray-850/50">
                                        <span class="text-[10px] font-black text-white font-mono tracking-tight select-all">{{ color.hex }}</span>
                                        <span v-if="copiedFormat === color.hex" class="text-[8px] font-black text-emerald-400">Copied!</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </PublicLayout>
</template>

<style scoped>
/* Custom scrollbar styling */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.02);
    border-radius: 9999px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.12);
    border-radius: 9999px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.22);
}

/* Range Slider Custom Styling */
input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 12px;
    height: 28px;
    border-radius: 6px;
    background: #ffffff;
    cursor: ew-resize;
    border: 1px solid #1e293b;
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
}

input[type="range"]::-moz-range-thumb {
    width: 12px;
    height: 28px;
    border-radius: 6px;
    background: #ffffff;
    cursor: ew-resize;
    border: 1px solid #1e293b;
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
}
</style>
