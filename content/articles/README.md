# Écrire des articles ici

Chaque article = un fichier `.md` (Markdown) dans ce dossier.
À chaque clic sur **Outils → Sync QL → Synchroniser** dans l'admin WP,
les articles sont créés ou mis à jour automatiquement.

## Nom du fichier

Format recommandé : `YYYY-MM-DD-slug.md`
Exemple : `2026-04-17-nantes-habitat-insalubrite.md`

Le slug dans le nom du fichier est **indicatif** — le vrai slug est
celui du front-matter (voir ci-dessous).

## Structure d'un article

```markdown
---
title: "Nantes Habitat : l'insalubrité continue Clos Toreau"
slug: "nantes-habitat-insalubrite-clos-toreau"
category: "infos-locale"
tags:
  - logement
  - clos-toreau
  - dossier-logement
excerpt: "Six mois après le reportage, les habitants attendent toujours."
featured_image: "content/media/nantes-habitat-insalubrite.jpg"
status: "publish"
date: "2026-04-17 10:30"
author: "khalidawi44"
---

Le chapô est déjà dans `excerpt` ci-dessus. Ici commence le **corps**
de l'article en Markdown.

## Un sous-titre

Un paragraphe normal avec du **gras**, de l'*italique* et
[un lien](https://exemple.fr).

> « Une citation frappante, entre guillemets. »
> — Habitant·e du quartier

- Un item de liste
- Un autre
- Un troisième

![Légende de l'image](content/media/nom-de-l-image.jpg)

### Sous-sous-titre

Autre paragraphe.
```

## Champs du front-matter (entre les `---`)

| Champ | Obligatoire | Description |
|---|---|---|
| `title` | **oui** | Titre de l'article |
| `slug` | recommandé | URL de l'article (sans accents, tirets). Sinon WP le génère depuis le titre. |
| `category` | oui | Slug de la catégorie : `infos-locale`, `en-france`, `international`, `les-luttes`, etc. |
| `tags` | non | Liste de tags. Préfixer par `dossier-` pour apparaître en mosaïque Dossiers. |
| `excerpt` | oui | Chapô affiché en lede + partages sociaux |
| `featured_image` | non | Chemin relatif au repo vers l'image à la une |
| `status` | non | `publish` (par défaut) ou `draft` |
| `date` | non | Date de publication. Défaut : maintenant. |
| `author` | non | Identifiant WP de l'auteur (login). Défaut : auteur de la sync. |
| `source_name` | non | Nom de la source externe (ex. « Contre-Attaque »). Affiche un encart « Source » en bas d'article. |
| `source_url` | non | URL de la source originale. Cliquable, s'ouvre dans un nouvel onglet. |

### Exemple avec source externe (republication)

```yaml
---
title: "Les alvéoles de Kadhafi : prison pour les pauvres"
slug: "alveoles-kadhafi-prison-pauvres"
category: "international"
tags:
  - libye
excerpt: "Un article important republié avec autorisation."
source_name: "Contre-Attaque"
source_url: "https://contre-attaque.net/2025/09/22/alveoles-kadhafi/"
status: "publish"
---

Corps de l'article en Markdown ici.
```

Cela affiche en bas d'article :

> **SOURCE** — Contre-Attaque ↗

## Images

Mettre vos images dans `content/media/` puis les référencer :

- En featured : `featured_image: "content/media/image.jpg"`
- Dans le texte : `![alt](content/media/image.jpg)`

Elles sont **auto-uploadées** dans la médiathèque WP à la première sync
et ne le sont plus ensuite (évite les doublons).

## Mise à jour d'un article

Modifier le `.md` localement → commit → push → bouton Sync. Le slug
sert de clé d'unicité : tant que le slug reste le même, l'article
existant est **mis à jour**, pas dupliqué.

## Supprimer un article

- Par la sync : **n'efface jamais** (sécurité). Vous pouvez retirer le
  `.md` du repo, l'article reste en ligne.
- Dans l'admin WP : supprimer manuellement depuis *Articles*.

## Garder un draft côté repo

Dans le front-matter : `status: "draft"`. L'article est créé en
brouillon, non publié.

## Markdown supporté

- Titres `##`, `###`
- Gras `**texte**`, italique `*texte*`
- Liens `[texte](url)`
- Images `![alt](chemin)`
- Citations `> texte`
- Listes `-` et `1.`
- Paragraphes (ligne vide entre deux)

Pour du HTML plus complexe, écrire directement du HTML dans le
Markdown — il passe tel quel.
