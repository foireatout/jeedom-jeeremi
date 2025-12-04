<?php

class JeeRemiInstall {

    public static function postInstall() {
        self::checkDependency();
    }

    public static function postUpdate() {
        self::checkDependency();
    }

    public static function checkDependency() {
        $python = __DIR__ . '/../resources/python_venv/bin/python3';
        
        if (file_exists($python) && is_executable($python)) {
            plugin::byId('JeeRemi')->setState('dependencies', 'ok');
        } else {
            plugin::byId('JeeRemi')->setState('dependencies', 'nok');
        }
        plugin::byId('JeeRemi')->save();
    }

    public static function dependancy_info() {
        return [
            'log' => 'JeeRemi_dep',
            'progress_file' => '/tmp/jeedom/JeeRemi/dependancy',
            'state' => plugin::byId('JeeRemi')->getState('dependencies') == 'ok'
        ];
    }

    public static function dependancy_install() {
        // Laisse Jeedom gérer via packages.json
        passthru('sudo /bin/bash ' . dirname(__FILE__) . '/../core/php/install.php');
    }
}