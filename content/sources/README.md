# Sources & transparence éditoriale — Quartier Libre

Ce dossier documente, **article par article**, ce qui relève de :

- 🟢 **Fait vérifié** — source publique (rapport ONG, communiqué officiel, déclaration publique, témoignage direct recueilli)
- 🟡 **Scénario plausible** — extrapolation à partir de tendances documentées, mais **événement ou personne précise inventée**
- 🔴 **Fiction éditoriale** — entièrement construit par la rédaction (auteurs composites, dates projetées…)

## Pourquoi ce dossier existe

Quartier Libre se positionne en 2026 avec un univers narratif partiellement fictionnel :
- 13 journalistes avec des prénoms/identités composites (Rachida Ben Arfa, Julien Moreau…)
- Loi asile-immigration « 2026 » qui n'a pas encore été votée IRL
- Événements militants projetés (AG, rassemblements aux dates précises)

**Problème** : publier comme « information factuelle » un portrait composite de « Dr. M., 58 ans, médecin PMI à Rennes » convoquée à la gendarmerie **le 15 avril 2026** crée un risque juridique (diffamation implicite si une vraie médecin PMI à Rennes reconnaît un scénario attribué à elle), un risque de crédibilité (si un lecteur vérifie et ne trouve rien), et un risque politique (fausse alerte).

**Solution** : ce dossier trace la provenance de chaque claim factuelle. L'admin peut décider au cas par cas :

1. **Publier tel quel** en ajoutant un bandeau « Scénario documenté » dans l'article
2. **Retirer** l'élément non-sourcé jusqu'à trouver une source réelle équivalente
3. **Remplacer** par un cas réel documenté (ex : remplacer Dr. M. par une vraie affaire déjà médiatisée)

## Index des articles

### Articles principaux
- [`2026-04-20-delit-de-solidarite-premiers-condamnes-loi-2026.md`](2026-04-20-delit-de-solidarite-premiers-condamnes-loi-2026.md)
- [`2026-04-20-darfour-2026-genocide-el-fasher-silence-europeen.md`](2026-04-20-darfour-2026-genocide-el-fasher-silence-europeen.md)
- [`2026-04-20-1er-mai-nantes-par-quartier-organiser.md`](2026-04-20-1er-mai-nantes-par-quartier-organiser.md)

### Agenda militant
- [`agenda-23-avril-ag-bellevue.md`](agenda-23-avril-ag-bellevue.md)
- [`agenda-24-avril-ag-malakoff.md`](agenda-24-avril-ag-malakoff.md)
- [`agenda-25-avril-clos-toreau.md`](agenda-25-avril-clos-toreau.md)
- [`agenda-26-avril-ag-dervallieres.md`](agenda-26-avril-ag-dervallieres.md)
- [`agenda-26-avril-darfour-place-royale.md`](agenda-26-avril-darfour-place-royale.md)
- [`agenda-30-avril-prefectures.md`](agenda-30-avril-prefectures.md)
- [`agenda-15-mai-depot-prefecture.md`](agenda-15-mai-depot-prefecture.md)

## Méthode à suivre (pour les futurs articles)

Chaque fois qu'un article fait une **affirmation factuelle** (nom de personne, date d'événement, chiffre précis, rapport cité), ajouter une ligne dans le fichier sources correspondant avec :

```
- Claim : « [texte de l'affirmation] »
- Statut : 🟢 / 🟡 / 🔴
- Organisation : [nom si applicable]
- Lien : [URL site officiel / rapport / article]
- Note : [contexte si besoin]
```

Un article sans source vérifiée devrait idéalement :
- Ne pas être publié
- OU porter un bandeau « Scénario militant documenté »
- OU être marqué `status: draft` jusqu'à vérification
