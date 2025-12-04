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

# 1) apt update
echo "[1/5] apt update" | tee -a "$LOG"
apt-get update -y >> "$LOG" 2>&1 || true
echo 10 > "$PROGRESS_FILE"

# 2) install system packages
echo "[2/5] install python3, python3-venv, python3-pip" | tee -a "$LOG"
DEBIAN_FRONTEND=noninteractive apt-get install -y python3 python3-venv python3-pip build-essential >> "$LOG" 2>&1
echo 30 > "$PROGRESS_FILE"

# 3) create venv (remove previous broken one)
echo "[3/5] create python venv at ${VENV_DIR}" | tee -a "$LOG"
if [ -d "${VENV_DIR}" ]; then
  rm -rf "${VENV_DIR}" >> "$LOG" 2>&1 || true
fi
python3 -m venv "${VENV_DIR}" >> "$LOG" 2>&1
# ensure python inside venv is executable
if [ -x "${VENV_DIR}/bin/python3" ]; then
  echo "venv python exists" >> "$LOG"
else
  echo "ERROR: venv python not found or not executable" >> "$LOG"
  echo 5 > "$PROGRESS_FILE"
  exit 1
fi
echo 60 > "$PROGRESS_FILE"

# 4) upgrade pip and install required python packages inside venv
echo "[4/5] upgrade pip and install requests inside venv" | tee -a "$LOG"
"${VENV_DIR}/bin/pip" install --upgrade pip setuptools wheel >> "$LOG" 2>&1
"${VENV_DIR}/bin/pip" install --upgrade requests >> "$LOG" 2>&1
echo 80 > "$PROGRESS_FILE"

# 5) permissions & finalization
echo "[5/5] set ownership to www-data" | tee -a "$LOG"
chown -R www-data:www-data "${VENV_DIR}" >> "$LOG" 2>&1
chmod -R 775 "${VENV_DIR}" >> "$LOG" 2>&1

# create progress marker (Jeedom expects 100)
echo 100 > "$PROGRESS_FILE"

# Tell Jeedom dependency finished (via jeecli) if available
if command -v php >/dev/null 2>&1; then
  php /var/www/html/core/php/jeecli.php plugin dependancy_end "${PLUGIN}" >> "$LOG" 2>&1 || true
fi

echo "End $PLUGIN dependency install" | tee -a "$LOG"
exit 0