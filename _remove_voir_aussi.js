/**
 * _remove_voir_aussi.js — one-shot cleanup
 * Retire les blocs « ## Voir aussi » ajoutés dans les articles
 * (redondants avec la section « Sur le même sujet » générée par single.php).
 * Conserve le footer HelloAsso en dessous.
 */
const fs = require('fs');
const path = require('path');

const ARTICLES = path.join(__dirname, 'content', 'articles');

let touched = 0;

fs.readdirSync(ARTICLES).forEach(file => {
  if (!file.endsWith('.md') || file === 'README.md') return;
  const full = path.join(ARTICLES, file);
  const content = fs.readFileSync(full, 'utf8');

  // Regex : on capture depuis « ## Voir aussi » (avec ou sans suffixe)
  // jusqu'à la ligne blanche suivie soit d'un « --- » (séparateur),
  // soit d'un « ## », soit de la fin du fichier.
  const re = /\n+## Voir aussi[^\n]*\n+(?:- [^\n]*\n+)+/g;
  const out = content.replace(re, '\n\n');

  if (out !== content) {
    fs.writeFileSync(full, out);
    touched++;
    console.log('✓ Voir aussi retiré :', file);
  }
});

console.log(`\n${touched} article(s) nettoyé(s).`);
