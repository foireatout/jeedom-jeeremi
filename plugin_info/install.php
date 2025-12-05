<?php
require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

function JeeRemi_install() {
    // Jeedom gère l'installation des dépendances via packages.json
    // On force la réinstallation des dépendances
    JeeRemi_reinstall_dependencies();
}

function JeeRemi_update() {
    // On force la réinstallation des dépendances à chaque mise à jour
    JeeRemi_reinstall_dependencies();
}

function JeeRemi_remove() {
    // Suppression du dossier python_venv lors de la désinstallation
    JeeRemi_remove_venv();
}

// Fonction pour vérifier l'état des dépendances
function JeeRemi_dependancy_info() {
    $venv_python = dirname(__FILE__) . '/../resources/python_venv/bin/python3';
    if (file_exists($venv_python) && is_executable($venv_python)) {
        return [
            'log' => 'JeeRemi_dep',
            'progress_file' => '/tmp/jeedom/JeeRemi/dependancy',
            'state' => true,
        ];
    } else {
        return [
            'log' => 'JeeRemi_dep',
            'progress_file' => '/tmp/jeedom/JeeRemi/dependancy',
            'state' => false,
        ];
    }
}

// Fonction pour réinstaller les dépendances
function JeeRemi_reinstall_dependencies() {
    log::add('JeeRemi', 'info', 'Réinstallation des dépendances...');
    JeeRemi_remove_venv();
    // Demander à Jeedom de relancer l'installation des dépendances
    plugin::byId('JeeRemi')->dependancy_install();
}

// Fonction pour supprimer le dossier python_venv
function JeeRemi_remove_venv() {
    $venv_dir = dirname(__FILE__) . '/../resources/python_venv';
    if (file_exists($venv_dir)) {
        log::add('JeeRemi', 'info', 'Suppression du dossier python_venv');
        $cmd = 'sudo rm -rf ' . escapeshellarg($venv_dir);
        system($cmd . ' >> ' . log::getPathToLog('JeeRemi_dep') . ' 2>&1');
    }
}