# Workflow de synchronisation — 2 modes au choix

Vous avez maintenant **deux façons** de pousser des changements de thème
vers quartierlibre.org. Utilisez celle qui vous arrange selon le moment.

---

## Mode A — FTP (local → serveur) — « sync.bat »

**Quand l'utiliser** : vous venez de modifier un fichier en local,
vous voulez tester en ligne immédiatement, pas besoin de passer par
GitHub.

1. Modifier vos fichiers dans `quartier-libre-theme/`
2. **Double-clic sur `sync.bat`** (racine du repo)
3. Taper `O` à la confirmation
4. Purger NitroPack dans l'admin WP

**Avantage** : le plus rapide, aucune dépendance externe.
**Inconvénient** : pas d'historique Git côté serveur.

---

## Mode B — GitHub → WP admin (bouton « Synchroniser »)

**Quand l'utiliser** :
- Vous travaillez à plusieurs sur le thème (Git collaboration)
- Vous voulez un historique propre de ce qui a été déployé
- Vous êtes sur un autre PC sans FileZilla
- Vous voulez déployer depuis votre téléphone via l'admin WP

### Le flux

```
┌─────────────┐     git push     ┌──────────┐     1-clic     ┌──────────────┐
│  Votre PC   │ ───────────────▶ │  GitHub  │ ─────────────▶ │ quartierlibre│
│   (local)   │  (GitHub Desktop │  (repo)  │  (admin WP)    │   .org       │
│             │   ou git push)   │          │                │              │
└─────────────┘                  └──────────┘                └──────────────┘
```

### Utilisation

1. **Modifier** des fichiers en local dans `quartier-libre-theme/`
2. **Pousser sur GitHub** :
   - Via **GitHub Desktop** (que vous avez déjà dans la barre des tâches) :
     - Summary du commit → bouton *Commit to main*
     - Bouton *Push origin*
   - Ou via Git Bash : `git add . && git commit -m "..." && git push`
3. Aller dans l'admin WP de quartierlibre.org :
   **Outils → Sync QL**
4. Cliquer sur le gros bouton rouge **« Synchroniser maintenant »**
5. Attendre ~10-30 secondes
6. Purger NitroPack (bouton en haut) → hard-refresh

**Avantage** : pas de FTP, historique Git propre, sauvegardes auto sur GitHub.
**Inconvénient** : nécessite que le repo soit **public** (ou que le site ait un token GitHub pour les repos privés — non implémenté pour l'instant).

### Si le repo GitHub est privé

Le bouton actuel utilise l'API GitHub publique sans token. Pour que ça
marche sur un repo privé, il faudrait stocker un **Personal Access
Token** côté WP. Actuellement **non implémenté** car :
- Le repo Quartier Libre est (ou peut être) public
- Stocker un token en base expose le site à un risque si le site est
  compromis

Si vous voulez passer le repo en privé, on ajoutera l'option token
à ce moment-là.

---

## Installation du Mode B (à faire 1 fois)

Le fichier `ql-sync.php` est déjà dans le thème. Il s'active
automatiquement à la prochaine synchronisation. Pour le charger pour
la première fois :

1. Uploadez le thème via **Mode A** (sync.bat) — obligatoire la première fois.
2. Allez dans **Outils → Sync QL** : le menu apparaît.
3. Les syncs suivantes peuvent passer par le bouton.

### Configuration du repo

Le fichier `ql-sync.php` cible ce repo par défaut :

```php
define( 'QL_GH_OWNER',      'khalidawi44' );
define( 'QL_GH_REPO',       'QuartierLibre' );
define( 'QL_GH_BRANCH',     'main' );
define( 'QL_GH_THEME_PATH', 'quartier-libre-theme' );
```

Si vos valeurs sont différentes (autre nom de branche, autre owner),
modifier ces 4 constantes en haut de `ql-sync.php`.

---

## Ma recommandation

- **Développement quotidien** : **Mode A** (sync.bat), c'est instantané.
- **Avant une grosse mise en ligne** : commit + push sur GitHub, puis
  **Mode B** depuis l'admin — ça laisse une trace propre.
- **Équipe à distance / mobile** : **Mode B** uniquement.

Les deux coexistent sans problème. Le Mode B peut même récupérer des
changements faits via le Mode A, à condition que vous committiez +
pushiez ces changements sur GitHub après.
