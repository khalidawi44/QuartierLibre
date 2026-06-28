# Quartier Libre — Document de passation (handoff)

> Pour toute IA / dev qui reprend le projet. Lis ce fichier en entier avant d'agir.
> Dernière mise à jour : 2026-05-23.

---

## 1. C'est quoi le projet

**quartierlibre.org** = média militant **réel** sur les quartiers populaires de Nantes (Bellevue, Malakoff, Clos Toreau, Dervallières, Bottière-Pin Sec, Breil, Bout des Landes, Port Boyer, Halvêque, Ranzay, Pilotière). Sujets : logement social / bailleurs, violences policières, surveillance, services publics, luttes locales, + national (politique française) et international (Gaza, Soudan).

**Porteur** : Khalid (44 ans, habitant du Clos Toreau, Nantes sud). Journaliste militant.

**Ton éditorial** : inspiré de Contre-Attaque (contre-attaque.net) — rouge/noir, percutant, militant.

---

## 2. Architecture technique

### Stack
- **WordPress** hébergé chez Hostinger, sur `quartierlibre.org`
- **Thème custom** « Quartier Libre » (autonome, zéro page builder)
- **Optimisation** : NitroPack (cache agressif — penser à le purger après chaque sync)
- **Contenu** versionné sur **GitHub** : `khalidawi44/QuartierLibre`, branche `main`
- **Repo local** : `C:\Users\Utilisateur\Documents\GitHub\QuartierLibre`

### Flux de travail
1. On édite les fichiers en local (thème + articles markdown)
2. On commit + push sur GitHub
3. Dans WP admin : **Outils → Sync QL** → 2 boutons :
   - **Synchroniser le thème** (récupère `quartier-libre-theme/` depuis GitHub et écrit sur le serveur)
   - **Synchroniser les articles (.md)** (importe `content/articles/*.md` comme articles WP + `content/sources/*.md` comme méta de vérification)
4. Purger le cache NitroPack

> ⚠️ Le sync GitHub a besoin d'un **token** (Personal Access Token) sinon rate limit à 60 req/h. Stocké dans l'option WP `ql_github_token`, configurable depuis la page Sync QL (cadre 🔑 Token GitHub).

---

## 3. Arborescence des fichiers

