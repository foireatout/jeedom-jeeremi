<?php
require_once dirname(__FILE__) . '/JeeRemiCmd.class.php';
require_once dirname(__FILE__) . '/../api/urbanhello_api_wrapper.php';

class JeeRemi extends eqLogic {

    public static function backupExclude() {
        return ['resources/python_venv'];
    }

    /**
     * Récupère un token de session valide (en cache ou via login si expiré/manquant)
     */
    public static function getValidSessionToken($forceRefresh = false) {
        $sessionToken = config::byKey('sessionToken', 'JeeRemi', '');
        
        if ($sessionToken !== '' && !$forceRefresh) {
            return $sessionToken;
        }

        $username = config::byKey('username', 'JeeRemi', '');
        $password = config::byKey('password', 'JeeRemi', '');
        if ($username === '' || $password === '') {
            log::add('JeeRemi', 'warning', 'Identifiants JeeRemi non configurés');
            return null;
        }

        log::add('JeeRemi', 'info', 'Génération d\'un nouveau token de session Parse...');
        $login = JeeRemiApi::login($username, $password);
        if (is_array($login) && isset($login['sessionToken'])) {
            config::save('sessionToken', $login['sessionToken'], 'JeeRemi');
            config::save('userId', $login['objectId'] ?? '', 'JeeRemi');
            return $login['sessionToken'];
        }

        log::add('JeeRemi', 'warning', 'Échec de connexion lors du renouvellement du token.');
        return null;
    }

    public static function syncRemi() {
        log::add('JeeRemi', 'debug', 'Lancement de syncRemi()');
        $sessionToken = self::getValidSessionToken();
        $userId = config::byKey('userId', 'JeeRemi', '');

        if (!$sessionToken || !$userId) {
            log::add('JeeRemi', 'error', 'Impossible de synchroniser : identifiants ou token invalides');
            return;
        }

        $userInfo = JeeRemiApi::userInfo($sessionToken, $userId);
        if (!is_array($userInfo) || !isset($userInfo['remis'])) {
            // Tentative de re-login au cas où le token aurait expiré
            $sessionToken = self::getValidSessionToken(true);
            $userId = config::byKey('userId', 'JeeRemi', '');
            $userInfo = JeeRemiApi::userInfo($sessionToken, $userId);
            if (!is_array($userInfo) || !isset($userInfo['remis'])) {
                log::add('JeeRemi', 'error', 'Aucun REMI trouvé dans userInfo');
                return;
            }
        }

        foreach ($userInfo['remis'] as $remi) {
            $remiId = is_array($remi) && isset($remi['objectId']) ? $remi['objectId'] : $remi;
            log::add('JeeRemi', 'debug', 'Traitement du REMI ID: ' . $remiId);
            $eq = eqLogic::byLogicalId($remiId, 'JeeRemi');
            if (!is_object($eq)) {
                $eq = new JeeRemi();
                $eq->setEqType_name('JeeRemi');
                $eq->setLogicalId($remiId);
                $eq->setName('REMI ' . $remiId);
                $eq->setIsEnable(1);
                $eq->setIsVisible(1);
                $eq->save();
            }
            $eq->createCommands();
            $eq->updateInfos();
        }
    }

    public function prepareEventPayload($param, $value) {
        $payload = [];
        switch ($param) {
            case 'enabled':
                $payload['enabled'] = ($value == '1' || $value == 'true' || $value == 'on');
                break;
            case 'volume':
                $payload['volume'] = (int)$value;
                break;
            case 'name':
                $payload['name'] = (string)$value;
                break;
            case 'music_path':
                $payload['music_path'] = (string)$value;
                break;
            case 'time':
                $h = null; $m = null;
                if (strpos($value, ':') !== false) {
                    $parts = explode(':', $value);
                    $h = $parts[0]; $m = $parts[1];
                } elseif (strlen($value) == 4 && is_numeric($value)) {
                    $h = substr($value, 0, 2);
                    $m = substr($value, 2, 2);
                }
                if ($h !== null && $m !== null) {
                    $payload['event_time'] = [(int)$h, (int)$m];
                }
                break;
            case 'light':
                $payload['brightness'] = max(0, min(100, (int)$value));
                break;
            case 'recurrence':
                $days = explode(',', $value);
                if (count($days) == 7) {
                    $payload['recurrence'] = array_map('intval', $days);
                }
                break;
            case 'face':
                // Correction du bug sur blankFace
                $faceMap = [
                    'awakeFace'     => 'fIjF0yWRxX',
                    'sleepyFace'    => 'rnAltoFwYC',
                    'semiAwakeFace' => '9faiiPGBVv',
                    'blankFace'     => 'GDaZOVdRqj'
                ];
                if (isset($faceMap[$value])) {
                    $payload['face'] = [
                        '__type' => 'Pointer',
                        'className' => 'Face',
                        'objectId' => $faceMap[$value]
                    ];
                }
                break;
        }
        return $payload;
    }

