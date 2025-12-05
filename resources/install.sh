#!/bin/bash
set -euo pipefail

PLUGIN="JeeRemi"
BASE_DIR="/var/www/html/plugins/${PLUGIN}"
VENV_DIR="${BASE_DIR}/resources/python_venv"
LOG="/var/www/html/log/${PLUGIN}_packages"

# Créer le dossier de log s'il n'existe pas
mkdir -p "$(dirname "$LOG")"
touch "$LOG"
chown www-data:www-data "$LOG"
chmod 666 "$LOG"

echo "Début de l'installation des dépendances pour ${PLUGIN}" >> "$LOG"

# Supprimer l'ancien venv s'il existe
if [ -d "${VENV_DIR}" ]; then
    echo "Suppression de l'ancien venv..." >> "$LOG"
    rm -rf "${VENV_DIR}" >> "$LOG" 2>&1
fi

# Créer le venv
echo "Création du venv dans ${VENV_DIR}..." >> "$LOG"
sudo -u www-data python3 -m venv "${VENV_DIR}" >> "$LOG" 2>&1

# Installer requests dans le venv
echo "Installation de requests dans le venv..." >> "$LOG"
sudo -u www-data "${VENV_DIR}/bin/pip" install requests >> "$LOG" 2>&1

# Installer certifi dans le venv
echo "Installation de certifi dans le venv..." >> "$LOG"
sudo -u www-data "${VENV_DIR}/bin/pip" install certifi >> "$LOG" 2>&1

# Installer urllib3 dans le venv
echo "Installation de urllib3 dans le venv..." >> "$LOG"
sudo -u www-data "${VENV_DIR}/bin/pip" install urllib3 >> "$LOG" 2>&1

# Définir les permissions
echo "Définition des permissions pour ${VENV_DIR}..." >> "$LOG"
chown -R www-data:www-data "${VENV_DIR}"
chmod -R 755 "${VENV_DIR}"

echo "Fin de l'installation des dépendances pour ${PLUGIN}" >> "$LOG"