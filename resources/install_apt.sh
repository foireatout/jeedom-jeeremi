#!/bin/bash
set -e

PLUGIN="JeeRemi"
LOG="/var/www/html/log/${PLUGIN}_dep"
PROGRESS_FILE="/tmp/jeedom/${PLUGIN}/dependancy"

mkdir -p /tmp/jeedom/${PLUGIN}
echo 0 > "$PROGRESS_FILE"

echo "[1/3] Mise à jour APT..." | tee "$LOG"
sudo apt update -y >> "$LOG" 2>&1
echo 30 > "$PROGRESS_FILE"

echo "[2/3] Installation python3 + pip..." | tee -a "$LOG"
sudo apt install -y python3 python3-pip >> "$LOG" 2>&1
echo 60 > "$PROGRESS_FILE"

echo "[3/3] Installation de requests au niveau système..." | tee -a "$LOG"
sudo python3 -m pip install --upgrade pip >> "$LOG" 2>&1
sudo python3 -m pip install --upgrade requests >> "$LOG" 2>&1
echo 100 > "$PROGRESS_FILE"

echo "Installation terminée" | tee -a "$LOG"
exit 0