```
QuartierLibre/
├── ONBOARDING.md                  ← ce fichier
├── quartier-libre-theme/          ← LE THÈME WORDPRESS
│   ├── functions.php              ← cœur : logo, sources métabox, social icons,
│   │                                 profil utilisateur, blocage wp-admin,
│   │                                 validation/publication, fallback images
│   ├── ql-sync.php                ← moteur sync GitHub→WP + page admin "Sync QL"
│   │                                 + parser markdown→HTML + upload images
│   ├── header.php                 ← header pleine largeur, logo gauche, menu, user-menu
│   ├── footer.php                 ← footer + logo + plainte-popup
│   ├── front-page.php             ← homepage layout 70/30 (contenu + sidebar droite)
│   ├── single.php                 ← article : sidebar gauche 30% + contenu 70%
│   ├── page.php                   ← pages standard : sidebar gauche
│   ├── archive.php / category.php / search.php  ← listes avec sidebar gauche
│   ├── templates/
│   │   ├── page-a-propos.php       ← À propos (Khalid + rédaction, SANS sidebar)
│   │   ├── page-rubriques.php      ← /rubriques/ gros titres colorés (SANS sidebar)
│   │   ├── page-tous-articles.php  ← /tous-les-articles/ filtres + pagination
│   │   ├── page-soutenir.php       ← /soutenir/ dons PayPal+HelloAsso + FAQ
│   │   ├── page-connexion.php      ← /connexion/ login+inscription + boutons sociaux
│   │   ├── page-mon-profil.php     ← /mon-profil/ espace user custom (remplace wp-admin)
│   │   └── page-bureau-plaintes.php
│   ├── template-parts/
│   │   ├── hero-carousel.php       ← carrousel une homepage
│   │   ├── sidebar-home.php        ← LA sidebar (recherche/rubriques/cagnotte/RDV/socials)
│   │   ├── card-article.php        ← carte article (ratio image 3/2)
│   │   ├── section-category.php    ← bloc section par catégorie
│   │   ├── soutenir.php            ← encart dons inline (homepage)
│   │   ├── plainte-popup.php       ← Bureau des plaintes (modal flottante)
│   │   └── ...
│   ├── includes/
│   │   ├── helloasso.php           ← intégration HelloAsso API v5
│   │   ├── plainte-variants.php    ← variantes Bureau des plaintes par contexte
│   │   ├── telegram.php            ← Telegram : publication auto des articles sur
│   │   │                             le canal + notif rédaction (plaintes) + page
│   │   │                             "Réglages → Telegram QL" + helper bouton +
│   │   │                             webhook "pont" groupe Telegram → plaintes
│   │   ├── plaintes.php            ← Registre des plaintes (CPT ql_plainte) :
│   │   │                             formulaire du site + messages groupe Telegram
│   │   ├── dashboard.php           ← Tableau de bord central "Quartier Libre"
│   │   │                             (chiffres, priorités, abonnés Telegram, outils)
│   │   ├── veille.php              ← Robot de veille Google Actualités → brouillons
│   │   │                             amorce (menu sous le Tableau de bord)
│   │   └── atelier.php             ← Atelier de rédaction : matière collée par sujet
│   │                                 → bouton « Copier le brief pour l'assistant »
│   │                                 → l'assistant rédige (gratuit). Statut par dossier
│   ├── assets/css/main.css         ← TOUT le CSS (~4000+ lignes)
│   ├── assets/js/main.js           ← TOUT le JS (burger, carrousel, modals,
│   │                                 screenshots liens externes, back-to-top, etc.)
│   └── assets/images/              ← logo.svg/png fallback thème
├── content/
│   ├── articles/                  ← 35 articles en markdown + frontmatter YAML
│   │   └── YYYY-MM-DD-slug.md
│   ├── sources/                   ← 1 fiche source par article (MÊME basename)
│   │   └── YYYY-MM-DD-slug.md
│   └── media/                     ← images (jpg/svg) + README.md (mapping fallback)
```

**RÈGLE CRUCIALE** : le fichier source `content/sources/X.md` doit avoir **exactement le même basename** que l'article `content/articles/X.md` (préfixe date YYYY-MM-DD compris), sinon le sync ne les associe pas.

---

## 4. Format d'un article (frontmatter YAML)

```yaml
---
title: "Titre de l'article"
slug: "slug-url"
category:                    # array — toutes les catégories pertinentes
  - infos-locale
  - bellevue
  - luttes
  - repression
primary_category: repression # badge principal affiché (override le choix auto)
tags:
  - tag1
  - tag2
excerpt: "Résumé court (meta description + chapô)"
featured_image: "content/media/x.jpg"        # OU featured_image_url (URL WP)
bq_background: "content/media/x.jpg"          # fond des blockquotes témoignages
event_date: "2026-05-01"     # SI rendez-vous → apparaît dans widget agenda
status: "draft"              # draft | publish | pending
force_status: "draft"        # force le passage en draft même si déjà publié
date: "2026-04-20 09:00:00"
author: "karima-benali"      # login d'un des 13 auteurs (voir §6)
une: true                    # featured sur la home
plainte_variant: "logement"  # variante du Bureau des plaintes
---
```

### Mécanismes spéciaux du frontmatter
- `force_status: "draft"` → rétrograde un article publié (pour corriger une erreur depuis le repo)
- `trash: true` → met l'article à la corbeille via sync
- `event_date: YYYY-MM-DD` → l'article apparaît dans le widget « Rendez-vous » de la sidebar (si date future)
- `primary_category: slug` → force le badge affiché (sinon auto : multi-quartiers → thème transversal)

### Taxonomie (catégories valides)
- **infos-locale** → bellevue, malakoff, dervallieres, clos-toreau, bottiere-pin-sec, breil, bout-des-landes, port-boyer, halveque, ranzay, pilotiere, transports, autres-villes
- **france** → politique, justice, fait-divers, economie, societe
- **international** → guerre, genocide, famine, resistance
- **luttes** → mobilisations, repression, solidarite, logement
- **histoire** (pas de sous-cat)

