# Images des articles

Déposer ici les images référencées dans les articles :

- **Image à la une** : référencée dans le front-matter `featured_image:`
- **Images inline** : référencées avec `![alt](content/media/nom.jpg)` dans le corps

## Formats recommandés

- **`.webp`** si possible (30 à 50 % plus léger que JPEG)
- `.jpg` pour les photos
- `.png` pour les visuels avec transparence, infographies
- `.svg` pour les pictos

## Tailles

- Image à la une : **1600×900 px minimum** (le thème recadre en `ql-hero`).
- Images inline : **1200 px de large** suffit (affichage responsive).

## À la sync

- Chaque image est **uploadée une seule fois** dans la médiathèque WP.
- À la sync suivante, elle n'est pas re-uploadée (hash SHA1 comparé).
- L'URL dans l'article est réécrite automatiquement pour pointer vers
  la médiathèque WP (et non vers GitHub).

## Gitignore éventuel

Les grosses images peuvent faire gonfler le repo. Si un dossier devient
trop gros, une option est de les stocker **hors Git** (direct upload dans
la médiathèque WP via l'admin classique), et de ne passer par ce dossier
que pour les images qu'on veut versionner avec l'article.
