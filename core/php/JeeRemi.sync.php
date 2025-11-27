<?php
// Called from Jeedom admin -> depends on rights etc.
require_once dirname(__FILE__).'/../../core/class/JeeRemi.class.php';
JeeRemi::syncRemi();
echo 'OK';