    public static function cron5() {
        foreach (self::byType('JeeRemi', true) as $eq) {
            $eq->updateInfos();
        }
    }

    public function createCommands() {
        $cmds = [
            ['Remi_ID', 'info', 'string', 'Remi ID', []],
            ['Remi_unique_ID', 'info', 'string', 'ID unique du REMI', []],
            ['last_update', 'info', 'string', 'Dernière mise à jour', []],
            ['nom', 'info', 'string', 'Nom', []],
            ['firmware_version', 'info', 'string', 'Version du firmware', []],
            ['firmware_need_update', 'info', 'binary', 'Mise à jour firmware nécessaire', []],
            ['alive', 'info', 'binary', 'Alive', []],
            ['online', 'info', 'binary', 'Online', []],
            ['background_color', 'info', 'string', 'Couleur de fond', []],
            ['IP', 'info', 'string', 'IP', []],
            ['RSSI', 'info', 'numeric', 'RSSI', ['unit' => 'dB']],
            ['temperature', 'info', 'numeric', 'Température', ['unit' => '°C']],
            ['MusicMode', 'info', 'string', 'MusicMode', []],
            ['MusicPath', 'info', 'string', 'MusicPath', []],
            ['play_music', 'action', 'message', 'Démarrer musique', ['template' => '{{message}}']],
            ['stop_music', 'action', 'other', 'Arrêter musique', []],
            ['veilleuse', 'info', 'numeric', 'Luminosité', ['unit' => '%']],
            ['set_veilleuse', 'action', 'slider', 'Régler luminosité', ['min' => 0, 'max' => 100, 'unit' => '%']],
            ['light_min', 'info', 'numeric', 'Lum. Ecran Nuit', ['min' => 0, 'max' => 100, 'unit' => '%']],
            ['set_light_min', 'action', 'slider', 'Régler lum. Ecran Nuit', ['min' => 0, 'max' => 100, 'unit' => '%']],
            ['volume', 'info', 'numeric', 'Volume', ['unit' => '%']],
            ['set_volume', 'action', 'slider', 'Régler volume', ['min' => 0, 'max' => 100, 'unit' => '%']],
            ['face', 'info', 'string', 'Face', []],
            ['Visage_num', 'info', 'numeric', 'Visage (num)', []],
            ['awakeFace', 'action', 'other', 'Visage éveillé', []],
            ['sleepyFace', 'action', 'other', 'Visage endormi', []],
            ['blankFace', 'action', 'other', 'Visage blanc', []],
            ['semiAwakeFace', 'action', 'other', 'Visage semi-ouvert', []],
            ['music_list', 'info', 'string', 'Liste musiques', []],
            ['play_selectmusic', 'action', 'select', 'Jouer une musique', []],
            ['event_list', 'info', 'string', 'Liste des réveils', []],
            ['noise_notification_subscribers', 'info', 'numeric', 'Nb abonnés bruit', []],
            ['refresh', 'action', 'other', 'Rafraîchir', []],
            ['event_enable', 'action', 'select', 'Activer un réveil', []],
            ['event_disable', 'action', 'select', 'Désactiver un réveil', []],
            ['event_set_param', 'action', 'message', 'Modifier un paramètre de reveil', []],
            ['set_face', 'action', 'select', 'Changer de visage', [
                'listValue' => 'awakeFace|Visage éveillé;sleepyFace|Visage endormi;blankFace|Visage blanc;semiAwakeFace|Visage semi-ouvert'
            ]],
        ];

        foreach ($cmds as $c) {
            list($logical, $type, $subtype, $name, $opts) = array_pad($c, 5, []);
            $cmd = $this->getCmd(null, $logical);
            if (!is_object($cmd)) {
                $cmd = new JeeRemiCmd();
                $cmd->setName($name);
                $cmd->setEqLogic_id($this->getId());
                $cmd->setLogicalId($logical);
                $cmd->setType($type);
                $cmd->setSubType($subtype);
                if (isset($opts['min'])) $cmd->setConfiguration('minValue', $opts['min']);
                if (isset($opts['max'])) $cmd->setConfiguration('maxValue', $opts['max']);
                if (isset($opts['unit'])) $cmd->setUnite($opts['unit']);
                if (isset($opts['listValue'])) $cmd->setConfiguration('listValue', $opts['listValue']);
                if ($subtype == 'message' && isset($opts['template'])) $cmd->setConfiguration('template', $opts['template']);
                $cmd->save();
            }
        }
    }

