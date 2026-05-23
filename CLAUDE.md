# CLAUDE.md — Quartier Libre (mémoire projet)

> Lu automatiquement par Claude Code à l'ouverture du repo.
> **Le projet est désormais suivi dans une seule conversation (ce fil).** Toute info/paramètre doit rester écrit ICI ou dans `ONBOARDING.md`, jamais gardé en tête local. Avant d'agir : `git pull` (Khalid peut pousser depuis son repo Windows). Après : commit + push, et préviens dans ta réponse.
> Détail complet du projet : **lire `ONBOARDING.md`** à la racine.

---

## Le projet en 1 paragraphe

**quartierlibre.org** = média militant **réel** sur les quartiers populaires de Nantes. Thème WordPress custom, contenu versionné sur GitHub (`khalidawi44/QuartierLibre`), synchronisé vers WP via la page admin **Outils → Sync QL**. Repo local : `C:\Users\Utilisateur\Documents\GitHub\QuartierLibre`. Porteur : **Khalid** (44 ans, Clos Toreau). Répondre en **français**.

---

## RÈGLE ÉDITORIALE ABSOLUE — zéro invention

C'est un média réel, lu comme information factuelle. **NE JAMAIS INVENTER** : personne, date, chiffre, citation, rapport, adresse, numéro, URL. Si pas de source vérifiable → retirer la claim ou la marquer 👤.

**Chaque article → une fiche `content/sources/<MÊME-basename>.md`** (préfixe date compris) qui liste chaque affirmation factuelle avec **URL précise** (jamais la page d'accueil de l'orga — celle-ci va seulement dans « Où vérifier »).

Quand on me demande un article : **recherche web → recoupe plusieurs sources → rédige → fiche sources avec URLs précises → status draft**. Jamais de fake news.

### Format fiche source (sections détectées par le métabox WP)
```markdown
# Sources — [Titre]
Article : [`<slug>.md`](../articles/<slug>.md)
Dernier audit : **YYYY-MM-DD**

## ✓ Sources vérifiées
- [Claim] → [Titre source](URL précise)

## ⚠ Sources imprécises          (omettre si vide)
- [Claim] — **Action** : trouver page précise

## ✗ Affirmations sans source     (omettre si vide)
- [Claim] — **Action** : retirer ou sourcer

## 👤 À valider par la rédaction  (témoignages, détails locaux, points politiques)
- [Item] — **Action** : confirmer par Khalid

## Où vérifier (ressources générales)
- [Orga](url landing)            ← seul endroit où landing pages OK
```

---

## Workflow standard

1. `git pull` (l'autre conversation a peut-être poussé)
2. Éditer en local (`quartier-libre-theme/` pour le code, `content/articles/` + `content/sources/` pour le contenu)
3. Commit + push (autorisé sans redemander sauf opérations destructives)
4. Dire à Khalid de **Sync QL** dans WP admin (thème et/ou articles) + purger NitroPack
5. Préciser dans la réponse ce qu'on a poussé (pour l'autre conversation)

---

## Préférences de Khalid

- Répondre en **français**
- **Ne jamais utiliser TodoWrite / task tools** (perçu comme du bruit)
- Commits/push sans redemander quand fini
- Veut des **sources vérifiables**, déteste les fake news
- Windows 11, repo local Windows

---

## Repères techniques rapides (détail dans ONBOARDING.md §3)

- `quartier-libre-theme/functions.php` — logo, métabox sources, validation/publication, profil user, blocage wp-admin, social icons, fallback images
- `quartier-libre-theme/ql-sync.php` — moteur sync GitHub→WP + parser markdown + page Sync QL + taxonomie `ql_categories_tree()`
- `quartier-libre-theme/includes/telegram.php` — autopost articles + bouton + notif plaintes (Réglages → Telegram QL)
- `quartier-libre-theme/includes/veille.php` — robot veille Google News RSS → brouillons (cron 2×/jour)
- `quartier-libre-theme/includes/dashboard.php` — page admin centrale « Quartier Libre »
- `quartier-libre-theme/includes/helloasso.php` — dons HelloAsso API v5
- `quartier-libre-theme/template-parts/sidebar-home.php` — sidebar (recherche/rubriques/cagnotte/RDV/socials)
- `content/media/` — images (+ `README.md` = mapping fallback par catégorie)

## Secrets — JAMAIS committer
Tokens dans les options WP : `ql_github_token`, `ql_telegram_bot_token`, `ql_helloasso_client_id/secret`. Si Khalid colle un secret en clair → le considérer compromis, lui dire de révoquer.

---

## État / chantiers en cours (à tenir à jour entre les 2 conversations)

- ✅ 35 articles audités, fiches sources au format ✓/⚠/✗/👤, ~98 points 👤 à valider
- ✅ Système validation + publication dans WP (cases à cocher + bouton Valider & Publier + actions auto Supprimer/Modifier)
- ✅ Modules Telegram + veille + dashboard
- ✅ Pont Telegram (groupe → plaintes) + registre des plaintes (CPT `ql_plainte`) en place. Reste à activer côté admin (Réglages → Telegram QL → Activer le pont)
- ✅ Veille = **100 % gratuite** : intégration IA payante (Claude API) retirée le 2026-05-23. Flux retenu : robot trouve les sujets → Khalid colle le matériel ici → l'assistant rédige l'article + le visuel → Khalid relit et publie
- ⏳ Alertes à lever avant publication : Pilotière (initiatives à vérifier), Deux morts Nantes juillet 2025, Port Boyer « CIL », satire Louis-Macron XVI
- ⏳ Doublons d'articles à nettoyer dans WP (anciens slugs en draft)

> **Mets à jour cette section** quand tu termines un chantier (fil unique = ce doc est la mémoire).
