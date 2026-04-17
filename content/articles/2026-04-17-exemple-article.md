---
title: "Article d'exemple : comment écrire depuis le repo"
slug: "exemple-article-depuis-repo"
category: "infos-locale"
tags:
  - exemple
excerpt: "Un premier article de démonstration, créé depuis un fichier Markdown et synchronisé en un clic."
status: "draft"
date: "2026-04-17 10:00"
---

Cet article est un **exemple** pour montrer comment la synchronisation
Markdown → WordPress fonctionne. Il est en statut `draft` pour éviter
qu'il apparaisse publiquement.

## Ce qui se passe à la sync

1. Vous modifiez ce fichier `.md` en local.
2. Vous poussez sur GitHub (via GitHub Desktop ou `git push`).
3. Dans l'admin WP : *Outils → Sync QL → Synchroniser maintenant*.
4. Le système lit tous les `.md` de `content/articles/`, crée ou met à
   jour les articles correspondants dans WordPress.

## Ce que vous pouvez faire

- Écrire plusieurs articles en parallèle dans votre éditeur préféré.
- Travailler hors ligne, pousser quand vous avez réseau.
- Collaborer via Git (pull requests, branches, etc.).
- Avoir un **historique complet** de chaque article dans Git.

## Markdown courant

Voici un paragraphe avec **du gras**, *de l'italique*, et
[un lien externe](https://quartierlibre.org/).

> « Le média libre commence par la liberté de publier. »

- Une liste à puces
- Plusieurs items
- Aussi longue qu'on veut

1. Une liste numérotée
2. Fonctionne pareil

### Un sous-sous-titre

Le corps peut faire autant de paragraphes qu'il faut, séparés par des
lignes vides.

---

Supprimez ce fichier quand vous n'en avez plus besoin. L'article créé
côté WP restera en brouillon (il n'est pas auto-supprimé, pour éviter
les pertes accidentelles).
