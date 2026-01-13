<?php
require_once dirname(__FILE__) . '/JeeRemiCmd.class.php';
require_once dirname(__FILE__) . '/../api/urbanhello_api_wrapper.php';

class JeeRemi extends eqLogic {

    // Exclure le venv Python des sauvegardes
    public static function backupExclude() {
        return ['resources/python_venv'];
    }

    // Synchronisation des REMI
    public static function syncRemi() {
        log::add('JeeRemi', 'debug', 'Lancement de syncRemi()');
        $sessionToken = config::byKey('sessionToken', 'JeeRemi', '');
        $userId = config::byKey('userId', 'JeeRemi', '');
        if ($sessionToken == '' || $userId == '') {
            $username = config::byKey('username', 'JeeRemi', '');
            $password = config::byKey('password', 'JeeRemi', '');
            if ($username == '' || $password == '') {
                log::add('JeeRemi', 'error', 'Plugin non configuré');
                return;
            }
            $login = JeeRemiApi::login($username, $password);
            if (!is_array($login) || !isset($login['sessionToken'])) {
                log::add('JeeRemi', 'error', 'Login API échoué: ' . json_encode($login));
                return;
            }
            config::save('sessionToken', $login['sessionToken'], 'JeeRemi');
            config::save('userId', $login['objectId'], 'JeeRemi');
            $sessionToken = $login['sessionToken'];
            $userId = $login['objectId'];
        }
        $userInfo = JeeRemiApi::userInfo($sessionToken, $userId);
        if (!is_array($userInfo) || !isset($userInfo['remis'])) {
            log::add('JeeRemi', 'error', 'Aucun REMI trouvé dans userInfo');
            return;
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
                log::add('JeeRemi', 'debug', 'Création équipement REMI: ' . $remiId);
            } else {
                log::add('JeeRemi', 'debug', 'Équipement REMI existant: ' . $remiId);
            }
            $eq->createCommands();
            $eq->updateInfos();
        }
        log::add('JeeRemi', 'debug', 'syncRemi terminé');
    }

    // Cron 5 minutes
    public static function cron5() {
        foreach (self::byType('JeeRemi') as $eq) {
            $eq->updateInfos();
        }
    }

    // Création des commandes
    public function createCommands() {
        log::add('JeeRemi', 'debug', 'Création des commandes pour équipement: ' . $this->getLogicalId());

        // Commandes obligatoires
        $cmds = [
            ['Remi_ID', 'info', 'string', 'Remi ID', []],
            ['Remi_unique_ID', 'info', 'other', 'ID unique du REMI', []],
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
                log::add('JeeRemi', 'debug', 'Création commande ' . $logical);
            } else {
                // Mettre à jour listValue si défini
                if (isset($opts['listValue'])) {
                    $cmd->setConfiguration('listValue', $opts['listValue']);
                    $cmd->save();
                }
            }
        }

        // Commandes optionnelles (dataBundle)
        $optionalCommands = [
            ['gettime_wayback_max_s', 'info', 'numeric', 'Gettime Wayback Max', ['unit' => 's'], true],
            ['call_wayback_max_s', 'info', 'numeric', 'Call Wayback Max', ['unit' => 's'], true],
            ['gettime_shift_s', 'info', 'numeric', 'Gettime Shift', ['unit' => 's'], true],
            ['day_reconnection_count', 'info', 'numeric', 'Reconnexions/jour', [], true],
            ['day_disconnection_time', 'info', 'numeric', 'Temps déconnexion/jour', ['unit' => 's'], true],
        ];

        foreach ($optionalCommands as $c) {
            list($logical, $type, $subtype, $name, $opts, $isOptional) = array_pad($c, 6, null);
            if ($isOptional && $this->hasDataBundleKey($logical)) {
                $this->createOptionalCommand($logical, $type, $subtype, $name, $opts);
            }
        }
    }

    // Vérifie si une clé existe dans dataBundle
    private function hasDataBundleKey($key) {
        $info = $this->getRemiInfo();
        if (!isset($info['dataBundle'])) {
            return false;
        }

        if (is_string($info['dataBundle'])) {
            $pairs = explode(' ', $info['dataBundle']);
            foreach ($pairs as $pair) {
                if (strpos($pair, ':') !== false) {
                    [$k, $v] = explode(':', $pair, 2);
                    if ($k === $key) {
                        return true;
                    }
                }
            }
        } elseif (is_array($info['dataBundle'])) {
            return isset($info['dataBundle'][$key]);
        }

        return false;
    }

    // Crée une commande optionnelle
    private function createOptionalCommand($logical, $type, $subtype, $name, $opts) {
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
            $cmd->save();
            log::add('JeeRemi', 'debug', 'Création commande optionnelle ' . $logical);
        }
    }

    // Récupère les infos du REMI
    private function getRemiInfo() {
        $username = config::byKey('username', 'JeeRemi', '');
        $password = config::byKey('password', 'JeeRemi', '');
        if ($username == '' || $password == '') {
            return [];
        }
        $login = JeeRemiApi::login($username, $password);
        if (!is_array($login) || !isset($login['sessionToken'])) {
            return [];
        }
        $token = $login['sessionToken'];
        $id = $this->getLogicalId();
        return JeeRemiApi::remiInfo($token, $id);
    }

    // Met à jour les listes d'actions pour event_enable/event_disable
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

        foreach (['event_enable', 'event_disable'] as $logical) {
            $cmd = $this->getCmd(null, $logical);
            if (!is_object($cmd)) {
                $cmd = new JeeRemiCmd();
                $cmd->setEqLogic_id($this->getId());
                $cmd->setLogicalId($logical);
                $cmd->setType('action');
                $cmd->setSubType('select');
                $cmd->setName(($logical === 'event_enable') ? 'Activer un réveil' : 'Désactiver un réveil');
            }
            $cmd->setConfiguration('listValue', implode(';', $listValues));
            $cmd->save();
        }
    }

    // Met à jour la liste des réveils (event_list)
    private function updateEventListInfo(array $alarms) {
        $eventListCmd = $this->getCmd(null, 'event_list');
        if (!is_object($eventListCmd)) {
            return;
        }

        $lines = [];
        foreach ($alarms as $alarm) {
            if (empty($alarm['event_time'])) continue;
            $hour = str_pad($alarm['event_time'][0], 2, '0', STR_PAD_LEFT);
            $minute = str_pad($alarm['event_time'][1], 2, '0', STR_PAD_LEFT);
            $time = $hour . ':' . $minute;
            $name = trim($alarm['name'] ?? 'Réveil');
            $state = !empty($alarm['enabled']) ? '1' : '0';
            $lines[] = "$time - $name: $state";
        }

        $eventListCmd->event(implode("\n", $lines));
    }

    // Synchronise les alarmes
    private function syncAlarms(array $alarms, $token) {
        // Récupérer les objectId des alarmes actuelles
        $currentAlarmIds = [];
        foreach ($alarms as $alarm) {
            if (!empty($alarm['objectId'])) {
                $currentAlarmIds[] = $alarm['objectId'];
            }
        }

        // Supprimer les commandes alarm_* dont l'objectId n'est plus dans $currentAlarmIds
        $existingCommands = $this->getCmd();
        foreach ($existingCommands as $cmd) {
            if (strpos($cmd->getLogicalId(), 'alarm_') === 0) {
                $alarmId = str_replace('alarm_', '', $cmd->getLogicalId());
                if (!in_array($alarmId, $currentAlarmIds)) {
                    $cmd->remove();
                    log::add('JeeRemi', 'debug', '[ALARMS] Suppression de la commande obsolète : ' . $cmd->getName());
                }
            }
        }

        // Créer/mettre à jour les commandes pour les alarmes actuelles
        foreach ($alarms as $alarm) {
            if (empty($alarm['objectId']) || empty($alarm['event_time'])) {
                continue;
            }

            $hour = str_pad($alarm['event_time'][0], 2, '0', STR_PAD_LEFT);
            $minute = str_pad($alarm['event_time'][1], 2, '0', STR_PAD_LEFT);
            $time = $hour . ':' . $minute;
            $name = trim($alarm['name'] ?? '');
            if ($name === '') {
                $name = 'Réveil';
            }
            $displayName = $time . ' – ' . $name;
            $logicalId = 'alarm_' . $alarm['objectId'];

            // Vérifier si la commande existe déjà
            $cmd = cmd::byEqLogicIdAndLogicalId($this->getId(), $logicalId);
            if (!is_object($cmd)) {
                $cmd = new cmd();
                $cmd->setEqLogic_id($this->getId());
                $cmd->setLogicalId($logicalId);
                $cmd->setName($displayName);
                $cmd->setType('info');
                $cmd->setSubType('binary');
                $cmd->setIsVisible(1);
                $cmd->setIsHistorized(0);
                $cmd->setDisplay('generic_type', 'LIGHT_STATE');
                $cmd->save();
                log::add('JeeRemi', 'info', '[ALARMS] Création commande : ' . $displayName);
            }

            // Mettre à jour l'état
            $state = !empty($alarm['enabled']) ? 1 : 0;
            $cmd->event($state);
        }

        // Rafraîchir l'équipement
        $this->refresh();
    }

    // Met à jour les infos du REMI
    public function updateInfos() {
        log::add('JeeRemi', 'debug', 'Mise à jour des infos pour REMI: ' . $this->getLogicalId());
        $username = config::byKey('username', 'JeeRemi', '');
        $password = config::byKey('password', 'JeeRemi', '');
        if ($username == '' || $password == '') {
            log::add('JeeRemi', 'debug', 'Plugin non configuré');
            return;
        }
        $login = JeeRemiApi::login($username, $password);
        if (!is_array($login) || !isset($login['sessionToken'])) {
            log::add('JeeRemi', 'debug', 'Login API échoué');
            return;
        }
        $token = $login['sessionToken'];
        $id = $this->getLogicalId();
        $info = JeeRemiApi::remiInfo($token, $id);
        if (!is_array($info)) {
            log::add('JeeRemi', 'debug', 'remiInfo non array pour ' . $id);
            return;
        }

        // ReMapping musiques
        $musics = JeeRemiApi::listMusics($token, $id);
        if (is_array($musics) && count($musics) > 0) {
            $listValue = [];
            foreach ($musics as $music) {
                // Cas 1 : $music est un tableau avec 'name' et 'path'
                if (is_array($music) && isset($music['name'])) {
                    $fileName = $music['name'];
                    $filePath = isset($music['path']) && $music['path'] !== '' ? $music['path'] . '\\' : '';
                    $fullPath = $filePath . $fileName; // Chemin complet pour la valeur envoyée
                    $displayLabel = pathinfo($fileName, PATHINFO_FILENAME); // Nom sans extension pour l'affichage
                    $listValue[] = $fullPath . '|' . $displayLabel;
                }
                // Cas 2 : $music est une chaîne (ancien format)
                else {
                    $displayLabel = pathinfo($music, PATHINFO_FILENAME);
                    $listValue[] = $music . '|' . $displayLabel;
                }
            }

            // Mise à jour de play_selectmusic
            $selectCmd = $this->getCmd(null, 'play_selectmusic');
            if (is_object($selectCmd)) {
                $selectCmd->setConfiguration('listValue', implode(';', $listValue));
                $selectCmd->save();
            }

            // Mise à jour de music_list (liste brute)
            $listCmd = $this->getCmd(null, 'music_list');
            if (is_object($listCmd)) {
                $listCmd->event(json_encode($musics, JSON_UNESCAPED_UNICODE));
            }
        } else {
            // Valeur par défaut si aucune musique n'est disponible
            $selectCmd = $this->getCmd(null, 'play_selectmusic');
            if (is_object($selectCmd)) {
                $selectCmd->setConfiguration('listValue', '|Aucune musique disponible');
                $selectCmd->save();
            }
        }

        // Re-Mapping Alarms
        $alarms = JeeRemiApi::listAlarms($token, $id);
        if (is_array($alarms)) {
            $filteredAlarms = array_filter($alarms, function($alarm) use ($id) {
                return isset($alarm['objectId'])
                    && isset($alarm['remi']['objectId'])
                    && $alarm['remi']['objectId'] === $id;
            });
            $this->syncAlarms($filteredAlarms, $token);
            $this->updateAlarmActionLists($filteredAlarms);
            $this->updateEventListInfo($filteredAlarms);
        }

        // Re-Mapping dataBundles
        if (!empty($info['dataBundle'])) {
            $data = [];
            if (is_string($info['dataBundle'])) {
                $pairs = explode(' ', $info['dataBundle']);
                foreach ($pairs as $pair) {
                    if (strpos($pair, ':') !== false) {
                        [$k, $v] = explode(':', $pair, 2);
                        $data[$k] = (int)$v;
                    }
                }
            } elseif (is_array($info['dataBundle'])) {
                $data = $info['dataBundle'];
            }

            // Créer et mettre à jour les commandes uniquement si les clés existent dans $data
            $optionalCommands = [
                'gettime_wayback_max_s' => ['type' => 'info', 'subtype' => 'numeric', 'name' => 'Gettime Wayback Max', 'unit' => 's'],
                'call_wayback_max_s' => ['type' => 'info', 'subtype' => 'numeric', 'name' => 'Call Wayback Max', 'unit' => 's'],
                'gettime_shift_s' => ['type' => 'info', 'subtype' => 'numeric', 'name' => 'Gettime Shift', 'unit' => 's'],
                'day_reconnection_count' => ['type' => 'info', 'subtype' => 'numeric', 'name' => 'Reconnexions/jour'],
                'day_disconnection_time' => ['type' => 'info', 'subtype' => 'numeric', 'name' => 'Temps déconnexion/jour', 'unit' => 's'],
            ];

            foreach ($optionalCommands as $key => $config) {
                if (isset($data[$key])) {
                    $cmd = $this->getCmd(null, $key);
                    if (!is_object($cmd)) {
                        $cmd = new JeeRemiCmd();
                        $cmd->setEqLogic_id($this->getId());
                        $cmd->setLogicalId($key);
                        $cmd->setName($config['name']);
                        $cmd->setType($config['type']);
                        $cmd->setSubType($config['subtype']);
                        if (isset($config['unit'])) {
                            $cmd->setUnite($config['unit']);
                        }
                        $cmd->save();
                        log::add('JeeRemi', 'debug', 'Création commande dataBundle : ' . $key);
                    }
                    $cmd->event($data[$key]);
                }
            }
        }

        // Mapping des couleurs
        $colorMap = [
            '62,177,200' => 'blue',
            '255,115,120' => 'pink',
            '254,219,0' => 'yellow',
            '205,205,205' => 'grey'
        ];
        $bgColor = '205,205,205';
        if (isset($info['background_color']) && is_array($info['background_color']) && count($info['background_color']) == 3) {
            $bgColor = implode(',', $info['background_color']);
        }
        $backgroundColor = $colorMap[$bgColor] ?? 'blue';

        // Mise à jour des commandes
        $commandMap = [
            'luminosity' => 'veilleuse',
            'facenum' => 'Visage_num',
            'temp' => 'temperature',
            'light_min' => 'light_min',
            'volume' => 'volume',
            'name' => 'nom',
            'online' => 'online',
            'alive' => 'alive',
            'updateAt' => 'last_update',
            'ipv4Address' => 'IP',
            'rssi' => 'RSSI',
            'face' => 'face',
            'musicPath' => 'MusicPath',
            'musicMode' => 'MusicMode',
            'background_color' => 'background_color',
            'update_firmware_version' => 'firmware_version',
            'firmware_need_update' => 'firmware_need_update',
            'uniqueID' => 'Remi_unique_ID'
        ];

        foreach ($commandMap as $apiKey => $cmdName) {
            $cmd = $this->getCmd(null, $cmdName);
            if (is_object($cmd)) {
                if (isset($info[$apiKey])) {
                    $val = $info[$apiKey];
                    if ($apiKey === 'face') {
                        $faceName = JeeRemiApi::getFace($token, $id);
                        $cmd->event($faceName);
                        $visageNumCmd = $this->getCmd(null, 'Visage_num');
                        if (is_object($visageNumCmd)) {
                            $faceMap = [
                                'awakeFace' => 1,
                                'sleepyFace' => 2,
                                'semiAwakeFace' => 3,
                                'blankFace' => 4
                            ];
                            $visageNumCmd->event($faceMap[$faceName] ?? 0);
                        }
                    } elseif ($apiKey === 'temp') {
                        $val = round($val * 0.128);
                        $cmd->event($val);
                    } elseif ($apiKey === 'light_min') {
                        $val = round($val * 10);
                        $cmd->event($val);
                    } elseif ($apiKey === 'background_color') {
                        $cmd->event($backgroundColor);
                    } else {
                        $cmd->event($val);
                    }
                }
            }
        }

        if (isset($info['noise_notification_subscribers']) && is_array($info['noise_notification_subscribers'])) {
            $this->getCmd(null, 'noise_notification_subscribers')->event(count($info['noise_notification_subscribers']));
        }

        // Mise à jour du nom de l'équipement
        if (isset($info['name']) && strpos($this->getName(), 'REMI ' . $id) === 0) {
            $this->setName($info['name']);
            $this->save();
        }

        // Mise à jour de Remi_ID et last_update
        $remiIdCmd = $this->getCmd(null, 'Remi_ID');
        if (is_object($remiIdCmd)) {
            $remiIdCmd->event($id);
        }
        $lastUpdateCmd = $this->getCmd(null, 'last_update');
        if (is_object($lastUpdateCmd)) {
            $lastUpdateCmd->event(date('d-m-Y H:i:s'));
        }
    }
}

