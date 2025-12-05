<?php
class JeeRemiApi {

    private static function scriptPath() {
        return realpath(dirname(__FILE__) . '/../../resources/urbanhello_api.py');
    }

    /**
     * Exécute le script Python avec les arguments fournis.
     * Retourne array (décodé JSON) ou false en cas d'erreur.
     */
private static function runPythonCommand(array $args) {
    $venv_python = __DIR__ . '/../../resources/python_venv/bin/python3';
    if (!file_exists($venv_python) || !is_executable($venv_python)) {
        log::add('JeeRemi', 'error', 'Venv Python non disponible : ' . $venv_python);
        return false;
    }

    $script = self::scriptPath();
    if ($script === false || !file_exists($script)) {
        log::add('JeeRemi', 'error', 'urbanhello_api.py introuvable : ' . $script);
        return false;
    }

    // Construire la commande avec le venv
    $cmdParts = array_merge([$venv_python, $script], $args);
    $cmdEscaped = '';
    foreach ($cmdParts as $p) {
        $cmdEscaped .= ' ' . escapeshellarg($p);
    }
    $cmdEscaped = trim($cmdEscaped);

    log::add('JeeRemi', 'debug', 'Exécution de la commande : ' . $cmdEscaped);

    $output = [];
    $returnVar = 0;
    exec($cmdEscaped . ' 2>&1', $output, $returnVar);
    $outputText = implode("\n", $output);

    if ($returnVar !== 0) {
        log::add('JeeRemi', 'error', 'Erreur Python (rc=' . $returnVar . ') : ' . $outputText);
        return false;
    }

    $trim = trim($outputText);
    if ($trim === '') {
        return [];
    }

    $json = json_decode($trim, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        return $json;
    }

    return $trim;
}


    // --- Méthodes exposées au plugin ---

    public static function login($username, $password) {
        if ($username === null || $password === null) {
            log::add('JeeRemi', 'error', 'login: paramètres vides');
            return false;
        }
        return self::runPythonCommand(['login', $username, $password]);
    }

    public static function userInfo($token, $userObjectId, $attribute = null) {
        if ($token === null || $userObjectId === null) {
            log::add('JeeRemi', 'error', 'userInfo: paramètres vides');
            return false;
        }
        $args = ['user_info', $token, $userObjectId];
        if ($attribute !== null) $args[] = $attribute;
        return self::runPythonCommand($args);
    }

    public static function remiInfo($token, $remiId, $attribute = null) {
        if ($token === null || $remiId === null) {
            log::add('JeeRemi', 'error', 'remiInfo: paramètres vides');
            return false;
        }
        $args = ['remi_info', $token, $remiId];
        if ($attribute !== null) $args[] = $attribute;
        return self::runPythonCommand($args);
    }

    public static function getAlarms($token, $remiId) {
        return self::runPythonCommand(['get_alarms', $token, $remiId]);
    }

    public static function setLuminosity($token, $remiId, $level) {
        return self::runPythonCommand(['set_luminosity', $token, $remiId, (string)$level]);
    }

    public static function setVolume($token, $remiId, $level) {
        return self::runPythonCommand(['set_volume', $token, $remiId, (string)$level]);
    }

    public static function setFace($token, $remiId, $faceName) {
        return self::runPythonCommand(['set_face', $token, $remiId, $faceName]);
    }

    public static function playMusic($token, $remiId, $filename) {
        return self::runPythonCommand(['play_music', $token, $remiId, $filename]);
    }

    public static function stopMusic($token, $remiId) {
        return self::runPythonCommand(['stop_music', $token, $remiId]);
    }

    public static function getMusicPath($token, $remiId) {
        return self::runPythonCommand(['music_path', $token, $remiId]);
    }

    public static function getMusicMode($token, $remiId) {
        return self::runPythonCommand(['music_mode', $token, $remiId]);
    }

    // utilitaires supplémentaires si nécessaires
    public static function getTemperature($token, $remiId) {
        return self::runPythonCommand(['get_temperature', $token, $remiId]);
    }

    public static function getFace($token, $remiId) {
        return self::runPythonCommand(['get_face', $token, $remiId]);
    }

}