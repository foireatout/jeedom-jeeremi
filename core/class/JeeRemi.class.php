<?php
require_once dirname(__FILE__).'/JeeRemiCmd.class.php';
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
			$eq->updateInfos();
        }
		
      	// After creation, ensure commands created and initial values present
        foreach (self::byType('JeeRemi') as $eqToUpdate) {
            try {
                $eqToUpdate->updateInfos();
            } catch (Exception $e) {
        log::add('JeeRemi','error','updateInfos failed for '.$eqToUpdate->getLogicalId().' : '.$e->getMessage());
    }
}

        log::add('JeeRemi','info','syncRemi terminé');
    }

    public function createCommands() {
    $cmds = [
        // logicalId, type, subType, humanName, options array
        ['set_veilleuse','action','slider','Régler luminosité', ['min'=>0,'max'=>100,'unit'=>'%']],
        ['veilleuse','info','numeric','Luminosité', []],
        ['set_volume','action','slider','Régler volume', ['min'=>0,'max'=>100,'unit'=>'%']],
        ['volume','info','numeric','Volume', []],
        ['temperature','info','numeric','Température', []],
        ['face','info','string','Face', []],
        ['Visage_num','info','numeric','Visage (num)', []],
        ['nom','info','string','Nom', []],
        ['online','info','binary','Online', []],
        ['alive','info','binary','Alive', []],
        ['IP','info','string','IP', []],
        ['RSSI','info','numeric','RSSI', []],
        ['MusicPath','info','string','MusicPath', []],
        ['MusicMode','info','string','MusicMode', []],
        ['last_update','info','string','Dernière mise à jour', []],
        ['awakeFace','action','other','Visage éveillé', []],
        ['sleepyFace','action','other','Visage endormi', []],
        ['semiAwakeFace','action','other','Visage semi-ouvert', []],
        ['blankFace','action','other','Visage blanc', []],
        ['play_music','action','message','Démarrer musique', ['template'=>'{{message}}']],
        ['stop_music','action','other','Arrêter musique', []]
    ];

    foreach ($cmds as $c) {
        list($logical,$type,$subtype,$name,$opts) = $c;
        if (!is_object($this->getCmd(null, $logical))) {
            $cmd = new JeeRemiCmd();
            $cmd->setName($name);
            $cmd->setEqLogic_id($this->getId());
            $cmd->setLogicalId($logical);
            $cmd->setType($type);
            $cmd->setSubType($subtype);
            // options
            if (isset($opts['min'])) $cmd->setConfiguration('minValue',$opts['min']);
            if (isset($opts['max'])) $cmd->setConfiguration('maxValue',$opts['max']);
            if (isset($opts['unit'])) $cmd->setUnite($opts['unit']);
            if ($subtype == 'message' && isset($opts['template'])) $cmd->setConfiguration('template', $opts['template']);
            $cmd->save();
            log::add('JeeRemi','info','Création commande '.$logical.' pour eq '.$this->getLogicalId());
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