    private function updateAlarmActionLists(array $alarms) {
        $listValues = [];
        foreach ($alarms as $alarm) {
            if (empty($alarm['objectId']) || empty($alarm['event_time'])) continue;
            $hour = str_pad($alarm['event_time'][0], 2, '0', STR_PAD_LEFT);
            $minute = str_pad($alarm['event_time'][1], 2, '0', STR_PAD_LEFT);
            $time = $hour . ':' . $minute;
            $name = trim($alarm['name'] ?? 'Réveil');
            $listValues[] = $alarm['objectId'] . '|' . $time . ' - ' . $name;
        }

        $listStr = implode(';', $listValues);
        foreach (['event_enable', 'event_disable'] as $logical) {
            $cmd = $this->getCmd(null, $logical);
            if (is_object($cmd)) {
                if ($cmd->getConfiguration('listValue') !== $listStr) {
                    $cmd->setConfiguration('listValue', $listStr);
                    $cmd->save();
                }
            }
        }
    }

    private function syncAlarms(array $alarms) {
        $currentAlarmIds = [];
        foreach ($alarms as $alarm) {
            if (!empty($alarm['objectId'])) {
                $currentAlarmIds[] = $alarm['objectId'];
            }
        }

        foreach ($this->getCmd() as $cmd) {
            if (strpos($cmd->getLogicalId(), 'alarm_') === 0) {
                $alarmId = str_replace('alarm_', '', $cmd->getLogicalId());
                if (!in_array($alarmId, $currentAlarmIds)) {
                    $cmd->remove();
                }
            }
        }

        foreach ($alarms as $alarm) {
            if (empty($alarm['objectId']) || empty($alarm['event_time'])) continue;

            $hour = str_pad($alarm['event_time'][0], 2, '0', STR_PAD_LEFT);
            $minute = str_pad($alarm['event_time'][1], 2, '0', STR_PAD_LEFT);
            $time = $hour . ':' . $minute;
            $name = trim($alarm['name'] ?? '') ?: 'Réveil';
            $logicalId = 'alarm_' . $alarm['objectId'];

            $cmd = $this->getCmd(null, $logicalId);
            if (!is_object($cmd)) {
                $cmd = new JeeRemiCmd();
                $cmd->setEqLogic_id($this->getId());
                $cmd->setLogicalId($logicalId);
                $cmd->setName($time . ' – ' . $name);
                $cmd->setType('info');
                $cmd->setSubType('binary');
                $cmd->setIsVisible(1);
                $cmd->setIsHistorized(0);
                $cmd->save();
            }

            $state = !empty($alarm['enabled']) ? 1 : 0;
            $this->checkAndUpdateCmd($logicalId, $state);
        }
    }

