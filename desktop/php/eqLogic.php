<?php
if (!isConnect()) { throw new Exception('401 - Accès non autorisé'); }
$eqLogics = eqLogic::byType('JeeRemi');
?>

<div class="eqLogicThumbnailContainer">
<?php
if (count($eqLogics) == 0) {
    echo '<div class="alert alert-info">Aucun REMI détecté</div>';
} else {
    foreach ($eqLogics as $eqLogic) {
        echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '">';
        echo '<img src="plugins/JeeRemi/plugin_info/icon.png" style="height:48px;"/>';
        echo '<br/>' . $eqLogic->getHumanName();
        echo '</div>';
    }
}
?>
</div>

<?php include_file('desktop','JeeRemi','js','JeeRemi'); ?>