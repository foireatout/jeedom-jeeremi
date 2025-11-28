<?php
try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

    // Re-open session when PHPSESSID provided (fix Jeedom 4.3/4.4 edge cases)
    if (isset($_REQUEST['PHPSESSID'])) {
        session_write_close();
        session_id($_REQUEST['PHPSESSID']);
        session_start();
    }

    if (!isConnect()) {
        throw new Exception('401 - Accès non autorisé');
    }

    require_once dirname(__FILE__) . '/../class/JeeRemi.class.php';
    require_once dirname(__FILE__) . '/../api/urbanhello_api_wrapper.php';

    $action = init('action');

    switch ($action) {
        case 'testLogin':
            $username = init('username');
            $password = init('password');
            $res = JeeRemiApi::login($username, $password);
            if ($res === false) {
                ajax::error('Erreur appel API (voir logs).');
            }
            if (!is_array($res) || !isset($res['sessionToken'])) {
                // transmettre l'erreur lisible si possible
                $msg = is_string($res) ? $res : json_encode($res);
                ajax::error('Login API échoué : ' . $msg);
            }
            ajax::success(array('status' => 'ok', 'user' => $res));
            break;


        case 'listRemi':
            // returns userInfo with remis array
            $username = config::byKey('username','JeeRemi','');
            $password = config::byKey('password','JeeRemi','');
            if ($username=='' || $password=='') { ajax::error('Plugin non configuré'); }
            $login = JeeRemiApi::login($username,$password);
            if (!is_array($login) || !isset($login['sessionToken'])) { ajax::error('Login API échoué'); }
            $token = $login['sessionToken'];
            $userId = $login['objectId'];
            $userInfo = JeeRemiApi::userInfo($token,$userId);
            if (!is_array($userInfo)) ajax::error('Impossible de récupérer userInfo');
            ajax::success(array('remis' => isset($userInfo['remis']) ? $userInfo['remis'] : array(), 'user' => $userInfo));
            break;

        case 'syncRemi':
            log::add('JeeRemi','info','Ajax syncRemi requested');
            JeeRemi::syncRemi();
            ajax::success('OK');
            break;


        case 'remiInfo':
            $remiId = init('remiId');
            $username = config::byKey('username','JeeRemi','');
            $password = config::byKey('password','JeeRemi','');
            if ($username=='' || $password=='') { ajax::error('Plugin non configuré'); }
            $login = JeeRemiApi::login($username,$password);
            if (!is_array($login) || !isset($login['sessionToken'])) { ajax::error('Login API échoué'); }
            $token = $login['sessionToken'];
            $info = JeeRemiApi::remiInfo($token, $remiId);
            ajax::success($info);
            break;
        
        
        case 'dependancy_install':
            log::add('JeeRemi', 'info', 'Installation des dépendances lancée');

            // on demande à Jeedom de lancer les dépendances
            jeedom::setProgressBar(0);

            // lance réellement l’installation
            $cmd = 'sudo /bin/bash ' . dirname(__FILE__) . '/../../resources/install_apt.sh';
            $return = system($cmd . ' >> ' . log::getPathToLog('JeeRemi_dep') . ' 2>&1');

            plugin::byId('JeeRemi')->setState('dependencies', 'ok');
            plugin::byId('JeeRemi')->save();

            ajax::success();
            break;

        default:
            ajax::error("Action inconnue : $action");
    }

} catch (Exception $e) {
    ajax::error($e->getMessage());
}