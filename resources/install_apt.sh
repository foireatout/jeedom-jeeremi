#!/bin/bash
set -euo pipefail

PLUGIN="JeeRemi"
BASE_DIR="/var/www/html/plugins/${PLUGIN}"
VENV_DIR="${BASE_DIR}/resources/python_venv"
LOG="/var/www/html/log/${PLUGIN}_dep"
PROGRESS_DIR="/tmp/jeedom/${PLUGIN}"
PROGRESS_FILE="${PROGRESS_DIR}/dependancy"

mkdir -p "${PROGRESS_DIR}"
mkdir -p "$(dirname "$LOG")"
: > "$LOG"

echo "Start $PLUGIN dependency install" | tee -a "$LOG"
echo 0 > "$PROGRESS_FILE"

# 1) Installer les dépendances système
echo "[1/4] Installation des dépendances système (python3, python3-venv, python3-pip)" | tee -a "$LOG"
DEBIAN_FRONTEND=noninteractive apt-get update -y >> "$LOG" 2>&1
DEBIAN_FRONTEND=noninteractive apt-get install -y python3 python3-venv python3-pip >> "$LOG" 2>&1
echo 20 > "$PROGRESS_FILE"

# 2) Supprimer l'ancien venv s'il existe
echo "[2/4] Suppression de l'ancien venv (si présent)" | tee -a "$LOG"
if [ -d "${VENV_DIR}" ]; then
    rm -rf "${VENV_DIR}" >> "$LOG" 2>&1 || true
fi

# 3) Créer le venv
echo "[3/4] Création du venv dans ${VENV_DIR}" | tee -a "$LOG"
python3 -m venv "${VENV_DIR}" >> "$LOG" 2>&1
if [ ! -d "${VENV_DIR}" ]; then
    echo "ERREUR : Impossible de créer le venv dans ${VENV_DIR}" | tee -a "$LOG"
    echo 5 > "$PROGRESS_FILE"
    exit 1
fi

# 4) Installer requests dans le venv
echo "[4/4] Installation de requests dans le venv" | tee -a "$LOG"
"${VENV_DIR}/bin/pip" install --upgrade pip >> "$LOG" 2>&1
"${VENV_DIR}/bin/pip" install requests >> "$LOG" 2>&1

# 5) Vérifier que requests est bien installé
if ! "${VENV_DIR}/bin/pip" show requests &> /dev/null; then
    echo "ERREUR : Impossible d'installer requests dans le venv" | tee -a "$LOG"
    echo 5 > "$PROGRESS_FILE"
    exit 1
fi

# 6) Définir les permissions
echo "Définition des permissions pour ${VENV_DIR}" | tee -a "$LOG"
chown -R www-data:www-data "${VENV_DIR}"
chmod -R 775 "${VENV_DIR}"

# 7) Marquer la fin de l'installation
echo 100 > "$PROGRESS_FILE"
echo "Fin de l'installation des dépendances pour $PLUGIN" | tee -a "$LOG"
exit 0