    public function updateInfos() {
        $id = $this->getLogicalId();
        $token = self::getValidSessionToken();

        if (!$token) {
            $this->handleSyncError('Identifiants manquants ou session impossible');
            return;
        }

        $info = JeeRemiApi::remiInfo($token, $id);

        // Si la requête échoue, on tente un renouvellement de token (au cas où il ait expiré)
        if (!is_array($info)) {
            $token = self::getValidSessionToken(true);
            if ($token) {
                $info = JeeRemiApi::remiInfo($token, $id);
            }
        }

        if (!is_array($info)) {
            $this->handleSyncError('Impossible de joindre le REMI ' . $id);
            return;
        }

        // Succès de la synchronisation : réinitialisation du compteur d'erreurs
        $this->handleSyncSuccess();

        // 1. Musiques
        $musics = JeeRemiApi::listMusics($token, $id);
        if (is_array($musics) && count($musics) > 0) {
            $listValue = [];
            foreach ($musics as $music) {
                if (is_array($music) && isset($music['name'])) {
                    $fileName = $music['name'];
                    $filePath = !empty($music['path']) ? $music['path'] . '\\' : '';
                    $listValue[] = ($filePath . $fileName) . '|' . pathinfo($fileName, PATHINFO_FILENAME);
                } else {
                    $listValue[] = $music . '|' . pathinfo($music, PATHINFO_FILENAME);
                }
            }
            $selectCmd = $this->getCmd(null, 'play_selectmusic');
            if (is_object($selectCmd)) {
                $selectCmd->setConfiguration('listValue', implode(';', $listValue));
                $selectCmd->save();
            }
            $this->checkAndUpdateCmd('music_list', json_encode($musics, JSON_UNESCAPED_UNICODE));
        }

        // 2. Alarmes
        $alarms = JeeRemiApi::listAlarms($token, $id);
        if (is_array($alarms)) {
            $filteredAlarms = array_filter($alarms, function($alarm) use ($id) {
                return isset($alarm['objectId'], $alarm['remi']['objectId']) && $alarm['remi']['objectId'] === $id;
            });
            $this->syncAlarms($filteredAlarms);
            $this->updateAlarmActionLists($filteredAlarms);
        }

        // 3. Couleurs
        $colorMap = [
            '62,177,200' => 'blue',
            '255,115,120' => 'pink',
            '254,219,0' => 'yellow',
            '205,205,205' => 'grey'
        ];
        $bgColor = '205,205,205';
        if (isset($info['background_color']) && is_array($info['background_color'])) {
            $bgColor = implode(',', $info['background_color']);
        }
        $backgroundColor = $colorMap[$bgColor] ?? 'blue';

        // 4. Face (déduit directement de $info sans faire de requête HTTP en plus)
        $faceMapInv = [
            'rnAltoFwYC' => 'sleepyFace',
            'fIjF0yWRxX' => 'awakeFace',
            'GDaZOVdRqj' => 'blankFace',
            '9faiiPGBVv' => 'semiAwakeFace'
        ];
        $faceNumMap = [
            'awakeFace' => 1,
            'sleepyFace' => 2,
            'semiAwakeFace' => 3,
            'blankFace' => 4
        ];
        $faceId = $info['face']['objectId'] ?? null;
        $currentFace = $faceMapInv[$faceId] ?? 'awakeFace';

        // 5. Mise à jour optimisée via checkAndUpdateCmd
        $this->checkAndUpdateCmd('veilleuse', $info['luminosity'] ?? 0);
        $this->checkAndUpdateCmd('Visage_num', $faceNumMap[$currentFace] ?? 0);
        $this->checkAndUpdateCmd('face', $currentFace);
        $this->checkAndUpdateCmd('temperature', round(($info['temp'] ?? 0) * 0.128, 1));
        $this->checkAndUpdateCmd('light_min', round(($info['light_min'] ?? 0) * 10));
        $this->checkAndUpdateCmd('volume', $info['volume'] ?? 0);
        $this->checkAndUpdateCmd('nom', $info['name'] ?? '');
        $this->checkAndUpdateCmd('online', !empty($info['online']) ? 1 : 0);
        $this->checkAndUpdateCmd('alive', !empty($info['alive']) ? 1 : 0);
        $this->checkAndUpdateCmd('IP', $info['ipv4Address'] ?? '');
        $this->checkAndUpdateCmd('RSSI', $info['rssi'] ?? 0);
        $this->checkAndUpdateCmd('MusicPath', $info['musicPath'] ?? '');
        $this->checkAndUpdateCmd('MusicMode', $info['musicMode'] ?? '');
        $this->checkAndUpdateCmd('background_color', $backgroundColor);
        $this->checkAndUpdateCmd('firmware_version', $info['update_firmware_version'] ?? '');
        $this->checkAndUpdateCmd('firmware_need_update', !empty($info['firmware_need_update']) ? 1 : 0);
        $this->checkAndUpdateCmd('Remi_unique_ID', $info['uniqueID'] ?? '');
        $this->checkAndUpdateCmd('Remi_ID', $id);
        $this->checkAndUpdateCmd('last_update', date('d-m-Y H:i:s'));

        if (isset($info['noise_notification_subscribers']) && is_array($info['noise_notification_subscribers'])) {
            $this->checkAndUpdateCmd('noise_notification_subscribers', count($info['noise_notification_subscribers']));
        }
    }

    /**
     * Gestion du seuil des 3 erreurs consécutives
     */
    private function handleSyncError($errorMsg) {
        $errors = (int)$this->getCache('consecutive_errors', 0) + 1;
        $this->setCache('consecutive_errors', $errors);

        if ($errors >= 3) {
            log::add('JeeRemi', 'error', sprintf('Échec persistant de communication avec REMI (%d tentatives) : %s', $errors, $errorMsg));
        } else {
            log::add('JeeRemi', 'warning', sprintf('Échec temporaire (%d/3) avec REMI : %s', $errors, $errorMsg));
        }
    }

    private function handleSyncSuccess() {
        if ((int)$this->getCache('consecutive_errors', 0) > 0) {
            log::add('JeeRemi', 'info', 'Connexion rétablie avec le réveil REMI.');
            $this->setCache('consecutive_errors', 0);
        }
    }
}
