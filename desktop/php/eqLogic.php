<?php
if (!isConnect()) {
    throw new Exception('401 - Accès non autorisé');
}
$eqLogics = eqLogic::byType('JeeRemi');
?>
<div class="eqLogicThumbnailContainer">
    <?php
    if (count($eqLogics) == 0) {
        echo '<div class="alert alert-info">Aucun REMI détecté</div>';
    } else {
        foreach ($eqLogics as $eqLogic) {
            $opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
            $bgColorCmd = $eqLogic->getCmd(null, 'background_color');
            $validColors = ['blue', 'pink', 'yellow', 'grey'];
            $bgColorValue = 'blue';
            if (is_object($bgColorCmd)) {
                $cmdValue = $bgColorCmd->execCmd();
                if (in_array($cmdValue, $validColors)) {
                    $bgColorValue = $cmdValue;
                }
            }
            $iconPath = 'plugins/JeeRemi/plugin_info/img/' . $bgColorValue . '.png';
            echo '<div class="eqLogicDisplayCard cursor ' . $opacity . '" data-eqLogic_id="' . $eqLogic->getId() . '">';
            echo '<img src="' . $iconPath . '" style="height:48px;"/>';
            echo '<br/>' . $eqLogic->getHumanName(true);
            echo '</div>';
        }
    }
    ?>
</div>
<?php
include_file('desktop', 'JeeRemi', 'js', 'JeeRemi');
?>