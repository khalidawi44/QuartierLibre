/**
 * _update_authors.js — script one-shot
 * Met à jour auteur + catégorie + flag une sur tous les .md de content/articles/
 * À supprimer après exécution (pas committé sur le repo).
 */
const fs = require('fs');
const path = require('path');

const ARTICLES = path.join(__dirname, 'content', 'articles');

// login de l'auteur + catégorie WP attendue pour chaque article
const MAP = {
  // 2024
  '2024-04-23-ecologie-de-facade-misere-derriere-les-murs.md':                                                 { category: 'infos-locale',  author: 'mehdi-haddad' },
  // 2025 — juillet
  '2025-07-23-securite-mediation-prevention-les-quartiers-sous-controle-pas-sous-protection.md':               { category: 'luttes',        author: 'fatou-traore' },
  '2025-07-24-deux-morts-a-nantes-crime-social-silence-politique.md':                                          { category: 'infos-locale',  author: 'aissata-diallo' },
  '2025-07-31-nuisibles-dans-les-logements-sociaux-a-nantes-silence-et-inaction-coupables-de-la-mai.md':       { category: 'infos-locale',  author: 'soraya-messaoudi' },
  // 2025 — août
  '2025-08-06-la-propagande-par-le-fait.md':                                                                    { category: 'histoire',      author: 'julien-moreau' },
  '2025-08-06-pourquoi-obeit-on-encore-de-la-boetie-a-nos-quartiers-aujourdhui.md':                             { category: 'histoire',      author: 'julien-moreau' },
  '2025-08-12-nantes-2024-quand-largent-public-danse-au-bal-des-privilegies-et-lombre-lourde-des-ba.md':        { category: 'france',        author: 'julien-moreau' },
  '2025-08-22-gaza-famine-declaree-les-responsables-doivent-rendre-des-comptes.md':                             { category: 'international', author: 'rachida-ben-arfa' },
  // 2025 — septembre
  '2025-09-01-le-cadavre-politique-du-ps-un-mort-qui-gesticule-encore.md':                                     { category: 'france',        author: 'julien-moreau' },
  '2025-09-10-bloquons-tout-a-nantes-rappel-des-rendez-vous.md':                                                { category: 'luttes',        author: 'karima-benali' },
  '2025-09-10-nantes-sous-les-gaz-la-repression-dun-gouvernement-illegitime.md':                               { category: 'luttes',        author: 'fatou-traore',     une: true },
  '2025-09-16-gaza-le-genocide-sous-nos-yeux.md':                                                               { category: 'international', author: 'rachida-ben-arfa', une: true },
  '2025-09-17-rendez-gaza-visible.md':                                                                          { category: 'international', author: 'rachida-ben-arfa' },
  '2025-09-22-heritages-de-la-resistance.md':                                                                   { category: 'international', author: 'rachida-ben-arfa' },
  '2025-09-27-homard-en-papier-mache-parapluies-vs-valises-de-kadhafi-prison-pour-les-pauvres-passe.md':        { category: 'france',        author: 'julien-moreau' },
  // 2025 — octobre
  '2025-10-01-keolis-nantes-la-privatisation-a-marche-forcee.md':                                              { category: 'infos-locale',  author: 'samir-toure' },
  '2025-10-11-louis-macron-xvi-et-son-moine-soldat-le-retour-de-la-monarchie-par-ordonnance.md':               { category: 'france',        author: 'julien-moreau',    une: true },
  // 2026 — HLM series
  '2026-04-01-bellevue-zone-de-surveillance-prioritaire.md':                                                   { category: 'infos-locale',  author: 'aissata-diallo',   une: true },
  '2026-04-02-malakoff-la-renovation-urbaine-contre-les-habitants.md':                                         { category: 'infos-locale',  author: 'younes-boukhris' },
  '2026-04-03-dervallieres-l-abandon-des-services-publics.md':                                                 { category: 'infos-locale',  author: 'karima-benali' },
  '2026-04-04-clos-toreau-punaises-moisissures-mepris.md':                                                     { category: 'infos-locale',  author: 'soraya-messaoudi' },
  '2026-04-05-bottiere-pin-sec-npnru-contre-habitants.md':                                                     { category: 'infos-locale',  author: 'mehdi-haddad' },
  '2026-04-06-breil-bac-ados-controles-au-facies.md':                                                          { category: 'infos-locale',  author: 'fatou-traore' },
  '2026-04-07-bout-des-landes-enclaves-et-oublies.md':                                                         { category: 'infos-locale',  author: 'samir-toure' },
  '2026-04-08-port-boyer-etudiants-precaires-marchands-sommeil.md':                                            { category: 'infos-locale',  author: 'lea-marchand' },
  '2026-04-09-halveque-la-ville-stigmatisee-par-les-medias.md':                                                { category: 'infos-locale',  author: 'naima-ouedraogo' },
  '2026-04-10-ranzay-ville-dortoir-sans-services.md':                                                          { category: 'infos-locale',  author: 'amadou-kone' },
  '2026-04-11-pilotiere-auto-organisation-contre-abandon.md':                                                  { category: 'infos-locale',  author: 'sofia-bensalem' },
};

let ok = 0, skipped = 0, missing = [];

for (const [filename, changes] of Object.entries(MAP)) {
  const full = path.join(ARTICLES, filename);
  if (!fs.existsSync(full)) {
    missing.push(filename);
    continue;
  }
  let content = fs.readFileSync(full, 'utf8');
  const original = content;

  // Split frontmatter / body
  const m = content.match(/^---\n([\s\S]*?)\n---\n([\s\S]*)$/);
  if (!m) { console.warn('⚠ pas de frontmatter :', filename); skipped++; continue; }
  let front = m[1], body = m[2];

  // category
  if (/^category:.*$/m.test(front)) {
    front = front.replace(/^category:.*$/m, `category: "${changes.category}"`);
  } else {
    front += `\ncategory: "${changes.category}"`;
  }

  // author
  if (/^author:.*$/m.test(front)) {
    front = front.replace(/^author:.*$/m, `author: "${changes.author}"`);
  } else {
    front += `\nauthor: "${changes.author}"`;
  }

  // une
  if (changes.une) {
    if (/^une:\s*true\s*$/m.test(front)) {
      // déjà présent
    } else if (/^une:.*$/m.test(front)) {
      front = front.replace(/^une:.*$/m, `une: true`);
    } else {
      // ajouter après author
      front = front.replace(/^(author:.*)$/m, `$1\nune: true`);
    }
  } else {
    // retirer si présent
    front = front.replace(/^une:.*\n?/m, '');
  }

  content = `---\n${front}\n---\n${body}`;
  if (content !== original) {
    fs.writeFileSync(full, content);
    ok++;
    const une = changes.une ? ' ⭐UNE' : '';
    console.log(`✓ ${filename}  →  ${changes.category} / ${changes.author}${une}`);
  } else {
    skipped++;
  }
}

console.log(`\n${ok} fichier(s) mis à jour, ${skipped} inchangé(s).`);
if (missing.length) {
  console.log(`⚠ ${missing.length} fichier(s) manquant(s) :`);
  missing.forEach(f => console.log('  -', f));
}
