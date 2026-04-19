#!/usr/bin/env node
/**
 * Génère 11 bannières SVG militantes pour les articles HLM de Nantes.
 *
 * DEUX MODES selon que `image` est fourni ou non :
 *  - Avec image (URL photo)  → style Contre-Attaque : photo de fond
 *    assombrie + titre blanc et accent jaune en overlay
 *  - Sans image              → fallback typographique : dégradé
 *    noir→rouge, hachures, titre blanc + accent jaune
 *
 * Pour passer en mode photo sur un quartier : renseigner `image` dans
 * BANNERS ci-dessous (URL absolue, format JPG/PNG/WEBP), puis relancer
 * `node _generate_banners.js`.
 */
const fs = require('fs');
const path = require('path');

const OUTPUT_DIR = 'content/media';

// image: URL absolue d'une photo (si fournie) — sinon rendu typographique
// category_label : texte du badge rouge en haut (défaut "INFOS LOCALE")
const BANNERS = [
  // 11 quartiers HLM (badge INFOS LOCALE)
  { slug: 'bellevue',         title: 'QUARTIER BELLEVUE',     accent: 'ZONE SOUS CONTRÔLE',          image: null },
  { slug: 'malakoff',         title: 'QUARTIER MALAKOFF',     accent: 'ON DÉMOLIT POUR CHASSER',     image: null },
  { slug: 'dervallieres',     title: 'QUARTIER DERVALLIÈRES', accent: 'L\'ÉTAT SE RETIRE',           image: null },
  { slug: 'clos-toreau',      title: 'QUARTIER CLOS TOREAU',  accent: 'PUNAISES, MÉPRIS',            image: null },
  { slug: 'bottiere-pin-sec', title: 'BOTTIÈRE — PIN SEC',    accent: '240 M€ CONTRE LES HABITANTS', image: null },
  { slug: 'breil',            title: 'QUARTIER BREIL',        accent: 'LA BAC CHASSE LES ADOS',      image: null },
  { slug: 'bout-des-landes',  title: 'BOUT DES LANDES',       accent: 'ENCLAVÉS, OUBLIÉS',           image: null },
  { slug: 'port-boyer',       title: 'QUARTIER PORT BOYER',   accent: 'MARCHANDS DE SOMMEIL',        image: null },
  { slug: 'halveque',         title: 'QUARTIER HALVÊQUE',     accent: 'CE QU\'ON NE VOUS MONTRE PAS',image: null },
  { slug: 'ranzay',           title: 'QUARTIER RANZAY',       accent: 'VILLE-DORTOIR',               image: null },
  { slug: 'pilotiere',        title: 'QUARTIER PILOTIÈRE',    accent: 'ON N\'ATTEND PLUS, ON FAIT',  image: null },

  // 4 actualités urgentes — mode photo (buildSvgWithPhoto) :
  // image_path = chemin LOCAL → sera embarquée en base64 dans le SVG
  // (nécessaire car WP sert les SVG via <img>, qui bloque les fetch
  //  d'images externes pour raisons de sécurité. Embed = self-contained.)
  { slug: 'actualite-soudan',
    title: 'SOUDAN',                   accent: 'LA GUERRE OUBLIÉE',
    category_label: 'INTERNATIONAL · GUERRE',
    image_path: 'content/media/soudan-guerre-oubliee.jpg' },
  { slug: 'actualite-loi-immigration',
    title: 'LOI IMMIGRATION 2026',     accent: 'AIDER DEVIENT UN CRIME',
    category_label: 'FRANCE · POLITIQUE',
    image_path: 'content/media/loi-immigration-2026.jpg' },
  { slug: 'actualite-videosurveillance',
    title: '150 CAMÉRAS IA',           accent: 'LE PANOPTIQUE ARRIVE',
    category_label: 'NANTES · BELLEVUE',
    image_path: 'content/media/nantes-videosurveillance.jpg' },
  { slug: 'actualite-1er-mai',
    title: '1ER MAI 2026',             accent: 'TOUT LIER, TOUT BLOQUER',
    category_label: 'LUTTES · GRÈVE GÉNÉRALE',
    image_path: 'content/media/1er-mai-2026.jpg' },
];

// Helper : lit un fichier local et le convertit en data URI base64
function embedAsDataUri(filePath) {
  const abs = path.resolve(__dirname, filePath);
  if (!fs.existsSync(abs)) {
    console.warn('⚠ Image introuvable :', abs);
    return '';
  }
  const buf = fs.readFileSync(abs);
  const ext = path.extname(filePath).slice(1).toLowerCase();
  const mime = ext === 'jpg' ? 'jpeg' : ext;
  return `data:image/${mime};base64,${buf.toString('base64')}`;
}

