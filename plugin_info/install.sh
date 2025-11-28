#!/bin/bash
VENV="$(dirname "$0")/python_venv"

echo "Création venv..."
python3 -m venv "$VENV"

echo "Installation modules Python..."
"$VENV/bin/pip3" install --upgrade pip
"$VENV/bin/pip3" install requests

exit 0