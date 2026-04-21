# Images des articles — Base éditoriale Quartier Libre

Déposer ici les images référencées dans les articles :

- **Image à la une** : front-matter `featured_image:` (chemin repo) ou `featured_image_url:` (URL WP)
- **Images inline** : `![alt](content/media/nom.jpg)` dans le corps

## Formats recommandés

- **`.webp`** si possible (30-50 % plus léger que JPEG)
- `.jpg` pour les photos
- `.png` pour les visuels avec transparence, infographies
- `.svg` pour les pictos et bannières typographiques

## Tailles

- Image à la une : **1600×900 px minimum** (le thème recadre en `ql-hero`)
- Images inline : **1200 px de large** suffit (affichage responsive)

## Fallback automatique

Tout article sans `featured_image` reçoit automatiquement une image
thématique selon sa `primary_category` (cf. `$fallback_map` dans
`quartier-libre-theme/ql-sync.php`).

Mapping actuel :

| Catégorie | Image fallback |
|---|---|
| genocide / famine / guerre / resistance | `soudan-guerre-oubliee.jpg` |
| international | `theme-international.jpg` |
| politique / france | `loi-immigration-2026.jpg` |
| justice / fait-divers | `theme-medias.jpg` |
| economie / transports | `theme-economie.jpg` |
| societe | `theme-ecologie.jpg` |
| mobilisations / solidarite / luttes / infos-locale / autres-villes | `1er-mai-2026.jpg` |
| repression | `theme-etat-policier.jpg` |
| logement | `quartier-clos-toreau.jpg` |
| histoire | `theme-histoire.jpg` |
| bellevue / malakoff / clos-toreau / bottiere-pin-sec / breil / bout-des-landes / port-boyer / halveque / ranzay / pilotiere / dervallieres | `quartier-<slug>.jpg` |

## Origine et licences des images

### Images propres à QL (rédaction)
- `actualite-*.svg` — bannières typographiques générées par la rédaction
- `quartier-*.svg` — bannières typographiques HLM générées par la rédaction
- `quartier-*.jpg` / `quartier-*-bg.jpg` — photos de quartiers (sources diverses, documentation publique)

### Images importées de Contre-Attaque (`contre-attaque.net`)

Contre-Attaque est un média militant allié qui publie ses articles avec
des images libres de reprise. Téléchargées le **20 avril 2026** :

| Fichier local | Article source | Thème |
|---|---|---|
| `theme-etat-policier.jpg` | [Vers une société de milices privées — 24/03/2025](https://contre-attaque.net/rubriques/etat-policier/) | Répression / État policier |
| `theme-ecologie.jpg` | [Plutôt la fin du monde que la fin du capitalisme](https://contre-attaque.net/rubriques/environnement/) | Écologie |
| `theme-economie.jpg` | [La taxe Zucman est-elle d'extrême gauche ?](https://contre-attaque.net/rubriques/economie/) | Économie / inégalités |
| `theme-international.jpg` | [Israël-Iran : ce que les médias français ne vous diront pas](https://contre-attaque.net/rubriques/international/) | International |
| `theme-medias.jpg` | [Mafia médiatique](https://contre-attaque.net/rubriques/chiens-de-garde/) | Médias dominants / justice |
| `theme-histoire.jpg` | [Rosa Luxemburg, révolutionnaire — 15 janvier 1919](https://contre-attaque.net/rubriques/histoire/) | Histoire politique |

Tailles : 1000×1000 px (histoire : 1000×1250).

### Autres photos reprises de Contre-Attaque (contexte plus ancien)
- `1er-mai-2026.jpg` — photo de banderole manif Nantes
- `loi-immigration-2026.jpg` — photo manif anti-loi immigration
- `nantes-videosurveillance.jpg` — photo caméra / surveillance Nantes
- `soudan-guerre-oubliee.jpg` — photo Soudan / Darfour

## Attribution dans les articles

Pour les images Contre-Attaque, citer dans la légende quand c'est pertinent :
*« Photo : Contre-Attaque »*

## À la sync

- Chaque image est **uploadée une seule fois** dans la médiathèque WP
- À la sync suivante, elle n'est pas re-uploadée (hash SHA1 comparé)
- L'URL dans l'article est réécrite automatiquement pour pointer vers
  la médiathèque WP (et non vers GitHub)

## Pour enrichir la base

1. Télécharger dans `content/media/<nom-descriptif>.jpg` (slug court)
2. Documenter ici l'origine et le thème
3. Si l'image doit servir comme fallback automatique : mettre à jour
   `$fallback_map` dans `ql-sync.php`
4. Dimensions recommandées : **1000×1000** à **1600×900**

## Gitignore éventuel

Les grosses images peuvent faire gonfler le repo. Si un dossier devient
trop gros, option : stocker hors Git (upload direct dans la médiathèque
WP via l'admin) et ne passer par ce dossier que pour les images qu'on
veut versionner avec l'article.
