<?php
require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

function JeeRemi_install() {
    JeeRemi_reinstall_dependencies();
}

function JeeRemi_update() {
    JeeRemi_reinstall_dependencies();
}

function JeeRemi_remove() {
    JeeRemi_remove_venv();
}

function JeeRemi_dependancy_info() {
    $venv_python = dirname(__FILE__) . '/../resources/python_venv/bin/python3';
    if (file_exists($venv_python) && is_executable($venv_python)) {
        return [
            'log' => 'JeeRemi_packages',
            'progress_file' => '/tmp/jeedom/JeeRemi/dependancy',
            'state' => true,
        ];
    } else {
        return [
            'log' => 'JeeRemi_packages',
            'progress_file' => '/tmp/jeedom/JeeRemi/dependancy',
            'state' => false,
        ];
    }
}

function JeeRemi_reinstall_dependencies() {
    log::add('JeeRemi', 'info', 'Réinstallation des dépendances...');
    JeeRemi_remove_venv();
    plugin::byId('JeeRemi')->dependancy_install();
}

function JeeRemi_remove_venv() {
    $venv_dir = dirname(__FILE__) . '/../resources/python_venv';
    if (file_exists($venv_dir)) {
        log::add('JeeRemi', 'info', 'Suppression du dossier python_venv');
        $cmd = 'sudo rm -rf ' . escapeshellarg($venv_dir);
        system($cmd . ' >> ' . log::getPathToLog('JeeRemi_packages') . ' 2>&1');
    }
}