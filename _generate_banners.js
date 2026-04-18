#!/usr/bin/env node
/**
 * Génère 11 bannières SVG militantes pour les articles HLM de Nantes.
 * Sortie : content/media/quartier-{slug}.svg (1600×900, ~5 kB chacun)
 *
 * Design : affiche militante — fond noir→rouge, hachures 45° légères,
 * serif lourd blanc pour le titre, jaune pour le mot-accent.
 */
const fs = require('fs');
const path = require('path');

const OUTPUT_DIR = 'content/media';

const BANNERS = [
  { slug: 'bellevue',          title: 'QUARTIER BELLEVUE',        accent: 'ZONE SOUS CONTRÔLE' },
  { slug: 'malakoff',          title: 'QUARTIER MALAKOFF',        accent: 'ON DÉMOLIT POUR CHASSER' },
  { slug: 'dervallieres',      title: 'QUARTIER DERVALLIÈRES',    accent: 'L\'ÉTAT SE RETIRE' },
  { slug: 'clos-toreau',       title: 'QUARTIER CLOS TOREAU',     accent: 'PUNAISES, MÉPRIS' },
  { slug: 'bottiere-pin-sec',  title: 'BOTTIÈRE — PIN SEC',       accent: '240 M€ CONTRE LES HABITANTS' },
  { slug: 'breil',             title: 'QUARTIER BREIL',           accent: 'LA BAC CHASSE LES ADOS' },
  { slug: 'bout-des-landes',   title: 'BOUT DES LANDES',          accent: 'ENCLAVÉS, OUBLIÉS' },
  { slug: 'port-boyer',        title: 'QUARTIER PORT BOYER',      accent: 'MARCHANDS DE SOMMEIL' },
  { slug: 'halveque',          title: 'QUARTIER HALVÊQUE',        accent: 'CE QU\'ON NE VOUS MONTRE PAS' },
  { slug: 'ranzay',             title: 'QUARTIER RANZAY',         accent: 'VILLE-DORTOIR' },
  { slug: 'pilotiere',         title: 'QUARTIER PILOTIÈRE',       accent: 'ON N\'ATTEND PLUS, ON FAIT' },
];

/**
 * Échappe les caractères XML critiques.
 */
function xmlEscape(s) {
  return String(s)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&apos;');
}

/**
 * Calcule une taille de police adaptée à la longueur du texte, pour
 * que ça tienne dans la largeur utile (~1400px).
 */
function fitFontSize(text, baseSize, maxWidth, avgCharWidthRatio = 0.55) {
  const estimatedWidth = text.length * baseSize * avgCharWidthRatio;
  if (estimatedWidth <= maxWidth) return baseSize;
  return Math.floor(baseSize * (maxWidth / estimatedWidth));
}

function buildSvg({ title, accent }) {
  const W = 1600, H = 900;
  const titleSize = fitFontSize(title, 110, 1400);
  const accentSize = fitFontSize(accent, 75, 1400);

  const titleEsc = xmlEscape(title);
  const accentEsc = xmlEscape(accent);

  return `<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${W} ${H}" width="${W}" height="${H}" role="img" aria-label="${titleEsc} — ${accentEsc}">
  <title>${titleEsc} — ${accentEsc}</title>
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

  <!-- Fond -->
  <rect width="${W}" height="${H}" fill="url(#bg)"/>
  <rect width="${W}" height="${H}" fill="url(#hatches)"/>
  <rect width="${W}" height="${H}" fill="url(#glow)"/>

  <!-- Cadre rouge fin en haut -->
  <rect x="0" y="0" width="${W}" height="8" fill="#e02810"/>
  <rect x="0" y="${H - 8}" width="${W}" height="8" fill="#e02810"/>

  <!-- Badge catégorie (en haut à gauche) -->
  <g transform="translate(80, 80)">
    <rect x="0" y="0" width="260" height="44" fill="#e02810" rx="2"/>
    <text x="130" y="31"
          font-family="Inter, system-ui, sans-serif"
          font-weight="800"
          font-size="16"
          letter-spacing="4"
          fill="#ffffff"
          text-anchor="middle">INFOS LOCALE</text>
  </g>

  <!-- Titre principal -->
  <text x="${W / 2}" y="${H / 2 - 40}"
        font-family="Fraunces, Georgia, 'Times New Roman', serif"
        font-weight="900"
        font-size="${titleSize}"
        letter-spacing="-2"
        fill="#ffffff"
        text-anchor="middle"
        dominant-baseline="middle"
        filter="url(#textshadow)">${titleEsc}</text>

  <!-- Filet rouge de séparation -->
  <line x1="${W / 2 - 80}" y1="${H / 2 + 20}" x2="${W / 2 + 80}" y2="${H / 2 + 20}"
        stroke="#e02810" stroke-width="4"/>

  <!-- Accent en jaune -->
  <text x="${W / 2}" y="${H / 2 + 100}"
        font-family="Fraunces, Georgia, serif"
        font-weight="900"
        font-style="italic"
        font-size="${accentSize}"
        letter-spacing="1"
        fill="#ffcb05"
        text-anchor="middle"
        dominant-baseline="middle"
        filter="url(#textshadow)">${accentEsc}</text>

  <!-- Signature en bas -->
  <text x="${W / 2}" y="${H - 60}"
        font-family="Inter, system-ui, sans-serif"
        font-weight="600"
        font-size="20"
        letter-spacing="6"
        fill="#a9a595"
        text-anchor="middle"
        text-transform="uppercase">— QUARTIERLIBRE.ORG —</text>
</svg>
`;
}

function main() {
  if (!fs.existsSync(OUTPUT_DIR)) {
    fs.mkdirSync(OUTPUT_DIR, { recursive: true });
  }

  let ok = 0;
  for (const banner of BANNERS) {
    const svg = buildSvg(banner);
    const filename = `quartier-${banner.slug}.svg`;
    const outPath = path.join(OUTPUT_DIR, filename);
    fs.writeFileSync(outPath, svg, 'utf-8');
    console.log(`  OK ${filename}  (${svg.length} chars)`);
    ok++;
  }
  console.log(`\n${ok} bannières SVG générées dans ${OUTPUT_DIR}/`);
}

try {
  main();
} catch (e) {
  console.error('ERREUR:', e.message);
  process.exit(1);
}
