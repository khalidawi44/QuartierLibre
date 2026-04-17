#!/usr/bin/env node
/**
 * Convert WP REST API JSON export -> Markdown files with YAML frontmatter.
 * Output : content/articles/YYYY-MM-DD-slug.md
 */
const fs = require('fs');
const path = require('path');

const INPUT = '_articles_raw.json';
const OUTPUT_DIR = 'content/articles';

function stripTags(s) {
  if (!s) return '';
  return s.replace(/<[^>]+>/g, '').trim();
}

function decodeEntities(s) {
  if (!s) return '';
  return s
    .replace(/&nbsp;/g, ' ')
    .replace(/&amp;/g, '&')
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>')
    .replace(/&quot;/g, '"')
    .replace(/&#039;/g, "'")
    .replace(/&apos;/g, "'")
    .replace(/&rsquo;/g, '’')
    .replace(/&lsquo;/g, '‘')
    .replace(/&rdquo;/g, '”')
    .replace(/&ldquo;/g, '“')
    .replace(/&hellip;/g, '…')
    .replace(/&mdash;/g, '—')
    .replace(/&ndash;/g, '–')
    .replace(/&#8217;/g, '’')
    .replace(/&#8216;/g, '‘')
    .replace(/&#8220;/g, '“')
    .replace(/&#8221;/g, '”')
    .replace(/&#8211;/g, '–')
    .replace(/&#8212;/g, '—')
    .replace(/&#8230;/g, '…')
    .replace(/&#(\d+);/g, (_, n) => String.fromCodePoint(parseInt(n, 10)))
    .replace(/&#x([0-9a-fA-F]+);/g, (_, n) => String.fromCodePoint(parseInt(n, 16)));
}

function htmlToMd(html) {
  if (!html) return '';

  // 0. Strip <style>...</style> and <script>...</script> entirely
  html = html.replace(/<style\b[^>]*>[\s\S]*?<\/style>/gi, '');
  html = html.replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi, '');
  html = html.replace(/<noscript\b[^>]*>[\s\S]*?<\/noscript>/gi, '');

  // 1. Strip WP block comments + HTML comments
  html = html.replace(/<!--\s*\/?wp:[^>]*-->/gi, '');
  html = html.replace(/<!--[\s\S]*?-->/g, '');

  // 2. Kill decorative widget blocks (shape-divider, countdown, progressbar...)
  const killClasses = [
    'wp-block-kadence-countdown',
    'wp-block-kadence-progressbar',
    'wp-block-kadence-spacer',
    'wp-block-kadence-form',
    'wp-block-kadence-googlemaps',
    'wp-block-kadence-lottie',
    'wp-block-kadence-iconlist',
    'wp-block-blockspare-shape-divider',
    'wp-block-blockspare-slider',
    'blockspare-shape-divider',
    'shape-divider',
    'shape_divider',
  ];
  for (const cls of killClasses) {
    const re = new RegExp(
      `<(div|section|figure)[^>]*class="[^"]*${cls}[^"]*"[^>]*>([\\s\\S]*?)</\\1>`,
      'gi'
    );
    // Handle nesting by iterating
    let prev;
    do { prev = html; html = html.replace(re, ''); } while (html !== prev);
  }

  // 3. Kill large decorative SVGs
  html = html.replace(/<svg\b[^>]*>([\s\S]*?)<\/svg>/gi, (match) => {
    if (/<text/i.test(match)) return match;
    const vb = match.match(/viewBox="[^"]*\s(\d+)(?:\s|")/i);
    if (vb && parseInt(vb[1], 10) > 100) return '';
    const h = match.match(/height="(\d+)/i);
    if (h && parseInt(h[1], 10) > 80) return '';
    return match;
  });

  // 4. Kill forms (login / register / donorbox iframes)
  html = html.replace(/<form\b[^>]*>[\s\S]*?<\/form>/gi, '');
  html = html.replace(/<iframe\b[^>]*donorbox[^>]*>[\s\S]*?<\/iframe>/gi, '');

  // 5. Unwrap Kadence containers (keep content, remove wrapper tags)
  const unwrapClasses = [
    'wp-block-kadence-rowlayout',
    'wp-block-kadence-column',
    'wp-block-kadence-section',
    'wp-block-kadence-inner-column',
    'wp-block-kadence-advancedheading',
    'kb-row-layout-wrap',
    'kb-column-wrap',
  ];
  for (const cls of unwrapClasses) {
    // remove opening <div class="...cls...">
    html = html.replace(
      new RegExp(`<div[^>]*class="[^"]*${cls}[^"]*"[^>]*>`, 'gi'),
      ''
    );
  }

  // 6. Strip classes and styles from heading tags (keep tag only)
  html = html.replace(/<(h[1-6])[^>]*>/gi, (_, t) => `<${t}>`);

  // 7. Convert headings to markdown
  for (let lvl = 6; lvl >= 1; lvl--) {
    const re = new RegExp(`<h${lvl}[^>]*>([\\s\\S]*?)<\\/h${lvl}>`, 'gi');
    html = html.replace(re, (_, inner) => `\n\n${'#'.repeat(lvl)} ${stripTags(inner).trim()}\n\n`);
  }

  // 8. Convert images (<img src="..." alt="...">)
  html = html.replace(/<img\b[^>]*\bsrc="([^"]+)"[^>]*>/gi, (m, src) => {
    const altMatch = m.match(/\balt="([^"]*)"/i);
    const alt = altMatch ? altMatch[1] : '';
    return `\n\n![${alt}](${src})\n\n`;
  });

  // 9. <figure> unwrap + <figcaption>
  html = html.replace(/<figure[^>]*>/gi, '');
  html = html.replace(/<\/figure>/gi, '\n\n');
  html = html.replace(/<figcaption[^>]*>([\s\S]*?)<\/figcaption>/gi,
    (_, inner) => `\n*${stripTags(inner).trim()}*\n\n`);

  // 10. Links
  html = html.replace(/<a\b[^>]*\bhref="([^"]+)"[^>]*>([\s\S]*?)<\/a>/gi,
    (_, href, inner) => {
      const text = stripTags(inner).trim();
      return text ? `[${text}](${href})` : '';
    });

  // 11. Bold / italic
  html = html.replace(/<(strong|b)\b[^>]*>([\s\S]*?)<\/\1>/gi,
    (_, t, inner) => `**${stripTags(inner).trim()}**`);
  html = html.replace(/<(em|i)\b[^>]*>([\s\S]*?)<\/\1>/gi,
    (_, t, inner) => `*${stripTags(inner).trim()}*`);

  // 12. Blockquotes
  html = html.replace(/<blockquote[^>]*>([\s\S]*?)<\/blockquote>/gi,
    (_, inner) => {
      const text = stripTags(inner).trim();
      return '\n\n' + text.split('\n').map(l => `> ${l}`).join('\n') + '\n\n';
    });

  // 13. Lists
  html = html.replace(/<ul[^>]*>([\s\S]*?)<\/ul>/gi, (_, inner) => {
    const items = [];
    inner.replace(/<li[^>]*>([\s\S]*?)<\/li>/gi, (_, liInner) => {
      items.push('- ' + stripTags(liInner).trim());
      return '';
    });
    return '\n\n' + items.join('\n') + '\n\n';
  });
  html = html.replace(/<ol[^>]*>([\s\S]*?)<\/ol>/gi, (_, inner) => {
    const items = [];
    let i = 1;
    inner.replace(/<li[^>]*>([\s\S]*?)<\/li>/gi, (_, liInner) => {
      items.push(`${i++}. ` + stripTags(liInner).trim());
      return '';
    });
    return '\n\n' + items.join('\n') + '\n\n';
  });

  // 13b. Orphan <li> tags (lists with broken wrapping)
  html = html.replace(/<li[^>]*>([\s\S]*?)<\/li>/gi, (_, inner) => `\n- ${stripTags(inner).trim()}`);

  // 14. <br>
  html = html.replace(/<br\s*\/?>/gi, '\n');

  // 14b. <hr> with attributes
  html = html.replace(/<hr\b[^>]*>/gi, '\n\n---\n\n');

  // 15. Paragraphs
  html = html.replace(/<p[^>]*>([\s\S]*?)<\/p>/gi,
    (_, inner) => `\n\n${stripTags(inner).trim()}\n\n`);

  // 16. HR
  html = html.replace(/<hr\s*\/?>/gi, '\n\n---\n\n');

  // 17. Strip remaining structural tags transparently
  html = html.replace(/<\/?(?:div|span|section|article|main|aside|header|footer|nav|ul|ol)\b[^>]*>/gi, '');

  // 17b. Strip any remaining unclosed tags
  html = html.replace(/<\/?[a-z][a-z0-9]*\b[^>]*>/gi, '');

  // 18. Decode HTML entities
  html = decodeEntities(html);

  // 19. Cleanup whitespace
  html = html.replace(/[ \t]+\n/g, '\n');
  html = html.replace(/\n{3,}/g, '\n\n');
  html = html.trim();

  return html;
}

function yamlEscape(s) {
  if (!s) return '""';
  // If contains special chars or starts with special, quote
  const escaped = String(s).replace(/\\/g, '\\\\').replace(/"/g, '\\"');
  return `"${escaped}"`;
}

function slugify(name) {
  return String(name)
    .toLowerCase()
    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-|-$/g, '');
}

function main() {
  const raw = fs.readFileSync(INPUT, 'utf-8');
  const posts = JSON.parse(raw);

  if (!fs.existsSync(OUTPUT_DIR)) fs.mkdirSync(OUTPUT_DIR, { recursive: true });

  let count = 0;
  for (const post of posts) {
    const pid = post.id;
    let slug = post.slug || `post-${pid}`;
    // Décode les slugs URL-encodés (emoji, accents, etc.) → ASCII propre
    try { slug = decodeURIComponent(slug); } catch (e) {}
    slug = slug
      .toLowerCase()
      .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-|-$/g, '');
    const title = decodeEntities(stripTags(post.title?.rendered || ''));
    const dateStr = post.date || '';
    const dt = new Date(dateStr);
    const dateFile = dt.toISOString().slice(0, 10);
    const dateFull = `${dateFile} ${dt.toISOString().slice(11, 19)}`;

    const contentHtml = post.content?.rendered || '';
    const excerpt = decodeEntities(stripTags(post.excerpt?.rendered || '')).trim();
    const status = post.status || 'publish';
    const link = post.link || '';

    // Embedded
    let authorName = '';
    let featuredUrl = '';
    const categories = [];
    const tags = [];
    const embedded = post._embedded || {};

    if (embedded.author && embedded.author[0]) {
      authorName = embedded.author[0].name || '';
    }

    if (embedded['wp:featuredmedia'] && embedded['wp:featuredmedia'][0]) {
      featuredUrl = embedded['wp:featuredmedia'][0].source_url || '';
    }

    if (embedded['wp:term']) {
      for (const termGroup of embedded['wp:term']) {
        for (const term of termGroup) {
          if (term.taxonomy === 'category') categories.push(term.slug || term.name || '');
          else if (term.taxonomy === 'post_tag') tags.push(term.slug || term.name || '');
        }
      }
    }

    const primaryCat = categories[0] || 'non-classe';
    let bodyMd = htmlToMd(contentHtml);

    // Strip leading duplicate of the featured image from the body.
    // (l'hero est déjà rendu depuis featured_image_url — pas besoin du doublon)
    if (featuredUrl) {
      // Match ![...](URL) au tout début, avec des espaces/retours possibles
      const escapedUrl = featuredUrl.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
      const leadImgRe = new RegExp(
        `^\\s*!\\[[^\\]]*\\]\\(${escapedUrl}\\)\\s*`,
        ''
      );
      bodyMd = bodyMd.replace(leadImgRe, '').trim();

      // Aussi : toute autre image en tête si son URL contient le stem du
      // filename de la featured (variante de taille, ex -300x200.jpg)
      const fname = featuredUrl.split('/').pop().replace(/\.[^.]+$/, '');
      if (fname) {
        const stemRe = new RegExp(
          `^\\s*!\\[[^\\]]*\\]\\([^)]*${fname.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}[^)]*\\)\\s*`,
          ''
        );
        bodyMd = bodyMd.replace(stemRe, '').trim();
      }
    }

    // Frontmatter
    const lines = ['---'];
    lines.push(`title: ${yamlEscape(title)}`);
    lines.push(`slug: "${slug}"`);
    lines.push(`category: "${primaryCat}"`);
    if (tags.length) {
      lines.push('tags:');
      for (const t of tags) lines.push(`  - ${t}`);
    }
    if (excerpt) lines.push(`excerpt: ${yamlEscape(excerpt)}`);
    if (featuredUrl) lines.push(`featured_image_url: "${featuredUrl}"`);
    lines.push(`status: "${status}"`);
    lines.push(`date: "${dateFull}"`);
    if (authorName) lines.push(`author: "${authorName}"`);
    lines.push(`original_url: "${link}"`);
    lines.push('---');
    lines.push('');
    lines.push(bodyMd);
    lines.push('');

    let outName = `${dateFile}-${slug}.md`;
    if (outName.length > 100) outName = outName.slice(0, 96) + '.md';
    const outPath = path.join(OUTPUT_DIR, outName);
    fs.writeFileSync(outPath, lines.join('\n'), 'utf-8');

    console.log(`  OK ${outName}  (${bodyMd.length} chars)`);
    count++;
  }

  console.log(`\n${count} articles convertis dans ${OUTPUT_DIR}/`);
}

try {
  main();
} catch (e) {
  console.error('ERREUR:', e.message);
  console.error(e.stack);
  process.exit(1);
}
