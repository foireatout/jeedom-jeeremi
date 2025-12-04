#!/bin/bash
set -e

PLUGIN="JeeRemi"
BASE_DIR="/var/www/html/plugins/$PLUGIN"
VENV_DIR="$BASE_DIR/resources/python_venv"
PROGRESS_FILE="/tmp/jeedom/${PLUGIN}/dependancy"
LOG="/var/www/html/log/${PLUGIN}_dep"

mkdir -p /tmp/jeedom/${PLUGIN}

echo "******************* Begin of $PLUGIN dependencies *******************" | tee "$LOG"
echo 0 > "$PROGRESS_FILE"

########################################
# 1. Mise à jour APT
########################################
echo "[1/5] Mise à jour des dépôts APT..." | tee -a "$LOG"
sudo apt update -y >> "$LOG" 2>&1 || true
echo 10 > "$PROGRESS_FILE"

########################################
# 2. Installation python3 + pip + venv
########################################
echo "[2/5] Installation python3, pip et python3-venv..." | tee -a "$LOG"
sudo apt install -y python3 python3-pip python3-venv >> "$LOG" 2>&1
echo 30 > "$PROGRESS_FILE"

########################################
# 3. Création du venv
########################################
echo "[3/5] Création du virtualenv : $VENV_DIR" | tee -a "$LOG"
sudo rm -rf "$VENV_DIR"
python3 -m venv "$VENV_DIR"
echo 60 > "$PROGRESS_FILE"

########################################
# 4. Installation des modules Python dans le venv
########################################
echo "[4/5] Installation des modules Python dans le venv..." | tee -a "$LOG"
"$VENV_DIR/bin/pip3" install --upgrade pip >> "$LOG" 2>&1
"$VENV_DIR/bin/pip3" install requests >> "$LOG" 2>&1
echo 80 > "$PROGRESS_FILE"

########################################
# 5. Permissions
########################################
echo "[5/5] Correction des droits sur le venv..." | tee -a "$LOG"
sudo chown -R www-data:www-data "$VENV_DIR"
sudo chmod -R 775 "$VENV_DIR"
echo 100 > "$PROGRESS_FILE"

echo "******************* End of $PLUGIN dependencies *******************" | tee -a "$LOG"
exit 0