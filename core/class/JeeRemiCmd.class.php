<?php
require_once dirname(__FILE__) . '/../api/urbanhello_api_wrapper.php';

class JeeRemiCmd extends cmd {

    public function execute($_options = array()) {
        try {
            $eq = $this->getEqLogic();
            if (!is_object($eq)) {
                throw new Exception('Équipement introuvable');
            }

            $remiId = $eq->getLogicalId();
            $username = config::byKey('username', 'JeeRemi', '');
            $password = config::byKey('password', 'JeeRemi', '');
            $login = JeeRemiApi::login($username, $password);

            if (!is_array($login) || !isset($login['sessionToken'])) {
                throw new Exception('Impossible de récupérer le token API');
            }

            $token = $login['sessionToken'];
            $logical = $this->getLogicalId();

            switch ($logical) {
                case 'set_veilleuse':
                    $value = intval($_options['slider']);
                    $result = JeeRemiApi::setLuminosity($token, $remiId, $value);
                    $cmd = $eq->getCmd(null, 'veilleuse');
                    if (is_object($cmd)) {
                        $cmd->event($value);
                    }
                    return $result;

                case 'set_volume':
                    $value = intval($_options['slider']);
                    $result = JeeRemiApi::setVolume($token, $remiId, $value);
                    $cmd = $eq->getCmd(null, 'volume');
                    if (is_object($cmd)) {
                        $cmd->event($value);
                    }
                    return $result;

                case 'awakeFace':
                case 'sleepyFace':
                case 'semiAwakeFace':
                case 'blankFace':
                    $result = JeeRemiApi::setFace($token, $remiId, $logical);
                    $faceCmd = $eq->getCmd(null, 'face');
                    $visageNumCmd = $eq->getCmd(null, 'Visage_num');
                    if (is_object($faceCmd)) {
                        $faceCmd->event($logical);
                    }
                    if (is_object($visageNumCmd)) {
                        $faceMap = [
                            'awakeFace' => 1,
                            'sleepyFace' => 2,
                            'semiAwakeFace' => 3,
                            'blankFace' => 4
                        ];
                        $visageNumCmd->event($faceMap[$logical]);
                    }
                    return $result;

                case 'play_music':
                    $msg = '';
                    if (is_array($_options) && isset($_options['message'])) {
                        $msg = $_options['message'];
                    }
                    return JeeRemiApi::playMusic($token, $remiId, $msg);

                case 'stop_music':
                    return JeeRemiApi::stopMusic($token, $remiId);

                case 'refresh':
                    $eq->updateInfos();
                    return true;

                default:
                    throw new Exception('Commande non gérée: ' . $logical);
            }
        } catch (Exception $e) {
            log::add('JeeRemi', 'error', 'Erreur lors de l\'exécution de la commande: ' . $e->getMessage());
            throw $e;
        }
    }
}