function xmlEscape(s) {
  return String(s)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&apos;');
}

function fitFontSize(text, baseSize, maxWidth, avgCharWidthRatio = 0.55) {
  const estimatedWidth = text.length * baseSize * avgCharWidthRatio;
  if (estimatedWidth <= maxWidth) return baseSize;
  return Math.floor(baseSize * (maxWidth / estimatedWidth));
}

/**
 * Rendu avec photo de fond (style Contre-Attaque).
 */
function buildSvgWithPhoto({ title, accent, image, category_label }) {
  const W = 1600, H = 900;
  const label = category_label || 'INFOS LOCALE';
  const titleSize = fitFontSize(title, 130, 1400);
  const accentSize = fitFontSize(accent, 80, 1400);
  // Badge : largeur dynamique selon la longueur du label
  const labelWidth = Math.max(240, label.length * 11 + 40);

  return `<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 ${W} ${H}" width="${W}" height="${H}" role="img" aria-label="${xmlEscape(title)} — ${xmlEscape(accent)}">
  <title>${xmlEscape(title)} — ${xmlEscape(accent)}</title>
  <defs>
    <clipPath id="clip"><rect width="${W}" height="${H}"/></clipPath>
    <linearGradient id="overlay" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0%" stop-color="rgba(15,15,15,0.55)"/>
      <stop offset="50%" stop-color="rgba(15,15,15,0.25)"/>
      <stop offset="100%" stop-color="rgba(15,15,15,0.95)"/>
    </linearGradient>
    <filter id="textshadow" x="-10%" y="-10%" width="120%" height="120%">
      <feDropShadow dx="0" dy="6" stdDeviation="12" flood-color="#000" flood-opacity="0.75"/>
    </filter>
  </defs>

  <!-- Photo de fond (préserve l'aspect via slice) -->
  <g clip-path="url(#clip)">
    <image href="${xmlEscape(image)}" xlink:href="${xmlEscape(image)}"
           x="0" y="0" width="${W}" height="${H}"
           preserveAspectRatio="xMidYMid slice"/>
  </g>

  <!-- Dégradé assombrisant pour lisibilité -->
  <rect width="${W}" height="${H}" fill="url(#overlay)"/>

  <!-- Cadre rouge -->
  <rect x="0" y="0" width="${W}" height="8" fill="#e02810"/>
  <rect x="0" y="${H - 8}" width="${W}" height="8" fill="#e02810"/>

  <!-- Badge catégorie -->
  <g transform="translate(60, 60)">
    <rect x="0" y="0" width="${labelWidth}" height="44" fill="#e02810" rx="2"/>
    <text x="${labelWidth / 2}" y="31" font-family="Inter, system-ui, sans-serif" font-weight="800"
          font-size="16" letter-spacing="4" fill="#ffffff" text-anchor="middle">${xmlEscape(label)}</text>
  </g>

  <!-- Titre principal (bas, plein largeur) -->
  <text x="${W / 2}" y="${H - 220}"
        font-family="Fraunces, Georgia, serif"
        font-weight="900"
        font-size="${titleSize}"
        letter-spacing="-2"
        fill="#ffffff"
        text-anchor="middle"
        dominant-baseline="middle"
        filter="url(#textshadow)">${xmlEscape(title)}</text>

  <!-- Accent jaune -->
  <text x="${W / 2}" y="${H - 110}"
        font-family="Fraunces, Georgia, serif"
        font-weight="900"
        font-style="italic"
        font-size="${accentSize}"
        letter-spacing="1"
        fill="#ffcb05"
        text-anchor="middle"
        dominant-baseline="middle"
        filter="url(#textshadow)">${xmlEscape(accent)}</text>

  <!-- Signature -->
  <text x="${W / 2}" y="${H - 40}"
        font-family="Inter, system-ui, sans-serif"
        font-weight="600"
        font-size="18"
        letter-spacing="6"
        fill="#a9a595"
        text-anchor="middle">— QUARTIERLIBRE.ORG —</text>
</svg>
`;
}

/**
 * Rendu typographique (fallback) — utilisé si pas d'image fournie.
 */
