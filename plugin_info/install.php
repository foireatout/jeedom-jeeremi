<?php

class JeeRemiInstall
{
    public static function postInstall()
    {
        self::launchDep();
    }

    public static function postUpdate()
    {
        self::launchDep();
    }

    public static function launchDep()
    {
        $plugin = plugin::byId('JeeRemi');
        $plugin->setState('dependencies', 'nok');
        $plugin->save();

        // Déclenche l'installation officielle des dépendances
        jeedom::getPlugin('JeeRemi')->installDependancy();
    }

    public static function checkDependancy()
    {
        $plugin = plugin::byId('JeeRemi');
        $python = __DIR__ . '/../resources/python_venv/bin/python3';

        if (file_exists($python) && is_executable($python)) {
            $plugin->setState('dependencies', 'ok');
        } else {
            $plugin->setState('dependencies', 'nok');
        }

        $plugin->save();
    }
}