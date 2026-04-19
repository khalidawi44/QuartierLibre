/**
 * _add_cross_links.js — one-shot
 * Ajoute des liens internes (entre articles quartier) et externes
 * (HelloAsso) au corps des .md.
 */
const fs = require('fs');
const path = require('path');

const ARTICLES = path.join(__dirname, 'content', 'articles');

// Nom FR (pour matcher dans le texte) → slug de catégorie
const QUARTIERS = [
  ['Bottière-Pin Sec',    'bottiere-pin-sec'],
  ['Bottière — Pin Sec',  'bottiere-pin-sec'],
  ['Bottière – Pin Sec',  'bottiere-pin-sec'],
  ['Bottière',            'bottiere-pin-sec'],
  ['Bout des Landes',     'bout-des-landes'],
  ['Clos Toreau',         'clos-toreau'],
  ['Dervallières',        'dervallieres'],
  ['Dervallieres',        'dervallieres'],
  ['Halvêque',            'halveque'],
  ['Halveque',            'halveque'],
  ['Pilotière',           'pilotiere'],
  ['Pilotiere',           'pilotiere'],
  ['Port Boyer',          'port-boyer'],
  ['Ranzay',              'ranzay'],
  ['Bellevue',            'bellevue'],
  ['Malakoff',            'malakoff'],
  ['Breil',               'breil'],
];

// Slug propre à chaque article (pour ne pas s'auto-lier)
const OWN_SLUG = {
  '2026-04-01-bellevue-zone-de-surveillance-prioritaire.md':          'bellevue',
  '2026-04-02-malakoff-la-renovation-urbaine-contre-les-habitants.md':'malakoff',
  '2026-04-03-dervallieres-l-abandon-des-services-publics.md':        'dervallieres',
  '2026-04-04-clos-toreau-punaises-moisissures-mepris.md':            'clos-toreau',
  '2026-04-05-bottiere-pin-sec-npnru-contre-habitants.md':            'bottiere-pin-sec',
  '2026-04-06-breil-bac-ados-controles-au-facies.md':                 'breil',
  '2026-04-07-bout-des-landes-enclaves-et-oublies.md':                'bout-des-landes',
  '2026-04-08-port-boyer-etudiants-precaires-marchands-sommeil.md':   'port-boyer',
  '2026-04-09-halveque-la-ville-stigmatisee-par-les-medias.md':       'halveque',
  '2026-04-10-ranzay-ville-dortoir-sans-services.md':                 'ranzay',
  '2026-04-11-pilotiere-auto-organisation-contre-abandon.md':         'pilotiere',
};

// URL de dons (HelloAsso placeholder, remplaçable via l'option ql_donorbox_url)
const HELLOASSO = 'https://www.helloasso.com/associations/quartier-libre-nantes';

// Gaza : lier entre eux les articles Gaza (international)
const GAZA_FILES = [
  '2025-08-22-gaza-famine-declaree-les-responsables-doivent-rendre-des-comptes.md',
  '2025-09-16-gaza-le-genocide-sous-nos-yeux.md',
  '2025-09-17-rendez-gaza-visible.md',
  '2025-09-22-heritages-de-la-resistance.md',
];

function escapeRegex(s) { return s.replace(/[-/\\^$*+?.()|[\]{}]/g, '\\$&'); }

/**
 * Calcule les plages de texte qui ne doivent PAS être touchées :
 * - liens markdown existants [text](url)
 * - URLs bruts https://…
 * - bloc de code ```…```
 * - ligne commençant par # (titre) — on laisse les titres tranquilles
 */
function protectedRanges(body) {
  const ranges = [];
  // Liens markdown
  let m; const re1 = /\[[^\]]*\]\([^)]+\)/g;
  while ((m = re1.exec(body)) !== null) ranges.push([m.index, m.index + m[0].length]);
  // URLs brutes
  const re2 = /https?:\/\/[^\s)\]]+/g;
  while ((m = re2.exec(body)) !== null) ranges.push([m.index, m.index + m[0].length]);
  // Blocs de code
  const re3 = /```[\s\S]*?```/g;
  while ((m = re3.exec(body)) !== null) ranges.push([m.index, m.index + m[0].length]);
  return ranges;
}

function inRanges(pos, ranges) {
  for (const [a, b] of ranges) if (pos >= a && pos < b) return true;
  return false;
}

function linkifyQuartiers(body, ownSlug) {
  const already = new Set([ownSlug]);
  for (const [name, slug] of QUARTIERS) {
    if (already.has(slug)) continue;
    const regex = new RegExp(
      '(?<![A-Za-zÀ-ÿ\\-])' + escapeRegex(name) + '(?![A-Za-zÀ-ÿ\\-])',
      'g'
    );
    let match;
    const ranges = protectedRanges(body);
    while ((match = regex.exec(body)) !== null) {
      if (inRanges(match.index, ranges)) continue;
      // Insertion : remplace cette occurrence par un lien
      const before = body.slice(0, match.index);
      const after = body.slice(match.index + match[0].length);
      body = before + '[' + match[0] + '](/category/' + slug + '/)' + after;
      already.add(slug);
      break; // une seule occurrence par quartier
    }
  }
  return body;
}

