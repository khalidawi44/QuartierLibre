#!/usr/bin/env node
/**
 * _pexels_search.js — recherche Pexels et propose les meilleures photos
 *
 * Usage : node _pexels_search.js "query1" "query2" ...
 * Pour chaque query, affiche top 12 photos avec ID + description.
 * Pexels renvoie un __NEXT_DATA__ JSON dans la page → on parse directement.
 */
const { execFileSync } = require('child_process');

const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

// Pexels fingerprint Node https → 403. Curl passe avec UA navigateur.
function fetch(url) {
  try {
    const body = execFileSync('curl', ['-sL', '-A', UA, url], { encoding: 'utf8', maxBuffer: 10 * 1024 * 1024 });
    return { status: 200, body };
  } catch (e) {
    return { status: 500, body: '', error: e.message };
  }
}

function pexelsSearch(query) {
  const url = `https://www.pexels.com/search/${encodeURIComponent(query)}/`;
  const { status, body } = fetch(url);
  if (status !== 200) return { error: `HTTP ${status}` };
  // Regex permissive : on prend simplement tout entre > et </script>
  // après __NEXT_DATA__ (peu importe les attributs intermédiaires).
  const m = body.match(/__NEXT_DATA__[^>]*>([\s\S]+?)<\/script>/);
  if (!m) return { error: 'no __NEXT_DATA__ (body len=' + body.length + ')' };
  try {
    const data = JSON.parse(m[1]);
    const photos = data?.props?.pageProps?.initialData?.data || [];
    const simplified = photos.slice(0, 15).map(p => {
      const a = p.attributes || {};
      return {
        id: a.id,
        description: (a.description || a.alt || '').replace(/\s+/g, ' ').trim().slice(0, 100),
        photographer: a.photographer?.name || a.user?.name || '',
        width: a.width,
        height: a.height,
      };
    });
    return { photos: simplified };
  } catch (e) {
    return { error: 'parse: ' + e.message };
  }
}

const queries = process.argv.slice(2);
if (!queries.length) {
  console.log('Usage: node _pexels_search.js "query1" "query2" ...');
  process.exit(1);
}
for (const q of queries) {
  console.log('\n=== ' + q.toUpperCase() + ' ===');
  const r = pexelsSearch(q);
  if (r.error) {
    console.log('ERR:', r.error);
    continue;
  }
  r.photos.forEach(p => {
    console.log(`  ${p.id}  |  ${p.description}`);
  });
}