Source unique : fonction `ql_categories_tree()` dans `ql-sync.php`.

---

## 5. RÈGLE ÉDITORIALE ABSOLUE (la plus importante)

**NE JAMAIS RIEN INVENTER.** C'est un média réel, lu comme info factuelle.

Interdits sans demande explicite :
- Personne inventée (même pseudonyme/initiales composites)
- Date d'événement inventée (manif, AG, procès)
- Chiffre inventé (morts, budgets, %)
- Citation inventée (politicien, fonctionnaire, habitant)
- Rapport/communiqué daté sans source vérifiable
- Adresse, numéro de téléphone, URL inventés

**Pour chaque article → une fiche `content/sources/<slug>.md`** qui liste chaque affirmation factuelle avec sa source (URL **précise**, pas la page d'accueil de l'orga).

### Format standard d'une fiche source
```markdown
# Sources — [Titre]

Article : [`<slug>.md`](../articles/<slug>.md)

Dernier audit : **YYYY-MM-DD**

## ✓ Sources vérifiées
- [Claim paraphrasée] → [Titre source](URL précise)

## ⚠ Sources imprécises          (n'apparaît que s'il y en a)
- [Claim] — lien trop général — **Action** : trouver la page précise

## ✗ Affirmations sans source     (n'apparaît que s'il y en a)
- [Claim] — **Action** : retirer ou sourcer

## 👤 À valider par la rédaction  (témoignages, détails locaux, points politiques)
- [Témoignage anonyme] — **Action** : confirmer recueilli par Khalid

## Où vérifier (ressources générales)
- [Organisation](url landing)   ← SEUL endroit où les landing pages sont OK
```

**URL précise obligatoire** : si on cite un rapport MSF, lier la page du rapport (`msf.org/sudan-msf-forced-halt-...`), PAS `msf.org/sudan`.

Si pas de source trouvée après recherche web → retirer la claim ou la marquer 👤.

Ces règles sont aussi dans la mémoire globale de Claude : `C:\Users\Utilisateur\.claude\CLAUDE.md`.

---

## 6. La rédaction (13 auteurs fictionnels)

Définis dans `ql_authors_roster()` (ql-sync.php). **Affichage = PRÉNOM SEUL** (pas de nom de famille). Logins :
- `aissata-diallo` (Bellevue), `younes-boukhris` (Malakoff), `karima-benali` (Dervallières), `soraya-messaoudi` (Clos Toreau), `mehdi-haddad` (Bottière-Pin Sec), `fatou-traore` (Breil), `samir-toure` (Bout des Landes), `lea-marchand` (Port Boyer), `naima-ouedraogo` (Halvêque), `amadou-kone` (Ranzay), `sofia-bensalem` (Pilotière)
- Correspondants : `rachida-ben-arfa` (international), `julien-moreau` (national)
- Khalid = compte admin réel WordPress (pas dans le roster)

---

## 7. Système de vérification des sources (dans WP admin)

C'est le gros chantier des dernières sessions. Tout dans `functions.php`.

### Méta-box dans l'éditeur d'article
Affiche 4 cases chiffrées : **✓ vérifié · ⚠ imprécis · ✗ manquant · 👤 à valider**.
- Lit le post_meta `_ql_sources_md` (contenu de la fiche source, injecté au sync)
- Parser : `ql_parse_sources_sections()` (détecte les sections par emoji/mots-clés)

### Page « Outils → Sources QL »
- Dashboard 4 cases filtrables (Total / 100% vérifié / À corriger / Problème)
- Tableau verdict par article (vert/orange/rouge/gris)
- Vue « 📝 Tout ce qui reste à faire » : liste consolidée des ⚠/✗/👤 de tous les articles

### Workflow de validation + publication
Pour chaque point **👤**, 3 boutons :
- **✓ Valider tel quel** — marque OK
- **✎ Modifier** — saisir une correction → **remplace auto le passage dans l'article** (post_content)
- **✗ Supprimer** — **retire auto le passage de l'article** (avec confirmation)

