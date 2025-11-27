<?php
require_once dirname(__FILE__).'/../api/urbanhello_api_wrapper.php';

class JeeRemi extends eqLogic {

    public static function syncRemi() {
        log::add('JeeRemi','info','Lancement syncRemi()');
        $username = config::byKey('username','JeeRemi','');
        $password = config::byKey('password','JeeRemi','');
        if ($username=='' || $password=='') {
            log::add('JeeRemi','error','Plugin non configuré');
            return;
        }

        $login = JeeRemiApi::login($username,$password);
        if (!is_array($login) || !isset($login['sessionToken'])) {
            log::add('JeeRemi','error','Login API échoué: '.json_encode($login));
            return;
        }
        $token = $login['sessionToken'];
        $userId = $login['objectId'];

        $userInfo = JeeRemiApi::userInfo($token,$userId);
        if (!is_array($userInfo) || !isset($userInfo['remis'])) {
            log::add('JeeRemi','error','Aucun remis trouvé');
            return;
        }

        foreach ($userInfo['remis'] as $remi) {
            $remiId = is_array($remi) && isset($remi['objectId']) ? $remi['objectId'] : $remi;
            $eq = eqLogic::byLogicalId($remiId, 'JeeRemi');
            if (!is_object($eq)) {
                $eq = new JeeRemi();
                $eq->setEqType_name('JeeRemi');
                $eq->setLogicalId($remiId);
                $eq->setName('REMI '.$remiId);
                $eq->setIsEnable(1);
                $eq->save();
                log::add('JeeRemi','info','Création équipement REMI '.$remiId);
            } else {
                log::add('JeeRemi','debug','Équipement REMI existant '.$remiId);
            }

            // create commands (idempotent)
            $eq->createCommands();
        }

        log::add('JeeRemi','info','syncRemi terminé');
    }

    public function createCommands() {
        // list: logicalId, type, subtype, humanName
        $cmds = [
            ['set_veilleuse','action','slider','Régler luminosité'],
            ['veilleuse','info','numeric','Luminosité (%)'],
            ['set_volume','action','slider','Régler volume'],
            ['volume','info','numeric','Volume (%)'],
            ['temperature','info','numeric','Température'],
            ['face','info','string','Face'],
            ['Visage_num','info','numeric','Visage (num)'],
            ['nom','info','string','Nom'],
            ['online','info','binary','Online'],
            ['alive','info','binary','Alive'],
            ['IP','info','string','IP'],
            ['RSSI','info','numeric','RSSI'],
            ['MusicPath','info','string','Music Path'],
            ['MusicMode','info','string','Music Mode'],
            ['last_update','info','string','Dernière mise à jour'],
            ['awakeFace','action','other','Visage éveillé'],
            ['sleepyFace','action','other','Visage endormi'],
            ['semiAwakeFace','action','other','Visage semi-ouvert'],
            ['blankFace','action','other','Visage blanc'],
            ['play_music','action','message','Démarrer musique'],
            ['stop_music','action','other','Arrêter musique']
        ];

        foreach ($cmds as $c) {
            $logical = $c[0];
            $type = $c[1];
            $subtype = $c[2];
            $name = $c[3];

            if (!is_object($this->getCmd(null, $logical))) {
                $cmd = new cmd();
                $cmd->setName($name);
                $cmd->setEqLogic_id($this->getId());
                $cmd->setLogicalId($logical);
                $cmd->setType($type);
                $cmd->setSubType($subtype);
                if ($subtype == 'slider') {
                    $cmd->setConfiguration('minValue',0);
                    $cmd->setConfiguration('maxValue',100);
                    $cmd->setUnite('%');
                }
                if ($subtype == 'message') {
                    $cmd->setConfiguration('template','{{message}}');
                }
                $cmd->save();
            }
        }
    }

    public static function cron5() {
        foreach (self::byType('JeeRemi') as $eq) {
            $eq->updateInfos();
        }
    }

    public function updateInfos() {
        $username = config::byKey('username','JeeRemi','');
        $password = config::byKey('password','JeeRemi','');
        if ($username=='' || $password=='') return;
        $login = JeeRemiApi::login($username,$password);
        if (!is_array($login) || !isset($login['sessionToken'])) return;
        $token = $login['sessionToken'];
        $id = $this->getLogicalId();
        $info = JeeRemiApi::remiInfo($token, $id);
        if (!is_array($info)) {
            log::add('JeeRemi','error','remiInfo non array for '.$id);
            return;
        }

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
            'musicMode' => 'MusicMode'
        ];

        foreach ($map as $k => $cmdName) {
            if (isset($info[$k])) {
                $cmd = $this->getCmd(null, $cmdName);
                if (is_object($cmd)) {
                    $val = $info[$k];
                    // face -> humanize
                    if ($k === 'face') {
                        if (is_array($val) && isset($val['objectId'])) $val = $val['objectId'];
                        elseif (is_array($val) && isset($val['expression'])) $val = $val['expression'];
                        else $val = json_encode($val);
                    }
                    $cmd->event($val);
                }
            }
        }
    }
}