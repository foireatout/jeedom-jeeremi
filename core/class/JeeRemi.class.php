<?php
require_once dirname(__FILE__) . '/JeeRemiCmd.class.php';
require_once dirname(__FILE__) . '/../api/urbanhello_api_wrapper.php';

class JeeRemi extends eqLogic {

    public static function backupExclude() {
        return ['resources/python_venv'];
    }


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


    public function createCommands() {
        log::add('JeeRemi', 'debug', 'Création des commandes pour équipement: ' . $this->getLogicalId());
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
            ['volume', 'info', 'numeric', 'Volume', ['unit' => '%']],
            ['set_volume', 'action', 'slider', 'Régler volume', ['min' => 0, 'max' => 100, 'unit' => '%']],
            ['face', 'info', 'string', 'Face', []],
            ['Visage_num', 'info', 'numeric', 'Visage (num)', []],
            ['blankFace', 'action', 'other', 'Visage blanc', []],
            ['sleepyFace', 'action', 'other', 'Visage endormi', []],
            ['awakeFace', 'action', 'other', 'Visage éveillé', []],
            ['semiAwakeFace', 'action', 'other', 'Visage semi-ouvert', []],
            ['refresh', 'action', 'other', 'Rafraîchir', []],
        ];

        foreach ($cmds as $c) {
            list($logical, $type, $subtype, $name, $opts) = $c;
            if (!is_object($this->getCmd(null, $logical))) {
                $cmd = new JeeRemiCmd();
                $cmd->setName($name);
                $cmd->setEqLogic_id($this->getId());
                $cmd->setLogicalId($logical);
                $cmd->setType($type);
                $cmd->setSubType($subtype);
                if (isset($opts['min'])) $cmd->setConfiguration('minValue', $opts['min']);
                if (isset($opts['max'])) $cmd->setConfiguration('maxValue', $opts['max']);
                if (isset($opts['unit'])) $cmd->setUnite($opts['unit']);
                if ($subtype == 'message' && isset($opts['template'])) $cmd->setConfiguration('template', $opts['template']);
                $cmd->save();
                log::add('JeeRemi', 'debug', 'Création commande ' . $logical);
            }
        }
    }

    public static function cron5() {
        foreach (self::byType('JeeRemi') as $eq) {
            $eq->updateInfos();
        }
    }

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

        // Mapping des couleurs
        $colorMap = [
            '62,177,200' => 'blue',
            '255,115,120' => 'pink',
            '254,219,0' => 'yellow',
            '205,205,205' => 'grey'
        ];

        // Récupération de background_color
        $bgColor = '205,205,205'; // Valeur par défaut : grey
        if (isset($info['background_color']) && is_array($info['background_color']) && count($info['background_color']) == 3) {
            $bgColor = implode(',', $info['background_color']);
        }
        $backgroundColor = $colorMap[$bgColor] ?? 'blue'; // Valeur par défaut : blue

        // Mise à jour des commandes
        $map = [
            'luminosity' => 'veilleuse',
            'facenum' => 'Visage_num',
            'temp' => 'temperature',
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

        foreach ($map as $k => $cmdName) {
            $cmd = $this->getCmd(null, $cmdName);
            if (is_object($cmd)) {
                if (isset($info[$k])) {
                    $val = $info[$k];
                    if ($k === 'face') {
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
                    } elseif ($k === 'temp') {
                        $val = round($val * 0.128);
                        $cmd->event($val);
                    } elseif ($k === 'background_color') {
                        $cmd->event($backgroundColor);
                    } else {
                        $cmd->event($val);
                    }
                    log::add('JeeRemi', 'debug', 'Mise à jour de la commande ' . $cmdName . ' avec la valeur ' . $val);
                } else {
                    log::add('JeeRemi', 'debug', 'Champ "' . $k . '" manquant dans la réponse API pour ' . $id);
                }
            } else {
                log::add('JeeRemi', 'debug', 'Commande "' . $cmdName . '" non trouvée pour ' . $this->getLogicalId());
            }
        }

        // Mise à jour du nom de l'équipement uniquement si le nom actuel est "REMI ID"
        if (isset($info['name']) && strpos($this->getName(), 'REMI ' . $id) === 0) {
            $this->setName($info['name']);
            $this->save();
        }

        // Mise à jour de Remi_ID
        $remiIdCmd = $this->getCmd(null, 'Remi_ID');
        if (is_object($remiIdCmd)) {
            $remiIdCmd->event($id);
        }

        // Mise à jour de last_update
        $lastUpdateCmd = $this->getCmd(null, 'last_update');
        if (is_object($lastUpdateCmd)) {
            $lastUpdateCmd->event(date('d-m-Y H:i:s'));
        }
    }

}
?>
