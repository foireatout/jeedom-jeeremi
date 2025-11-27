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
            ajax::success(JeeRemiApi::login($username, $password));
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

        default:
            ajax::error("Action inconnue : $action");
    }

} catch (Exception $e) {
    ajax::error($e->getMessage());
}