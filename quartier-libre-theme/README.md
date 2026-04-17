# Quartier Libre — Thème WordPress

Thème autonome, rapide, sobre. Aucune dépendance à Elementor, à un page
builder ou à une lib de blocs tierce. Tout le HTML, le CSS et le JS sont
dans ce dossier.

## Arborescence

```
quartier-libre-theme/
├── style.css                 ← métadonnées du thème (obligatoire)
├── functions.php             ← enqueues, supports, formulaire plaintes
├── header.php / footer.php
├── index.php                 ← home du média
├── single.php                ← page article
├── page.php                  ← page WP classique
├── archive.php               ← listing (catégorie, tag, auteur, date)
├── search.php + searchform.php
├── 404.php
├── comments.php
├── sidebar.php
├── assets/
│   ├── css/main.css          ← design system (rouge-orange)
│   ├── js/main.js            ← menu mobile, recherche
│   └── images/               ← logo.svg, favicon.svg à déposer ici
├── template-parts/
│   ├── card-article.php
│   ├── hero.php
│   └── section-category.php
└── templates/
    ├── page-bureau-plaintes.php
    └── page-pleine-largeur.php
```

## Installation (FTP via FileZilla)

1. **Brancher FileZilla** sur `quartierlibre.org` (host, user, mot de
   passe Hostinger).
2. Aller dans `public_html/wp-content/themes/`.
3. **Uploader le dossier complet `quartier-libre-theme/`** (pas
   l'intérieur — le dossier entier).
4. Dans WP Admin → *Apparence → Thèmes*, activer **Quartier Libre**.
5. Vérifier la home.

### Première configuration dans WordPress

- *Apparence → Menus* : créer un menu avec rubriques principales
  (Info locale, France, International, Luttes) et l'assigner au
  « Menu principal ».
- *Réglages → Lecture* : choisir « La page d'accueil affiche : vos
  derniers articles ».
- *Apparence → Personnaliser → Identité du site* : uploader logo +
  favicon, ou déposer `logo.svg` et `favicon.svg` dans
  `assets/images/` et le thème les prendra automatiquement.
- Créer une page « Bureau des plaintes », lui assigner le template
  *Bureau des Plaintes*, slug `bureau-des-plaintes`.

## Identité graphique

| Token | Valeur | Usage |
|---|---|---|
| `--ql-accent` | `#e63312` | rouge-orange militant (boutons, liens, badges rubriques) |
| `--ql-accent-dark` | `#b5250a` | hover/focus |
| `--ql-ink` | `#0f0f0f` | texte, footer sombre |
| `--ql-paper` | `#fafaf7` | fond crème principal |
| Titres | **Fraunces** 700/900 (serif) | titres hero & articles |
| Corps | **Inter** 400-700 | tout le reste |

Changer l'accent : éditer `--ql-accent` en haut de
`assets/css/main.css` — un seul endroit.

## Performance

- Pas de jQuery, pas de lib JS externe.
- Google Fonts chargé avec `preconnect`, `display=swap`.
- Emojis WP désactivés (~15 kB).
- Images : `loading="lazy"` + `decoding="async"` automatiques, tailles
  `ql-hero`, `ql-card`, `ql-thumb` pré-crop.
- Hero LCP : image marquée `fetchpriority="high"`.
- Pagination `no_found_rows` sur les boucles annexes.
- CSS ~12 kB (non gzipé), JS ~1 kB.

## Formulaire Bureau des Plaintes

Le formulaire est géré en natif WP (`admin-post.php` +
`wp_mail()`). Pas de plugin. Le mail est envoyé à
*Réglages → Général → Adresse e-mail d'administration*.

Pour changer le destinataire, ajouter dans `functions.php` :

```php
// Envoyer les plaintes à une adresse dédiée
add_filter( 'pre_option_admin_email', function ( $v ) {
    if ( did_action( 'admin_post_ql_plainte' ) || did_action( 'admin_post_nopriv_ql_plainte' ) ) {
        return 'plaintes@quartierlibre.org';
    }
    return $v;
} );
```

## Checklist post-activation

- [ ] Les 4 catégories (local, france, international, luttes)
  existent et contiennent des articles.
- [ ] Le menu principal est assigné.
- [ ] Logo et favicon sont chargés.
- [ ] La page `/bureau-des-plaintes/` existe avec le bon template.
- [ ] Test d'envoi du formulaire plaintes → mail reçu.
- [ ] Comparer vitesse avant/après avec PageSpeed Insights.

## Étape 2 — Nettoyage plugins

Après activation du thème, passer à la phase 2 (désactivation des
plugins redondants) — voir `DEPLOY.md`.
