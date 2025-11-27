<?php
if (!isConnect()) { throw new Exception(__('401 - Accès non autorisé', __FILE__)); }
$eqLogics = eqLogic::byType('JeeRemi');
?>

<div class="eqLogicThumbnailContainer">
<?php
foreach ($eqLogics as $eqLogic) {
    echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '">';
    echo '<img src="plugins/JeeRemi/plugin_info/icon.png" style="height:48px;"/>';
    echo '<br/>' . $eqLogic->getHumanName();
    echo '</div>';
}
?>
</div>

<?php include_file('desktop', 'JeeRemi', 'js', 'JeeRemi'); ?>