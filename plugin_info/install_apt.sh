#!/bin/bash
echo "Installation dépendances système"
apt-get update
apt-get install -y python3 python3-venv python3-pip
exit 0