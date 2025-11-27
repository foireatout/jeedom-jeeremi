<?php
require_once dirname(__FILE__).'/../api/urbanhello_api_wrapper.php';

class JeeRemiCmd extends cmd {

    public function execute($_options = array()) {

        $eq = $this->getEqLogic();
        if (!is_object($eq)) throw new Exception('Equipement introuvable');

        $remiId = $eq->getLogicalId();

        // get token
        $username = config::byKey('username','JeeRemi','');
        $password = config::byKey('password','JeeRemi','');
        $login = JeeRemiApi::login($username,$password);
        if (!is_array($login) || !isset($login['sessionToken'])) {
            throw new Exception('Impossible de récupérer token API');
        }
        $token = $login['sessionToken'];

        $logical = $this->getLogicalId();

        switch ($logical) {
            case 'set_veilleuse':
                $value = intval($_options['slider']);
                return JeeRemiApi::setLuminosity($token,$remiId,$value);

            case 'set_volume':
                $value = intval($_options['slider']);
                return JeeRemiApi::setVolume($token,$remiId,$value);

            case 'awakeFace':
            case 'sleepyFace':
            case 'semiAwakeFace':
            case 'blankFace':
                return JeeRemiApi::setFace($token,$remiId,$logical);

            case 'play_music':
                $msg = '';
                if (is_array($_options) && isset($_options['message'])) $msg = $_options['message'];
                return JeeRemiApi::playMusic($token,$remiId,$msg);

            case 'stop_music':
                return JeeRemiApi::stopMusic($token,$remiId);

            default:
                throw new Exception('Commande non gérée: '.$logical);
        }
    }
}