function linkifyGazaCrossRefs(body, ownFile) {
  // Pour Gaza : si l'article ne contient pas déjà de lien HelloAsso etc,
  // on ajoute une ligne renvoyant aux autres articles Gaza via tag/cat.
  // Ici on se contente d'ajouter un lien contextualisé si « Gaza » apparaît
  // plus de 5 fois sans lien existant.
  // Simple : on laisse tel quel si déjà linké, sinon lien vers /category/genocide/
  if (/\[[^\]]*Gaza[^\]]*\]\(/.test(body)) return body;
  if (body.includes('/category/')) return body;
  // Première occurrence de « Gaza » → lien vers la catégorie Génocide
  const regex = /(?<![A-Za-zÀ-ÿ\-])Gaza(?![A-Za-zÀ-ÿ\-])/;
  const m = regex.exec(body);
  if (m && !inRanges(m.index, protectedRanges(body))) {
    return body.slice(0, m.index) + '[Gaza](/category/genocide/)' + body.slice(m.index + 4);
  }
  return body;
}

function addHelloAssoFooter(body) {
  if (body.includes('helloasso.com')) return body;
  const footer =
    '\n\n---\n\n' +
    '*Cette enquête vit grâce à ses lectrices et lecteurs. Pour qu\'elle continue, ' +
    '[soutenez Quartier Libre](' + HELLOASSO + ').*\n';
  return body.trimEnd() + footer;
}

/**
 * Ajoute un bloc « Voir aussi » listant 3 autres quartiers HLM.
 * Choisit les 3 adjacents dans l'ordre de publication pour variété.
 */
const HLM_ORDER = [
  ['bellevue',         'Bellevue'],
  ['malakoff',         'Malakoff'],
  ['dervallieres',     'Dervallières'],
  ['clos-toreau',      'Clos Toreau'],
  ['bottiere-pin-sec', 'Bottière — Pin Sec'],
  ['breil',            'Breil'],
  ['bout-des-landes',  'Bout des Landes'],
  ['port-boyer',       'Port Boyer'],
  ['halveque',         'Halvêque'],
  ['ranzay',           'Ranzay'],
  ['pilotiere',        'Pilotière'],
];
function addVoirAussi(body, ownSlug) {
  if (body.includes('## Voir aussi')) return body;
  const i = HLM_ORDER.findIndex(([s]) => s === ownSlug);
  if (i < 0) return body;
  const n = HLM_ORDER.length;
  // 3 autres quartiers : les 2 suivants + le précédent (wrap)
  const picks = [
    HLM_ORDER[(i + 1) % n],
    HLM_ORDER[(i + 2) % n],
    HLM_ORDER[(i + n - 1) % n],
  ];
  const block =
    '\n\n## Voir aussi — les autres quartiers\n\n' +
    picks.map(([slug, name]) => '- [' + name + '](/category/' + slug + '/)').join('\n') +
    '\n';
  // Insère AVANT le footer HelloAsso si présent, sinon à la fin
  if (body.includes('helloasso.com')) {
    const footerIdx = body.lastIndexOf('\n---\n');
    if (footerIdx > 0) {
      return body.slice(0, footerIdx) + block + body.slice(footerIdx);
    }
  }
  return body.trimEnd() + block;
}

// Main
let touched = 0;

// 1. HLM quartier articles
for (const [file, ownSlug] of Object.entries(OWN_SLUG)) {
  const full = path.join(ARTICLES, file);
  if (!fs.existsSync(full)) continue;
  const content = fs.readFileSync(full, 'utf8');
  const m = content.match(/^---\n([\s\S]*?)\n---\n([\s\S]*)$/);
  if (!m) continue;
  const front = m[1];
  let body = m[2];
  const original = body;

  body = linkifyQuartiers(body, ownSlug);
  body = addVoirAussi(body, ownSlug);
  body = addHelloAssoFooter(body);

  if (body !== original) {
    fs.writeFileSync(full, '---\n' + front + '\n---\n' + body);
    touched++;
    console.log('✓', file);
  }
}

// 2. Gaza articles — cross-ref + HelloAsso footer
for (const file of GAZA_FILES) {
  const full = path.join(ARTICLES, file);
  if (!fs.existsSync(full)) continue;
  const content = fs.readFileSync(full, 'utf8');
  const m = content.match(/^---\n([\s\S]*?)\n---\n([\s\S]*)$/);
  if (!m) continue;
  const front = m[1];
  let body = m[2];
  const original = body;

  body = linkifyGazaCrossRefs(body, file);
  body = addHelloAssoFooter(body);

  if (body !== original) {
    fs.writeFileSync(full, '---\n' + front + '\n---\n' + body);
    touched++;
    console.log('✓', file);
  }
}

console.log(`\n${touched} article(s) enrichi(s) avec liens internes + HelloAsso.`);
