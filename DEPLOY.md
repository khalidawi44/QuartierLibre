# Déploiement Quartier Libre — plan en 3 phases

## Phase 1 — Activer le nouveau thème (sans rien casser)

1. **Backup complet** avant toute chose : via Hostinger (panneau
   hébergeur → Sauvegarde) ou Duplicator. Ne jamais sauter cette
   étape.
2. Uploader par FileZilla le dossier `quartier-libre-theme/` dans
   `public_html/wp-content/themes/`.
3. WP Admin → *Apparence → Thèmes* → activer **Quartier Libre**.
4. Vérifier : home, un article, une page catégorie, la recherche,
   `/bureau-des-plaintes/`, un test du formulaire.
5. Si problème visuel : revenir au thème `quartier_libre` (child
   MoreNews) en 1 clic. Aucune donnée n'est perdue.

---

## Phase 2 — Nettoyage plugins (source principale de lenteur)

### À SUPPRIMER (redondants ou inutiles)

| Plugin | Pourquoi |
|---|---|
| **autoptimize** | Redondant avec NitroPack. |
| **litespeed-cache** | Redondant avec NitroPack (si l'hôte est LiteSpeed, on peut au contraire garder LiteSpeed ET virer NitroPack — un seul des deux). |
| **elementor** | Le nouveau thème n'en a pas besoin. -100 kB/page. |
| **gutenberg** (plugin) | Gutenberg est déjà intégré à WP core, le plugin standalone n'est plus nécessaire. |
| **blockspare** | Lib de blocs inutilisée. |
| **elespare** | Lib Elementor — inutile sans Elementor. |
| **kadence-blocks** | Lib de blocs à ne garder que si utilisée sur des articles existants. |
| **timeline-block** ou **timeline-blocks** | Doublon. N'en garder qu'un si vraiment utilisé. |
| **ultimate-member** | Plus nécessaire (commentaires = natifs WP). |
| **nextend-facebook-connect** | Social login inutile. |
| **loginpress** | Personnalise la page login — luxe inutile. |
| **login-logout-menu** | Idem. |
| **one-click-demo-import** | À utiliser puis désinstaller. |
| **child-theme-configurator** | Plus besoin — le nouveau thème est autonome. |
| **duplicate-page** | Bonus dev. |
| **duplicator** + **backups-dup-lite** | Doublon — n'en garder qu'un. |
| **userfeedback-lite** | À évaluer selon usage. |
| **post-types-order** | À évaluer. |
| **formidable** | Lourd — on a déjà notre formulaire Bureau des Plaintes natif. |

### À GARDER

| Plugin | Pourquoi |
|---|---|
| **wordpress-seo** (Yoast) | SEO — indispensable. |
| **donorbox-donation-form** | Demandé (collecte de dons). |
| **nitropack** *(ou LiteSpeed)* | Un seul cache — à choisir. |
| **imagify** | Compression images ; à configurer en AVIF/WebP auto. |
| **wp-mail-smtp** | Fiabilité envoi mails (plaintes, commentaires). |
| **jetpack** | ⚠️ Garder uniquement si vraiment utilisé (stats, anti-spam Akismet). Sinon retirer, c'est un gros consommateur. |
| **wpconsent-cookies-banner** | RGPD — à garder. |
| **google-site-kit** *(+ google-analytics-for-wordpress)* | ⚠️ Un seul suffit pour Analytics. |
| **hostinger**, **hostinger-ai-assistant**, **hostinger-easy-onboarding** | Spécifiques à l'hébergeur — peuvent rester. |
| **woocommerce** + **woocommerce-payments** | ⚠️ À évaluer : utilisé pour vendre ? Sinon, **supprimer**, c'est très lourd. |
| **blog2social**, **omnisend** | Marketing — garder si usage actif. |
| **jetpack-waf**, **mu-plugins** | Sécurité — garder. |

### Ordre recommandé

1. **Un plugin à la fois**, en vérifiant la home + un article après
   chaque désactivation.
2. Désactiver → tester → supprimer si OK.
3. Les gros (Elementor, Gutenberg plugin, Ultimate Member, Jetpack)
   en dernier, un par un.

---

## Phase 3 — Optimisations finales

### Supprimer les thèmes inactifs

Dans `wp-content/themes/`, garder uniquement :
- `quartier-libre-theme` (actif)
- **un** thème par défaut WP (twentytwentyfive par ex.) en secours

À supprimer : `celebnews`, `morenews`, `neve`, `newsexo`, `newsio`,
`quartier_libre` (l'ancien child).

### Configurer le cache choisi

- **NitroPack** : activer « Standard » ou « Strong » pour le média.
- Ou **LiteSpeed** : activer CSS/JS minify, Image Lazy Load, Critical
  CSS. Désactiver WP-Cron hit (utiliser cron système si possible).

### Imagify — convertir l'historique

- Onglet *Média* → « Optimize remaining images » pour convertir les
  78 Mo d'uploads en WebP.

### .htaccess — compression + cache navigateur

Vérifier dans `public_html/.htaccess` :

```
<IfModule mod_deflate.c>
  AddOutputFilterByType DEFLATE text/html text/css application/javascript image/svg+xml
</IfModule>
<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresByType image/webp "access plus 1 year"
  ExpiresByType image/jpeg "access plus 1 year"
  ExpiresByType image/png  "access plus 1 year"
  ExpiresByType text/css   "access plus 1 month"
  ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

### Mesurer

Avant/après : **pagespeed.web.dev** sur :
- `/` (home)
- un article
- `/bureau-des-plaintes/`

Objectif LCP < 2.5 s, CLS < 0.1, INP < 200 ms.

---

## Rollback

À tout moment, si un problème apparaît :
- **Thème** : réactiver `quartier_libre` (l'ancien child) dans
  *Apparence → Thèmes*. 30 secondes.
- **Plugins** : réactiver depuis *Extensions*. Si le site est cassé
  et l'admin inaccessible, renommer le plugin fautif dans
  `wp-content/plugins/` via FileZilla → WP le désactive
  automatiquement.
- **Données** : restaurer le backup Hostinger.
