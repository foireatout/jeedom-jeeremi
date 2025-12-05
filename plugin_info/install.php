class JeeRemiInstall {
    public static function dependancy_install() {
        log::add('JeeRemi', 'info', 'Installation des dépendances...');
        $cmd = 'sudo /bin/bash ' . dirname(__FILE__) . '/../resources/install_apt.sh';
        $return = system($cmd . ' >> ' . log::getPathToLog('JeeRemi_dep') . ' 2>&1');
        if ($return === false) {
            log::add('JeeRemi', 'error', 'Échec de l\'installation des dépendances.');
            return false;
        }
        plugin::byId('JeeRemi')->setState('dependencies', 'ok');
        plugin::byId('JeeRemi')->save();
        return true;
    }

    public static function dependancy_info() {
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
}