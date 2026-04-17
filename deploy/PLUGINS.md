# Phase 2 — Nettoyage des plugins (le vrai gain de vitesse)

37 plugins actifs sur quartierlibre.org. La plupart des lenteurs ne
viennent pas du thème, mais de cette pile de plugins qui chargent chacun
leur CSS/JS sur **chaque page**, même quand ils ne servent pas.

Objectif : passer de **37 → 10-12 plugins** essentiels.

---

## Règle d'or

**Un plugin à la fois.** Entre chaque désactivation :
1. Ouvrir la home en navigation privée (pas de cache navigateur)
2. Ouvrir un article
3. Ouvrir `/bureau-des-plaintes/`
4. Si tout est OK → **supprimer** le plugin (pas juste désactiver)
5. Si quelque chose casse → **réactiver** et passer au suivant

Garder un backup Hostinger récent sous la main au cas où.

---

## Ordre recommandé (du plus sûr au plus risqué)

### Étage 1 — Très sûrs à supprimer

Ceux-là n'ont aucune fonction critique sur un média. À virer en premier,
c'est rapide et ça libère du poids.

- [ ] **child-theme-configurator** — servait à l'ancien thème MoreNews. Plus besoin maintenant que le thème custom est autonome.
- [ ] **duplicate-page** — outil de duplication d'articles, pratique dev mais pas en prod.
- [ ] **loginpress** — personnalisation page login. Luxe inutile.
- [ ] **login-logout-menu** — idem.
- [ ] **nextend-facebook-connect** — login Facebook. Les gens commentent en natif, c'est suffisant.
- [ ] **one-click-demo-import** — n'a plus aucune utilité, le site est en ligne.
- [ ] **userfeedback-lite** — popup de feedback. Sauf usage actif, supprimer.
- [ ] **ultimate-member** — gestion de membres. Non nécessaire pour commentaires/partage.
- [ ] **post-types-order** — tri custom d'articles. Rarement utile.

**Gain estimé** : -30 à -50 ko par page.

### Étage 2 — Doublons à dédoubler

Chaque doublon = deux plugins qui font la même chose en se marchant dessus.
**Garder un seul** dans chaque paire.

- [ ] **Backups** : `duplicator` OU `backups-dup-lite` → **garder `duplicator`** (plus complet), supprimer l'autre.
- [ ] **Timeline blocks** : `timeline-block` OU `timeline-blocks` → vérifier lequel est vraiment utilisé dans vos articles, supprimer l'autre.
- [ ] **Analytics** : `google-site-kit` OU `google-analytics-for-wordpress` (MonsterInsights) → **garder Site Kit** (officiel Google, plus léger). Supprimer MonsterInsights.

**Gain estimé** : -20 ko par page.

### Étage 3 — Page builders (le gros morceau)

⚠️ **Attention** : si des pages existantes ont été construites avec
Elementor, elles vont perdre leur mise en forme. Vérifier d'abord :
WP Admin → *Pages* → voir si des pages affichent le bouton "Modifier
avec Elementor".

- [ ] Si aucune page ne dépend d'Elementor : **supprimer `elementor`**.
  → -100 ko par page, énorme gain.
- [ ] **gutenberg** (plugin) — Gutenberg est déjà intégré à WP core depuis la 5.0. Le plugin standalone n'est plus nécessaire. Supprimer.
- [ ] **blockspare** — lib de blocs Gutenberg tierce. Supprimer sauf usage actif.
- [ ] **elespare** — lib d'addons Elementor. Supprimer si Elementor l'est.
- [ ] **kadence-blocks** — à ne garder que si des articles utilisent ses blocs spécifiques.

**Si une mise en forme casse** : réinstaller le plugin temporairement,
refaire la page en blocs Gutenberg natifs (colonne / image / groupe / etc.),
puis supprimer à nouveau.

**Gain estimé** : -100 à -200 ko par page.

### Étage 4 — Cache : n'en garder qu'UN

Vous avez **trois** plugins de cache en même temps :
`NitroPack` + `autoptimize` + `litespeed-cache`. **Ils se marchent dessus**
et peuvent même *ralentir* le site.