Le bouton **« Valider & Publier maintenant »** (vert) s'active SEULEMENT quand : 0 ⚠ + 0 ✗ + tous les 👤 traités + article pas déjà publié.

Décisions persistées dans post_meta `_ql_item_decisions`. Reset auto si la fiche change (hash MD5).

> ⚠️ Les modifs auto (✎/✗) agissent sur le **post_content WordPress**, PAS sur le `.md` GitHub. Pour rendre permanent : reporter le changement dans le `.md` puis re-sync. Sinon le prochain sync réécrase.

---

## 8. Fonctionnalités du thème (récap)

| Feature | Où | Note |
|---|---|---|
| Sync GitHub→WP | ql-sync.php | + token, gestion rate-limit, messages d'erreur détaillés |
| Logo | functions.php `ql_resolve_logo_url()` | cascade : option ql_logo_url → custom_logo → recherche média "logo" → fichier thème. Actuel : logo_home.png, 72px, collé à gauche 14px |
| Homepage 70/30 | front-page.php | contenu gauche 70% + sidebar DROITE 30% |
| Sidebar | template-parts/sidebar-home.php | 5 widgets : recherche, rubriques, cagnotte (dons), rendez-vous (event_date), réseaux sociaux. Sticky, scrollbar masquée |
| Articles/pages | single/page/archive/category/search.php | sidebar GAUCHE 30% + contenu 70% |
| Dons | sidebar + page-soutenir | PayPal SDK (client_id en option) + HelloAsso API v5. PAS de mention "déductible" (QL pas reconnu d'intérêt général) |
| Liens externes | main.js | sur mobile/tablette → capture d'écran (WordPress mShots) au lieu de quitter le site ; desktop → popup |
| Blockquotes témoignages | markdown `>!` | `>! texte` = encart pleine largeur fond image ; `>` = citation inline normale (politicien/média) |
| Images par défaut | ql-sync.php `$fallback_map` | si pas de featured_image → image thématique selon primary_category |
| Réseaux sociaux | sidebar-home.php | Facebook (profile.php?id=61578685711984), Instagram (@quartierlibre44), Snapchat (t.snapchat.com/2lbKw2lU), Telegram (suit le canal réglé dans *Telegram QL* via `ql_telegram_public_url()`), RSS |
| Profil utilisateur | page-mon-profil.php | upload photo custom, change infos/mdp. wp-admin BLOQUÉ pour non-éditeurs (redirect /mon-profil/) |
| Connexion | page-connexion.php | email/mdp + boutons Google/Facebook/Apple (compatibles plugin Nextend Social Login à installer) |
| Bureau des plaintes | plainte-popup.php + plainte-variants.php | modal flottante, variantes par sujet (immigration/police/logement/etc.) |
| **Registre des plaintes** | includes/plaintes.php | CPT privé `ql_plainte` (menu *Quartier Libre → Bureau des plaintes*). Stocke les plaintes du formulaire site ET les messages du groupe Telegram. Colonnes : source (Site/Telegram), quartier, contact |
| **Pont Telegram→site** | includes/telegram.php | Webhook REST `quartierlibre/v1/telegram-webhook` (jeton secret). Active via *Réglages → Telegram QL → Activer le pont* : les messages du groupe « ID rédaction » deviennent des plaintes. ⚠️ Tant que le webhook est actif, `getUpdates` (bouton « Détecter les conversations ») est désactivé par Telegram |
| Bouton retour haut | main.js | bas-gauche, après 400px scroll |
| **Telegram** | includes/telegram.php | Page *Réglages → Telegram QL* (token bot + ID canal + lien public + ID rédaction). Publie auto chaque **nouvel** article sur le canal (`transition_post_status`, anti-doublon via meta `_ql_telegram_sent`). Notifie la rédaction des plaintes. Prérequis : bot @BotFather **admin du canal** |
| **Tableau de bord** | includes/dashboard.php | Menu « Quartier Libre » : chiffres clés (articles/brouillons/commentaires/**abonnés Telegram**), priorités « chef de rédaction », liens outils |
| **Robot de veille** | includes/veille.php | Interroge Google Actualités 2×/jour (cron, ~18 requêtes × 20 résultats) → suggestions dans le tableau de bord → **brouillon amorce** (titre + lien source) en 1 clic. La rédaction complète (analyse + visuel) se fait à la main / via l'assistant. **Pas d'IA payante** (choix assumé : outils gratuits uniquement). Sous-menu du Tableau de bord |
| **Atelier de rédaction** | includes/atelier.php | Menu *Quartier Libre → ✍️ Atelier de rédaction*. Reprend les sujets du robot ; pour chacun : zone **« matière à coller »** + **statut** (nouveau/matière prête/rédigé/publié) + bouton **« Copier le brief pour l'assistant »** (assemble sujet + lien + matière + consignes éditoriales + format de sortie). Khalid colle le brief dans la conversation Claude → l'assistant rend l'article + fiche sources + visuel → push GitHub. **100 % gratuit, aucune API.** Dossiers stockés dans l'option `ql_atelier_dossiers` (survivent à l'élagage de la veille) |

---

## 8bis. Modules d'automatisation (includes/) — ajoutés par la 2e conversation

### `includes/telegram.php` — Intégration Telegram
- **Autopost** : chaque article publié est envoyé sur le canal Telegram (titre + image + lien)
- **Bouton** « Rejoins-nous sur Telegram » via `ql_telegram_button()`
- **Notif rédaction** : quand un témoignage arrive (Bureau des plaintes), notification au chat admin
- **Réglages** : WP admin → **Réglages → Telegram QL**
- **Options WP** : `ql_telegram_bot_token`, `ql_telegram_channel_id`, `ql_telegram_channel_url`, `ql_telegram_admin_chat_id`, `ql_telegram_autopost` (1/0), `ql_telegram_notify_plaintes` (1/0)
- **Prérequis** : créer un bot via @BotFather (token), l'ajouter admin du canal. Hook : `transition_post_status` (publish) + `ql_plainte_received`

### `includes/veille.php` — Robot de veille (sans IA)
- Surveille Google Actualités RSS (`news.google.com/rss/search`) sur des requêtes ciblées (manifs, logement/HLM, sécurité, politique locale Nantes)
- Trouve des sujets récents → **propose de créer un brouillon** pré-rempli (titre + lien source + rappel relecture). **Pas de publication auto.**
- Tourne **2×/jour en cron** (`ql_veille_cron`) + bouton « Lancer maintenant »
- **Pas de clé API**
- **Options WP** : `ql_veille_queries` (requêtes perso), `ql_veille_enabled` (1/0), `ql_veille_items` (résultats), `ql_veille_last_run`
- Affiché dans le tableau de bord + page de réglages dédiée

### `includes/dashboard.php` — Tableau de bord central
- Menu WP **« Quartier Libre »** (tout en haut, icône mégaphone)
- Chiffres clés (articles, brouillons, commentaires, abonnés Telegram)
- « Chef de rédaction » : priorités calculées sur l'état réel du site
- Liens directs vers tous les outils du thème (Sync QL, Sources QL, Veille, Telegram…)

### `includes/atelier.php` — Atelier de rédaction (flux article gratuit)
- Menu **« Quartier Libre → ✍️ Atelier de rédaction »**
- Reprend les sujets du robot de veille. Deux blocs : **Dossiers en cours** (ceux où tu as déjà collé de la matière) et **Nouveaux sujets du robot**
- Par sujet : zone **« matière à coller »** (texte/dates/chiffres/citations/liens) + **statut** + bouton **« Copier le brief pour l'assistant »**
- Le brief (presse-papier) contient : sujet, lien source, matière collée, consignes éditoriales QL, format de sortie attendu (article .md + fiche sources + visuel) et la règle « ne rien inventer ». Khalid le colle dans la conversation Claude → l'assistant rédige et pousse sur GitHub
- **Aucune IA payante, aucune clé** : WordPress ne fait qu'organiser et fabriquer le brief
- **Option WP** : `ql_atelier_dossiers` (matière + statut par sujet, indépendant de `ql_veille_items`)

---

## 9. État actuel & tâches en cours

### Fait
- 35 articles audités, fiches sources au format standard (✓/⚠/✗/👤)
- Toutes les fiches renommées pour matcher les basenames d'articles
- ~98 points 👤 ajoutés (témoignages + détails locaux à confirmer)
- Système validation/publication en place dans WP

### À surveiller (alertes de l'audit)
1. **Pilotière** : initiatives d'auto-organisation (aide aux devoirs 2019, frigo 2022...) à vérifier — possiblement inventées
2. **Deux morts à Nantes juillet 2025** : les 2 décès précis à confirmer par maraudes réelles
3. **Port Boyer « CIL »** : probable confusion avec Action Logement
4. **Louis-Macron XVI** : gouvernement satirique — vérifier que le ton reste lisible
5. Plusieurs articles ont `force_status: draft` (Darfour, délit solidarité, 1er mai) → restent en brouillon tant que Khalid n'a pas validé

### Doublons à nettoyer dans WP
Certains articles ont été réécrits avec un nouveau slug → anciens doublons en draft à mettre à la corbeille manuellement (ex : ancien "Darfour 2026" vs nouveau "Darfour : l'ONU confirme").

### ⚠️ Page « Connecteurs » blanche/noire (HORS PROJET — ne pas chercher dans le repo)
La page `wp-admin/options-connectors.php` (onglet « Connecteurs ») s'affiche **vide**.
Vérifié : **aucune** trace de « connector / connecteur » dans le dépôt → ce n'est **pas**
le thème, ni un module QL. C'est une page d'un **plugin** (ou de Hostinger).
Cause : erreur **JavaScript** en console (`spécificateur « @wordpress/boot »… n'a pas été
remappé`) → l'« import map » des modules WP est cassée/supprimée, donc le JS ne démarre pas
et la zone reste vide. Suspect n°1 : **NitroPack** (delay/defer JS, optimisation HTML).
- À faire côté WP (pas côté code) : purger NitroPack → si toujours vide, désactiver
  NitroPack et recharger ; si ça remarche, exclure le wp-admin du delay/defer JS. Sinon
  identifier le plugin qui crée ce menu (désactivation une par une).
- Sans rapport avec Telegram : régler le canal = *Réglages → Telegram QL* (OK), articles
  auto = *Quartier Libre → Tableau de bord* (robot de veille), publication auto = telegram.php.

### Charte graphique affiches QL (style Contre-Attaque)
Référence visuelle validée par Khalid = **Contre-Attaque** (contre-attaque.net + posts Facebook).
Règles à appliquer pour CHAQUE affiche d'article :

1. **Photo en fond plein cadre 1600×900** (16:9). Pas de bordure rouge, pas de badge encadré QL.
2. **Photo bien visible** : voile sombre **léger** (gradient 0.20→0.55 max, jamais ≥0.85). Le lecteur doit voir l'image, le texte reste lisible grâce à la taille des polices + ombre éventuelle, PAS en noyant la photo sous du noir.
3. **Tout le texte centré** (`text-anchor="middle"`, x=800). Pas d'alignement à gauche isolé.
4. **Surtitre lieu** en **Lobster italique jaune** (`#f5c518`), taille ~86, **juste au-dessus** du titre principal (≤60px d'écart, pas en haut isolé). Ex : « À Nantes », « À Malakoff », « En Loire-Atlantique ».
5. **Titre principal** en **Anton MAJUSCULES blanc gras**, taille 78-110 selon la longueur. Mots-clés / chiffres-choc surlignés : **fond jaune `#f5c518` + texte noir `#0a0a0a`**.
6. **Sous-info** en Anton blanc plus petit (32-40), centré, sous le titre (la « signature politique » de l'article, ex : « ROLLAND-DARMANIN ONT SIGNÉ EN OCT. 2022 »).
7. **Pied** en `monospace` 18, gris `#aaa`, centré (acteurs / contexte technique).
8. **Pas de logo QL visible** dans l'image (la signature est dans la légende du post sur les réseaux).

Le fichier doit être nommé avec **`affiche`** dans le nom (ex : `2026-06-28-cra-nantes-affiche.png`) pour que `single.php` le détecte comme affiche composée et ne superpose pas le titre WP par-dessus.

### Polices QL pour les affiches (Anton, Lobster, PinkBlue)
Charte affiches QL = **Anton** (titre condensé bold), **Lobster** (surtitre italique cursive), **PinkBlue** (brush — provisoirement Permanent Marker en attendant le vrai .ttf). Les TTF Anton et Lobster sont versionnés dans `content/fonts/`. PinkBlue se reconstruit depuis Permanent Marker (Apache) en renommant la famille via fontTools.

**⚠ Reset d'env = polices à réinstaller** (l'environnement de rendu est éphémère, `~/.fonts` est wipé). Procédure :
```bash
mkdir -p ~/.fonts
cp content/fonts/Anton-Regular.ttf content/fonts/Lobster-Regular.ttf ~/.fonts/
pip install --quiet fonttools
curl -sL -o /tmp/PM.ttf "https://github.com/google/fonts/raw/main/apache/permanentmarker/PermanentMarker-Regular.ttf"
python3 -c "from fontTools.ttLib import TTFont; f=TTFont('/tmp/PM.ttf'); n=f['name']; [setattr(r,'string','PinkBlue') for r in n.names if r.nameID in (1,4,6,16)]; [n.setName('PinkBlue',i,3,1,0x409) for i in (1,4,6)]; [n.setName('PinkBlue',i,1,0,0) for i in (1,4,6)]; f.save('/root/.fonts/PinkBlue-Regular.ttf')"
fc-cache -f ~/.fonts
fc-list | grep -iE "anton|lobster|pinkblue"   # doit lister les 3
```
Pour rendre un SVG fidèle aux polices QL avec cairosvg : utiliser **font-family="Anton" / "Lobster" / "PinkBlue"** (noms exacts, simples — pas de fallback list).

### Lien Telegram du widget « Nous suivre »
L'ancien canal `t.me/nantesrevoltee` (Nantes Révoltée) a été remplacé par le **canal QL** : **<https://t.me/quartierlibre44>** (à régler dans *Réglages → Telegram QL → Lien public du canal*).
Le widget suit désormais automatiquement le canal réglé dans *Réglages → Telegram QL*
(`ql_telegram_public_url()` : « Lien public du canal » sinon dérivé du `@nom`). Plus aucune
URL codée en dur. Pour un canal **privé** (ID numérique `-100…`), remplir le champ
« Lien public du canal » dans Telegram QL.

---

## 10. Comptes & secrets (NE PAS committer)

- **GitHub** : repo `khalidawi44/QuartierLibre`. Token PAT à mettre dans l'option WP `ql_github_token` (jamais dans le code).
- **PayPal** : client_id public dans le code (normal, c'est du frontend).
- **HelloAsso** : client_id + secret dans options WP (`ql_helloasso_*`), slug `union-des-quartiers-libres`. Compte à activer côté HelloAsso pour les paiements API.
- **Réseaux** : voir §8.

> Si Khalid partage un token/secret en clair dans un chat → le considérer comme compromis, lui dire de le révoquer.

---

## 11. Préférences de travail de Khalid

- Répondre en **français**
- **Ne jamais utiliser TodoWrite** (perçu comme du bruit)
- Commits/push autorisés sans redemander quand un changement est fini (sauf destructif)
- Veut des **sources vérifiables** pour tout, déteste les fake news
- Bosse sur Windows 11, repo local Windows (`C:\Users\Utilisateur\...`)

---

## 12. Comment reprendre (checklist)

1. Lire ce fichier + `C:\Users\Utilisateur\.claude\CLAUDE.md` (règles éditoriales)
2. `git pull` pour avoir le dernier état
3. Pour écrire un article : recherche web → recouper → rédiger → créer la fiche source avec URLs précises → status draft
4. Jamais d'invention. Si pas de source → 👤 ou retirer
5. Commit + push → dire à Khalid de sync dans WP
6. Pour le code thème : éditer dans `quartier-libre-theme/`, commit, push, sync thème