function buildSvgTypographic({ title, accent, category_label }) {
  const W = 1600, H = 900;
  const label = category_label || 'INFOS LOCALE';
  const labelWidth = Math.max(260, label.length * 11 + 40);
  const titleSize = fitFontSize(title, 110, 1400);
  const accentSize = fitFontSize(accent, 75, 1400);

  return `<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${W} ${H}" width="${W}" height="${H}" role="img" aria-label="${xmlEscape(title)} — ${xmlEscape(accent)}">
  <title>${xmlEscape(title)} — ${xmlEscape(accent)}</title>
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0%" stop-color="#0f0f0f"/>
      <stop offset="70%" stop-color="#1a0606"/>
      <stop offset="100%" stop-color="#3d0a0a"/>
    </linearGradient>
    <radialGradient id="glow" cx="50%" cy="55%" r="60%">
      <stop offset="0%" stop-color="#a51d08" stop-opacity="0.35"/>
      <stop offset="60%" stop-color="#3d0a0a" stop-opacity="0"/>
    </radialGradient>
    <pattern id="hatches" patternUnits="userSpaceOnUse" width="18" height="18" patternTransform="rotate(45)">
      <line x1="0" y1="0" x2="0" y2="18" stroke="#ffcb05" stroke-width="0.8" stroke-opacity="0.06"/>
    </pattern>
    <filter id="textshadow" x="-5%" y="-5%" width="110%" height="110%">
      <feDropShadow dx="0" dy="4" stdDeviation="8" flood-color="#000" flood-opacity="0.5"/>
    </filter>
  </defs>

  <rect width="${W}" height="${H}" fill="url(#bg)"/>
  <rect width="${W}" height="${H}" fill="url(#hatches)"/>
  <rect width="${W}" height="${H}" fill="url(#glow)"/>

  <rect x="0" y="0" width="${W}" height="8" fill="#e02810"/>
  <rect x="0" y="${H - 8}" width="${W}" height="8" fill="#e02810"/>

  <g transform="translate(80, 80)">
    <rect x="0" y="0" width="${labelWidth}" height="44" fill="#e02810" rx="2"/>
    <text x="${labelWidth / 2}" y="31" font-family="Inter, system-ui, sans-serif" font-weight="800"
          font-size="16" letter-spacing="4" fill="#ffffff" text-anchor="middle">${xmlEscape(label)}</text>
  </g>

  <text x="${W / 2}" y="${H / 2 - 40}"
        font-family="Fraunces, Georgia, serif" font-weight="900" font-size="${titleSize}"
        letter-spacing="-2" fill="#ffffff" text-anchor="middle" dominant-baseline="middle"
        filter="url(#textshadow)">${xmlEscape(title)}</text>

  <line x1="${W / 2 - 80}" y1="${H / 2 + 20}" x2="${W / 2 + 80}" y2="${H / 2 + 20}"
        stroke="#e02810" stroke-width="4"/>

  <text x="${W / 2}" y="${H / 2 + 100}"
        font-family="Fraunces, Georgia, serif" font-weight="900" font-style="italic"
        font-size="${accentSize}" letter-spacing="1" fill="#ffcb05"
        text-anchor="middle" dominant-baseline="middle"
        filter="url(#textshadow)">${xmlEscape(accent)}</text>

  <text x="${W / 2}" y="${H - 60}"
        font-family="Inter, system-ui, sans-serif" font-weight="600" font-size="20"
        letter-spacing="6" fill="#a9a595" text-anchor="middle">— QUARTIERLIBRE.ORG —</text>
</svg>
`;
}

function main() {
  if (!fs.existsSync(OUTPUT_DIR)) fs.mkdirSync(OUTPUT_DIR, { recursive: true });

  let ok = 0, photo = 0, typo = 0;
  for (const banner of BANNERS) {
    // Si image_path est fourni, on l'embed en base64 dans banner.image
    if (banner.image_path && !banner.image) {
      banner.image = embedAsDataUri(banner.image_path);
    }
    const svg = banner.image
      ? buildSvgWithPhoto(banner)
      : buildSvgTypographic(banner);
    // Les actualités gardent leur slug tel quel ; les quartiers héritent du préfixe historique.
    const filename = banner.slug.startsWith('actualite-')
      ? `${banner.slug}.svg`
      : `quartier-${banner.slug}.svg`;
    const outPath = path.join(OUTPUT_DIR, filename);
    fs.writeFileSync(outPath, svg, 'utf-8');
    const mode = banner.image ? '📷 photo' : '🎨 typo ';
    console.log(`  ${mode}  ${filename}  (${svg.length} chars)`);
    ok++;
    if (banner.image) photo++; else typo++;
  }
  console.log(`\n${ok} bannières (${photo} photo, ${typo} typographique) dans ${OUTPUT_DIR}/`);
}

try { main(); } catch (e) {
  console.error('ERREUR:', e.message);
  process.exit(1);
}
