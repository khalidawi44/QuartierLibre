# Synchronisation FTP en 1 clic

Déploie `quartier-libre-theme/` vers `public_html/wp-content/themes/` en
un double-clic.

## Première utilisation — configuration (à faire 1 seule fois)

### 1. Installer WinSCP (Windows) ou lftp (Mac/Linux)

- **Windows** : [télécharger WinSCP](https://winscp.net/eng/download.php)
  → **cocher "Add installation directory to PATH"** pendant l'install.
  Sans ça, le script ne trouve pas `winscp.com` et bascule sur le FTP
  natif Windows (pire : pas de vraie synchro).
- **Mac** : `brew install lftp`
- **Linux** : `sudo apt install lftp`

### 2. Renseigner vos identifiants FTP Hostinger

Ouvrir `deploy/deploy.bat` (ou `deploy.sh`) dans un éditeur texte et
remplacer :

```
set FTP_HOST=ftp.quartierlibre.org
set FTP_USER=VOTRE_IDENTIFIANT_FTP
set FTP_PASS=VOTRE_MOT_DE_PASSE_FTP
set FTP_PORT=21
set FTP_PATH=/public_html/wp-content/themes
```

**Où trouver les identifiants FTP Hostinger :**
hPanel → *Fichiers* → *Comptes FTP*. Hostinger affiche host/user/port.
Le mot de passe doit être celui que vous avez défini à la création du
compte FTP (ou réinitialisé).

## Utilisation quotidienne

- **Windows** : double-clic sur `sync.bat` à la racine, ou
  `deploy/deploy.bat`.
- **Mac/Linux** : `./deploy/deploy.sh`

Le script demande confirmation (O/N), se connecte, synchronise, affiche
un récap.

## Modes

| Mode | Effet |
|---|---|
| **push** *(défaut)* | Upload les nouveaux fichiers + remplace les modifiés. N'efface **rien** côté serveur. **Sûr.** |
| **mirror** | Synchro **miroir** : les fichiers supprimés localement sont aussi supprimés sur le serveur. À utiliser quand vous êtes certain de ne rien avoir manuellement ajouté côté serveur. |

Pour passer en mirror :
- Windows : éditer `deploy.bat` → `set MODE=mirror`
- Mac/Linux : `./deploy.sh --mirror`

## Simulation (dry-run)

Teste ce qui serait transféré sans rien faire :
- Mac/Linux : `./deploy.sh --dry`
- Windows : pas de dry-run natif — préférer WinSCP GUI en mode
  « Session → Synchroniser → Aperçu ».

## Sécurité

⚠️ Les identifiants FTP sont **en clair** dans `deploy.bat` / `deploy.sh`.
Ne pas partager ce fichier publiquement, ne pas committer sur un repo
public. Le `.gitignore` à la racine protège les fichiers `*.local.*`
au cas où.

Hostinger propose aussi **SFTP** (plus sûr, chiffré). Pour l'utiliser :
- port 22 au lieu de 21
- WinSCP gère nativement SFTP
- lftp : remplacer `ftp://` par `sftp://` dans la commande

Demandez si vous voulez que je convertisse les scripts en SFTP.

## Dépannage

| Symptôme | Cause probable |
|---|---|
| « WinSCP non détecté, fallback FTP natif » | Pas dans le PATH — réinstaller WinSCP avec l'option « Add to PATH ». |
| Timeout / « cannot connect » | Pare-feu ou mauvais host. Vérifier `ftp.quartierlibre.org` vs `files.hostinger.com` dans hPanel. |
| « 530 Login incorrect » | User/pass FTP erronés. Recréer le compte FTP dans hPanel. |
| Upload OK mais site inchangé | Cache NitroPack — aller dans le back-office et purger le cache. |
| Fichiers absents sur le serveur | `FTP_PATH` mal défini. Chez Hostinger c'est **souvent** `/public_html/...` et parfois `/domains/quartierlibre.org/public_html/...`. Ouvrir WinSCP GUI une fois pour vérifier. |

## Après la synchro

1. Purger le cache **NitroPack** (depuis le menu admin WP en haut à droite).
2. Faire un hard-refresh (Ctrl+Shift+R) pour voir les changements.
3. Vérifier la home + un article + `/bureau-des-plaintes/`.
