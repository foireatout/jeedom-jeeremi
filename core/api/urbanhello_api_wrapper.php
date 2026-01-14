<?php
class JeeRemiApi {

    private static function scriptPath() {
        return realpath(dirname(__FILE__) . '/../../resources/urbanhello_api.py');
    }

    private static function runPythonCommand(array $args) {

        $venv_python = __DIR__ . '/../../resources/python_venv/bin/python3';
        if (!file_exists($venv_python) || !is_executable($venv_python)) {
            log::add('JeeRemi', 'error', 'JeeRemi: API non disponible');
            log::add('JeeRemi', 'info', 'ERROR: Erreur de communication avec l\'API UrbanHello.');
            log::add('JeeRemi', 'debug', 'Venv Python non disponible : ' . $venv_python);
            return false;
        }

        $script = self::scriptPath();
        if ($script === false || !file_exists($script)) {
            log::add('JeeRemi', 'error', 'JeeRemi: API non disponible');
            log::add('JeeRemi', 'info', 'ERROR: Erreur de communication avec l\'API UrbanHello.');
            log::add('JeeRemi', 'debug', 'urbanhello_api.py introuvable');
            return false;
        }

        $cmdParts = array_merge([$venv_python, $script], $args);
        $cmdEscaped = '';
        foreach ($cmdParts as $p) {
            $cmdEscaped .= ' ' . escapeshellarg($p);
        }
        $cmdEscaped = trim($cmdEscaped);

        log::add('JeeRemi', 'debug', 'Exécution commande Python : ' . $cmdEscaped);

        $output = [];
        $returnVar = 0;
        exec($cmdEscaped . ' 2>&1', $output, $returnVar);
        $outputText = trim(implode("\n", $output));

        if ($returnVar !== 0) {

            log::add('JeeRemi', 'error', 'JeeRemi: API non disponible');

            log::add(
                'JeeRemi',
                'info',
                'ERROR: Erreur de communication avec l\'API UrbanHello.'
            );

            log::add(
                'JeeRemi',
                'debug',
                'Erreur Python (rc=' . $returnVar . ') : ' . $outputText
            );

            return false;
        }

        log::add('JeeRemi', 'debug', 'OK: Connexion à l\'API réussie');

        if ($outputText === '') {
            return [];
        }

        $json = json_decode($outputText, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }

        return $outputText;
    }

  	public static function login($username, $password) {
        if ($username === null || $password === null) {
            return false;
        }
        return self::runPythonCommand(['login', $username, $password]);
    }

    public static function userInfo($token, $userObjectId, $attribute = null) {
        if ($token === null || $userObjectId === null) {
            return false;
        }
        $args = ['user_info', $token, $userObjectId];
        if ($attribute !== null) $args[] = $attribute;
        return self::runPythonCommand($args);
    }

    public static function remiInfo($token, $remiId, $attribute = null) {
        if ($token === null || $remiId === null) {
            return false;
        }
        $args = ['remi_info', $token, $remiId];
        if ($attribute !== null) $args[] = $attribute;
        return self::runPythonCommand($args);
    }

    public static function setLuminosity($token, $remiId, $level) {
        return self::runPythonCommand(['set_luminosity', $token, $remiId, (string)$level]);
    }

    public static function setNightLuminosity($token, $remiId, $level) {
        return self::runPythonCommand(['set_nightluminosity', $token, $remiId, (string)$level]);
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

    public static function getFace($token, $remiId) {
        return self::runPythonCommand(['get_face', $token, $remiId]);
    }

    public static function listMusics($token, $remiId) {
        return self::runPythonCommand(['list_music', $token, $remiId]);
    }

    public static function listAlarms($token, $remiId) {
        return self::runPythonCommand(['list_events', $token, $remiId]);
    }

    public static function setAlarmEnabled($token, $alarmId, $enabled) {
        return self::runPythonCommand(['set_alarm_enabled', $token, $alarmId, $enabled ? '1' : '0']);
    }

    public static function updateEvent($token, $eventId, $payload) {
	return self::runPythonCommand(['update_event', $token, $eventId, json_encode($payload)]);
    }
}
