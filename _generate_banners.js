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

  // 4 actualités — style Contre-Attaque avec PALETTES variées
  // selon le sujet/la photo (jaune fluo, cyan dystopie, ocre désert,
  // rouge militant). Chaque champ `palette` définit :
  //   - accent   : couleur des mots **surlignés** dans le préambule
  //   - title_1  : couleur de la 1ère ligne du titre
  //   - title_2  : couleur de la 2ème ligne du titre (impact)
  //   - tag_bg   : fond du badge
  //   - tag_fg   : texte du badge
  //   - tint     : teinte du voile overlay (rgba ou hex)

  // SOUDAN — palette désert/sang : ocre sable + rouge-sang sur fond
  // brûlé. Teinte chaude qui évoque la poussière et la catastrophe.
  { slug: 'actualite-soudan',
    ca_style: true,
    tag: 'GUERRE OUBLIÉE',
    preamble: [
      'Depuis avril 2023, le Soudan s\'effondre.',
      '**150 000 morts**. **13 millions de déplacés**.',
      'Famine déclarée au Darfour, massacres ethniques à El Fasher.',
      'Les médias occidentaux regardent ailleurs.',
      'La France arme **les complices émiratis**.',
    ],
    title_lines: ['SOUDAN :', 'LE SILENCE COMPLICE'],
    image_path: 'content/media/soudan-guerre-oubliee.jpg',
    palette: {
      accent:  '#f5a830',   // ocre sable
      title_1: '#ffffff',
      title_2: '#e63824',   // rouge-sang
      tag_bg:  '#c71f05',
      tag_fg:  '#ffffff',
      tint:    '#2a1005',   // brun-brûlé
      tint_opacity: 0.82,
    } },

  // LOI IMMIGRATION — palette acier/jaune fluo : blocs institutionnels
  // oppressifs. Jaune criard pour choquer, comme un avertissement routier.
  { slug: 'actualite-loi-immigration',
    ca_style: true,
    tag: 'DÉLIT DE SOLIDARITÉ',
    preamble: [
      'Le 8 avril 2026, l\'Assemblée a voté la',
      '**52ème loi immigration depuis 1980**.',
      'Désormais : héberger un sans-papier =',
      '**5 ans de prison. 75 000 € d\'amende**.',
      'Les associations entrent en résistance.',
    ],
    title_lines: ['LOI 2026 :', 'AIDER DEVIENT UN CRIME'],
    image_path: 'content/media/loi-immigration-2026.jpg',
    palette: {
      accent:  '#ffcb05',   // jaune fluo avertissement
      title_1: '#ffffff',
      title_2: '#ffcb05',
      tag_bg:  '#0f0f0f',
      tag_fg:  '#ffcb05',   // tag jaune sur noir
      tint:    '#0a1628',   // bleu nuit acier
      tint_opacity: 0.82,
    } },

  // VIDÉOSURVEILLANCE — palette cyan dystopie : bleu électrique néon
  // sur fond très sombre. Évoque les écrans, l'IA, le panoptique.
  { slug: 'actualite-videosurveillance',
    ca_style: true,
    tag: 'PANOPTIQUE DE CLASSE',
    preamble: [
      'Vote discret du conseil municipal, 3 avril.',
      '**3,2 millions d\'euros** pour **150 caméras IA**',
      'installées exclusivement dans les quartiers populaires.',
      'Reconnaissance faciale prête à être activée.',
      'Bellevue, Malakoff, Breil : **territoires sous surveillance**.',
    ],
    title_lines: ['NANTES : 150 CAMÉRAS IA', 'DANS LES QUARTIERS'],
    image_path: 'content/media/nantes-videosurveillance.jpg',
    palette: {
      accent:  '#00d4ff',   // cyan néon
      title_1: '#ffffff',
      title_2: '#00d4ff',
      tag_bg:  '#e02810',
      tag_fg:  '#ffffff',
      tint:    '#050a15',   // noir très profond
      tint_opacity: 0.88,
    } },

  // 1ER MAI — palette rouge/jaune classique : drapeaux, syndicats,
  // solidarité. Rouge chaud + jaune lumineux = tradition ouvrière.
  { slug: 'actualite-1er-mai',
    ca_style: true,
    tag: 'GRÈVE GÉNÉRALE',
    preamble: [
      'CGT, Solidaires, FSU. Cimade, DAL, Palestine.',
      'Pour la première fois depuis dix ans,',
      '**les luttes convergent**.',
      'Retraites. Loi immigration. Gaza. Salaires.',
      '**Tout lier. Tout bloquer.**',
    ],
    title_lines: ['1ER MAI 2026 :', 'ON MARCHE ENSEMBLE'],
    image_path: 'content/media/1er-mai-2026.jpg',
    palette: {
      accent:  '#ffcb05',   // jaune solidarité
      title_1: '#e63824',   // rouge syndical
      title_2: '#ffffff',
      tag_bg:  '#ffcb05',
      tag_fg:  '#0f0f0f',   // tag jaune sur noir
      tint:    '#1a0806',   // rouge brûlé très sombre
      tint_opacity: 0.78,
    } },
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
 * Rendu style Contre-Attaque :
 * - Préambule multi-lignes en haut (mots ** en jaune)
 * - Photo en fond assombrie
 * - Titre gros impactant en bas
 * - Tag rouge en haut-gauche
 */