**Choix** :
- ✅ **NitroPack** : SaaS très efficace, gratuit jusqu'à 5 000 pages vues/mois. Bon choix si trafic modéré.
- ✅ **LiteSpeed Cache** : si l'hébergement Hostinger tourne sur serveur LiteSpeed (vérifier dans hPanel). Puissant, gratuit, pas de limite.

→ **Garder un seul**. Supprimer les deux autres.

Ordre de suppression :
- [ ] **autoptimize** — à supprimer en premier (doublon pur).
- [ ] Choisir **NitroPack** OU **LiteSpeed** — supprimer celui qu'on ne garde pas.

Après suppression, **purger le cache** du plugin restant.

**Gain estimé** : stabilité + pas de double-minification qui casse des scripts.

### Étage 5 — Lourds, à évaluer

- [ ] **jetpack** — ~200 ko. Utilisé pour quoi ? Akismet ? Stats ?
  - Si uniquement Akismet → installer **Akismet** seul (module), virer Jetpack.
  - Si uniquement stats → Site Kit fait déjà le job, virer Jetpack.
  - Si usage actif (partage social auto, etc.) → garder.
- [ ] **woocommerce** + **woocommerce-payments** — très lourd (~300 ko). Vous vendez quelque chose ? Si **non**, supprimer les deux.
  *Note* : les dons passent par **Donorbox**, pas WooCommerce. Donc WC est probablement inutile.
- [ ] **formidable** — constructeur de formulaires. Le formulaire Bureau des Plaintes est en natif dans le thème. Autres formulaires ? Si non, supprimer.
- [ ] **blog2social** — auto-post vers les réseaux. Garder si utilisé.
- [ ] **omnisend** — marketing email. Garder si utilisé.

---

## À garder (essentiels)

| Plugin | Rôle |
|---|---|
| **wordpress-seo** (Yoast) | SEO indispensable |
| **donorbox-donation-form** | Dons (votre choix) |
| **wp-mail-smtp** | Fiabilité envoi mails (plaintes, commentaires) |
| **imagify** | Compression images auto |
| **wpconsent-cookies-banner** | RGPD |
| **google-site-kit** | Analytics officiel Google |
| **hostinger** + variantes | Spécifiques à l'hébergeur, laisser |
| **nitropack** *ou* **litespeed-cache** | Un seul des deux |
| **akismet** (à installer si pas déjà) | Anti-spam commentaires |

---

## Après le nettoyage

### 1. Purger tous les caches
- Cache navigateur (Ctrl+Shift+Suppr)
- Cache plugin (NitroPack ou LiteSpeed → Purge tout)
- Cache Cloudflare si présent

### 2. Mesurer
- <https://pagespeed.web.dev/> sur :
  - `https://quartierlibre.org/`
  - un article récent
  - `/bureau-des-plaintes/`
- Screenshot du score **avant/après** pour documenter le gain.

### 3. Surveiller 48 h
- Vérifier que les inscriptions / commentaires / formulaires marchent.
- Vérifier Search Console → *Couverture* pour voir si Google détecte des erreurs.

### Objectifs Core Web Vitals
- **LCP** (Largest Contentful Paint) < 2.5 s
- **CLS** (Cumulative Layout Shift) < 0.1
- **INP** (Interaction to Next Paint) < 200 ms

---

## Si quelque chose casse

### Admin accessible
*Extensions* → réactiver le plugin qui vient d'être désactivé.

### Admin inaccessible (white screen / erreur 500)
1. Connexion FTP via FileZilla
2. Aller dans `public_html/wp-content/plugins/`
3. **Renommer** le dossier du plugin fautif (ex. `elementor` → `elementor-OFF`).
   → WP le considère comme désinstallé et redémarre sans.
4. L'admin redevient accessible → supprimer proprement depuis l'admin.

### Dernière chance : restaurer backup
hPanel Hostinger → *Fichiers → Sauvegardes* → restaurer celle de juste avant.

---

## Checklist finale

- [ ] Étage 1 fait (9 plugins)
- [ ] Étage 2 fait (3 doublons)
- [ ] Étage 3 fait (page builders) ⚠️ le plus gros gain
- [ ] Étage 4 fait (caches — 1 seul)
- [ ] Étage 5 évalué et traité
- [ ] Thèmes inactifs supprimés (celebnews, morenews, neve, newsexo, newsio, quartier_libre)
- [ ] Core Web Vitals mesurés avant/après
- [ ] 48 h de surveillance OK
