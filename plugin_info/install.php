<?php
class JeeRemiInstall {
    public static function postInstall() {
        self::launchDep();
    }

    public static function postUpdate() {
        self::launchDep();
    }

    public static function launchDep() {
        $plugin = plugin::byId('JeeRemi');
        $plugin->setState('dependencies', 'nok');
        $plugin->save();
        jeedom::getPlugin('JeeRemi')->installDependancy();
    }

    public static function checkDependancy() {
        $plugin = plugin::byId('JeeRemi');
        $python = __DIR__ . '/../resources/python_venv/bin/python3';

        if (file_exists($python) && is_executable($python)) {
            $plugin->setState('dependencies', 'ok');
            $plugin->save();

            // Solution plus robuste pour le rafraîchissement
            echo '<script>
                if (window.location.href.includes("plugin.php")) {
                    window.location.reload();
                } else {
                    // Si on n'est pas sur la page du plugin, on redirige vers la page du plugin
                    window.location.href = "index.php?v=d&p=plugin&id=JeeRemi";
                }
            </script>';
        } else {
            $plugin->setState('dependencies', 'nok');
            $plugin->save();
        }
    }

    public static function dependancy_info() {
        $return = [];
        $return['log'] = 'JeeRemi_dep';
        $return['progress_file'] = '/tmp/jeedom/JeeRemi/dependancy';
        $return['state'] = self::checkPython();
        return $return;
    }

    private static function checkPython() {
        $python = __DIR__ . '/../resources/python_venv/bin/python3';
        if (file_exists($python) && is_executable($python)) {
            return true;
        }
        return false;
    }
}