function buildSvgCAStyle({ tag, preamble, title_lines, image, palette }) {
  const W = 1600, H = 900;
  // Palette avec valeurs par défaut (fallback jaune/rouge/noir)
  const P = Object.assign({
    accent:  '#ffcb05',
    title_1: '#ffffff',
    title_2: '#ffcb05',
    tag_bg:  '#e02810',
    tag_fg:  '#ffffff',
    tint:    '#0f0f0f',
    tint_opacity: 0.85,
  }, palette || {});
  const tagWidth = Math.max(240, tag.length * 12 + 50);

  // Typographies inspirées de Contre-Attaque :
  // - Préambule : sans-serif semi-bold condensé (Haettenschweiler ou fallback)
  // - Titres : condensed bold sans-serif (Impact = présent sur tous les OS)
  //   Pas de serif Fraunces : CA utilise exclusivement des polices grotesques
  //   trapues et serrées qui crient l'urgence.
  const FONT_TITLE = "'Impact', 'Haettenschweiler', 'Arial Narrow Bold', 'Oswald', 'Anton', 'Bebas Neue', sans-serif";
  const FONT_BODY  = "'Haettenschweiler', 'Arial Narrow', 'Oswald', 'Roboto Condensed', 'Inter', sans-serif";

  function renderPreambleLine(line, y) {
    const parts = line.split(/(\*\*[^*]+\*\*)/);
    const tspans = parts.map(p => {
      if (p.startsWith('**') && p.endsWith('**')) {
        const t = p.slice(2, -2);
        return `<tspan fill="${P.accent}" font-weight="900">${xmlEscape(t)}</tspan>`;
      }
      return `<tspan fill="#ffffff" font-weight="600">${xmlEscape(p)}</tspan>`;
    }).join('');
    return `<text x="80" y="${y}" font-family="${FONT_BODY}" font-size="32" letter-spacing="0.2" filter="url(#textshadow)">${tspans}</text>`;
  }

  const preambleLines = preamble.map((line, i) => renderPreambleLine(line, 135 + i * 42)).join('\n  ');

  // Titre : Impact (condensed narrow) — avgCharWidthRatio ~0.40 au lieu
  // de 0.52 pour serif, donc on peut grossir la base sans overflow.
  const titleSizeBase = title_lines.length > 1 ? 110 : 130;
  const titleY = title_lines.length > 1 ? H - 170 : H - 115;
  const titleSpans = title_lines.map((l, i) => {
    const fit = fitFontSize(l, titleSizeBase, 1420, 0.42);
    const color = i === 0 ? P.title_1 : P.title_2;
    return `<text x="80" y="${titleY + i * (titleSizeBase - 5)}" font-family="${FONT_TITLE}" font-weight="900" font-size="${fit}" letter-spacing="1" fill="${color}" filter="url(#textshadow)">${xmlEscape(l)}</text>`;
  }).join('\n  ');

  // Le tint définit la couleur du voile (pas toujours noir)
  return `<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 ${W} ${H}" width="${W}" height="${H}" role="img" aria-label="${xmlEscape(tag)} — ${xmlEscape(title_lines.join(' '))}">
  <title>${xmlEscape(tag)} — ${xmlEscape(title_lines.join(' '))}</title>
  <defs>
    <clipPath id="clip"><rect width="${W}" height="${H}"/></clipPath>
    <!-- Gradient LETTERBOX : bandes sombres haut (préambule) + bas (titre),
         photo PLEINEMENT VISIBLE au centre (opacity ~0.22). -->
    <linearGradient id="overlay" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0%"   stop-color="${P.tint}" stop-opacity="0.93"/>
      <stop offset="27%"  stop-color="${P.tint}" stop-opacity="0.90"/>
      <stop offset="32%"  stop-color="${P.tint}" stop-opacity="0.35"/>
      <stop offset="45%"  stop-color="${P.tint}" stop-opacity="0.20"/>
      <stop offset="60%"  stop-color="${P.tint}" stop-opacity="0.22"/>
      <stop offset="72%"  stop-color="${P.tint}" stop-opacity="0.45"/>
      <stop offset="78%"  stop-color="${P.tint}" stop-opacity="0.85"/>
      <stop offset="100%" stop-color="${P.tint}" stop-opacity="0.95"/>
    </linearGradient>
    <filter id="textshadow" x="-5%" y="-5%" width="110%" height="110%">
      <feDropShadow dx="0" dy="4" stdDeviation="8" flood-color="#000" flood-opacity="0.75"/>
    </filter>
  </defs>

  <!-- Photo de fond -->
  <g clip-path="url(#clip)">
    <image href="${xmlEscape(image)}" xlink:href="${xmlEscape(image)}"
           x="0" y="0" width="${W}" height="${H}"
           preserveAspectRatio="xMidYMid slice"/>
  </g>

  <!-- Voile teinté selon palette -->
  <rect width="${W}" height="${H}" fill="url(#overlay)"/>

  <!-- Cadres rouges (toujours rouges pour identité QL) -->
  <rect x="0" y="0" width="${W}" height="8" fill="#e02810"/>
  <rect x="0" y="${H - 8}" width="${W}" height="8" fill="#e02810"/>

  <!-- Tag (couleur palette, police condensée comme le reste) -->
  <g transform="translate(80, 60)">
    <rect x="0" y="0" width="${tagWidth}" height="40" fill="${P.tag_bg}" rx="2"/>
    <text x="${tagWidth / 2}" y="28" font-family="${FONT_TITLE}" font-weight="900"
          font-size="17" letter-spacing="3.5" fill="${P.tag_fg}" text-anchor="middle">${xmlEscape(tag)}</text>
  </g>

  <!-- Préambule multi-lignes -->
  ${preambleLines}

  <!-- Titre principal (bas, couleurs palette) -->
  ${titleSpans}

  <!-- Signature (toujours discrète) -->
  <text x="${W - 80}" y="${H - 40}"
        font-family="Inter, system-ui, sans-serif"
        font-weight="600"
        font-size="16"
        letter-spacing="5"
        fill="#a9a595"
        text-anchor="end">— QUARTIERLIBRE.ORG</text>
</svg>
`;
}

/**
 * Rendu avec photo de fond (ancien style, encore utilisé par fallback).
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
    let svg;
    if (banner.ca_style) {
      svg = buildSvgCAStyle(banner);
    } else if (banner.image) {
      svg = buildSvgWithPhoto(banner);
    } else {
      svg = buildSvgTypographic(banner);
    }
    // Les actualités gardent leur slug tel quel ; les quartiers héritent du préfixe historique.
    const filename = banner.slug.startsWith('actualite-')
      ? `${banner.slug}.svg`
      : `quartier-${banner.slug}.svg`;
    const outPath = path.join(OUTPUT_DIR, filename);
    fs.writeFileSync(outPath, svg, 'utf-8');
    const mode = banner.ca_style ? '📰 ca-style' : (banner.image ? '📷 photo' : '🎨 typo ');
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
