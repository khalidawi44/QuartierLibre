#!/bin/bash
# ═══════════════════════════════════════════════════════════════════
# Quartier Libre — Synchronisation FTP en 1 clic (Mac / Linux / Git Bash)
# ═══════════════════════════════════════════════════════════════════
#
# UTILISATION :
#   ./deploy.sh           → synchronisation (push, sans suppression)
#   ./deploy.sh --mirror  → synchronisation miroir (avec suppression)
#   ./deploy.sh --dry     → simulation (ne transfère rien)
#
# PREREQUIS :
#   - lftp installé (recommandé)
#       Ubuntu/Debian : sudo apt install lftp
#       macOS         : brew install lftp
#       Windows       : utiliser deploy.bat à la place
#
# ═══════════════════════════════════════════════════════════════════

# ── CONFIGURATION — MODIFIER CES VALEURS ───────────────────────────
FTP_HOST="ftp.quartierlibre.org"
FTP_USER="VOTRE_IDENTIFIANT_FTP"
FTP_PASS="VOTRE_MOT_DE_PASSE_FTP"
FTP_PORT="21"
FTP_PATH="/public_html/wp-content/themes"
THEME_NAME="quartier-libre-theme"
# ───────────────────────────────────────────────────────────────────

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
CYAN='\033[0;36m'
NC='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
THEME_DIR="$SCRIPT_DIR/../$THEME_NAME"

# Parse flags
MODE="push"
DRY=""
for arg in "$@"; do
    case "$arg" in
        --mirror) MODE="mirror" ;;
        --dry)    DRY="--dry-run" ;;
    esac
done

echo ""
echo "═══════════════════════════════════════════════════"
echo "  Quartier Libre — Synchronisation FTP"
echo "═══════════════════════════════════════════════════"
echo ""
echo -e "${YELLOW}Dossier source :${NC} $THEME_DIR"
echo -e "${YELLOW}Serveur FTP    :${NC} $FTP_HOST:$FTP_PORT"
echo -e "${YELLOW}Destination    :${NC} $FTP_PATH/$THEME_NAME/"
echo -e "${YELLOW}Mode           :${NC} $MODE${DRY:+  (DRY-RUN)}"
echo ""

# Vérifier que le dossier existe
if [ ! -d "$THEME_DIR" ]; then
    echo -e "${RED}ERREUR : Dossier du thème introuvable : $THEME_DIR${NC}"
    exit 1
fi

# Vérifier que les credentials sont renseignés
if [ "$FTP_USER" = "VOTRE_IDENTIFIANT_FTP" ]; then
    echo -e "${RED}ATTENTION : identifiants FTP par défaut encore présents.${NC}"
    echo "  → Ouvre deploy.sh dans un éditeur et remplis FTP_HOST/USER/PASS."
    exit 1
fi

# Compter les fichiers
FILE_COUNT=$(find "$THEME_DIR" -type f | wc -l | tr -d ' ')
echo -e "${GREEN}$FILE_COUNT fichiers à synchroniser${NC}"
echo ""

# Confirmation
read -p "$(echo -e ${CYAN}Lancer la synchronisation ? \(o/N\) ${NC})" -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Oo]$ ]]; then
    echo "Synchronisation annulée."
    exit 0
fi

echo ""
echo "Synchronisation en cours..."
echo ""

# ── Méthode 1 : lftp (recommandé) ─────────────────────────────
if command -v lftp &> /dev/null; then
    MIRROR_OPTS="--reverse --verbose --parallel=4 --exclude-glob=.DS_Store --exclude-glob=Thumbs.db $DRY"
    if [ "$MODE" = "mirror" ]; then
        MIRROR_OPTS="$MIRROR_OPTS --delete"
    fi

    lftp -e "
        set ssl:verify-certificate no;
        set ftp:ssl-allow yes;
        set cmd:fail-exit yes;
        open ftp://$FTP_USER:$FTP_PASS@$FTP_HOST:$FTP_PORT;
        mkdir -f -p $FTP_PATH/$THEME_NAME;
        mirror $MIRROR_OPTS $THEME_DIR $FTP_PATH/$THEME_NAME/;
        quit
    "
    STATUS=$?

# ── Méthode 2 : ncftp (fallback) ──────────────────────────────
elif command -v ncftpput &> /dev/null; then
    echo -e "${YELLOW}lftp non trouvé — fallback ncftpput (upload sans suppression).${NC}"
    ncftpput -R -v -u "$FTP_USER" -p "$FTP_PASS" -P "$FTP_PORT" \
        "$FTP_HOST" "$FTP_PATH/" "$THEME_DIR"
    STATUS=$?

else
    echo -e "${RED}ERREUR : Aucun client FTP trouvé (lftp ou ncftp).${NC}"
    echo ""
    echo "Installation :"
    echo "  Ubuntu/Debian : sudo apt install lftp"
    echo "  macOS         : brew install lftp"
    echo ""
    echo "Ou utilise deploy.bat sur Windows."
    exit 1
fi

echo ""
if [ $STATUS -eq 0 ]; then
    echo "═══════════════════════════════════════════════════"
    echo -e "  ${GREEN}SYNCHRONISATION REUSSIE !${NC}"
    echo "═══════════════════════════════════════════════════"
    echo ""
    echo "Site à jour : https://quartierlibre.org/"
else
    echo -e "${RED}ERREUR lors de la synchronisation (code: $STATUS)${NC}"
    echo ""
    echo "Vérifications :"
    echo "  - FTP_HOST / FTP_USER / FTP_PASS corrects ?"
    echo "  - Le dossier $FTP_PATH existe-t-il sur le serveur ?"
    exit 1
fi
