#!/bin/bash
#echo "Installation dépendances système"
#apt-get update
#apt-get install -y python3 python3-venv python3-pip

VENV="$(dirname "$0")/../resources/python_venv"
echo "Création venv..."
python3 -m venv "$VENV"
echo "Installation modules Python..."
"$VENV/bin/pip3" install --upgrade pip
"$VENV/bin/pip3" install requests

# Création du fichier de progression pour Jeedom
mkdir -p /tmp/jeedom/JeeRemi
echo 100 > /tmp/jeedom/JeeRemi/dependancy
exit 0