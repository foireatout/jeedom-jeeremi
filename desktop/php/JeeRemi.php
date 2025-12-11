<?php
if (!isConnect('admin')) {
  throw new Exception('{{401 - Accès non autorisé}}');
}
sendVarToJS('eqType', 'JeeRemi');
$eqLogics = eqLogic::byType('JeeRemi');
?>

<div class="row row-overflow">

  <!-- Colonne gauche masquée (liste compacte) -->
  <div class="col-lg-2 col-sm-3 col-xs-12" id="hidCol" style="display:none;">
    <div class="bs-sidebar">
      <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
        <li class="filter" style="margin-bottom: 5px;">
          <input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/>
        </li>
        <?php
        foreach ($eqLogics as $eqLogic) {
          echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '"><a>' . $eqLogic->getHumanName(true) . '</a></li>';
        }
        ?>
      </ul>
    </div>
  </div>

  <!-- Zone principale : vignettes -->
  <div class="col-lg-12 eqLogicThumbnailDisplay" id="listCol">

    <legend><i class="fas fa-cog"></i> {{Gestion}}</legend>

    <div class="eqLogicThumbnailContainer">

      <!-- Bouton Configuration plugin -->
      <div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
        <i class="fas fa-wrench"></i><br/>
        <span>{{Configuration}}</span>
      </div>

    </div>

    <input class="form-control" placeholder="{{Rechercher}}" id="in_searchEqlogic" style="margin:10px 0;"/>

    <legend><i class="fas fa-home" id="butCol"></i> {{Mes Equipements}}</legend>

    <div class="eqLogicThumbnailContainer">
      <?php
      $validColors = ['blue', 'pink', 'yellow', 'grey'];

      foreach ($eqLogics as $eqLogic) {

        $opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';

        // Récupération background_color
        $bgColorCmd = $eqLogic->getCmd(null, 'background_color');
        $bgColorValue = 'blue';
        if (is_object($bgColorCmd)) {
          try {
            $cmdValue = trim($bgColorCmd->execCmd());
            if (in_array($cmdValue, $validColors)) {
              $bgColorValue = $cmdValue;
            }
          } catch (Exception $e) {}
        }

        $icon = 'plugins/JeeRemi/plugin_info/img/' . $bgColorValue . '.png';

        echo '<div class="eqLogicDisplayCard cursor ' . $opacity . '" data-eqLogic_id="' . $eqLogic->getId() . '" style="background-color:#fff;height:200px;margin-bottom:10px;padding:5px;border-radius:3px;width:160px;margin-left:10px;">';
        echo '<center>';
        echo '<img src="' . $icon . '" style="height:105px;width:95px;"/>';
        echo '</center>';
        echo '<span style="font-size:1.1em;position:relative;top:10px;word-break:break-word;"><center>' . $eqLogic->getHumanName(true,true) . '</center></span>';
        echo '</div>';
      }
      ?>
    </div>
  </div>

  <!-- Panneau détaillé d'un équipement -->
  <div class="col-lg-10 col-md-9 col-sm-8 eqLogic" style="border-left:1px solid #EEE;padding-left:25px;display:none;">

    <a class="btn btn-success eqLogicAction pull-right" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a>
    <a class="btn btn-danger eqLogicAction pull-right" data-action="remove" style="margin-right:5px;"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
    <a class="btn btn-default eqLogicAction pull-right" data-action="configure" style="margin-right:5px;"><i class="fas fa-cogs"></i> {{Configuration avancée}}</a>

    <ul class="nav nav-tabs" role="tablist">
      <li role="presentation"><a href="#" class="eqLogicAction" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
      <li role="presentation" class="active"><a href="#eqlogictab" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
      <li role="presentation"><a href="#commandtab" role="tab" data-toggle="tab"><i class="fas fa-list"></i> {{Commandes}}</a></li>
    </ul>

    <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x:hidden;">

      <!-- Onglet Equipement -->
      <div role="tabpanel" class="tab-pane active" id="eqlogictab">
        <form class="form-horizontal">
          <fieldset>
            <div class="form-group">
              <label class="col-sm-3 control-label">{{Nom}}</label>
              <div class="col-sm-3">
                <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display:none;"/>
                <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}"/>
              </div>
            </div>

            <div class="form-group">
              <label class="col-sm-3 control-label">{{Objet parent}}</label>
              <div class="col-sm-3">
                <select class="form-control eqLogicAttr" data-l1key="object_id">
                  <option value="">{{Aucun}}</option>
                  <?php
                  foreach (jeeObject::all() as $obj) {
                    echo '<option value="'.$obj->getId().'">'.$obj->getName().'</option>';
                  }
                  ?>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label class="col-sm-3 control-label">{{Catégorie}}</label>
              <div class="col-sm-8">
                <?php
                foreach (jeedom::getConfiguration('eqLogic:category') as $k => $v) {
                  echo '<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="'.$k.'"> '.$v['name'].'</label>';
                }
                ?>
              </div>
            </div>

            <div class="form-group">
              <label class="col-sm-3 control-label">{{Options}}</label>
              <div class="col-sm-8">
                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable"> {{Activer}}</label>
                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible"> {{Visible}}</label>
              </div>
            </div>

          </fieldset>
        </form>
      </div>

      <!-- Onglet Commandes -->
      <div role="tabpanel" class="tab-pane" id="commandtab">
        <br/>
        <table id="table_cmd" class="table table-bordered table-condensed">
          <thead>
            <tr>
              <th class="hidden-xs">ID</th>
              <th>{{Nom}}</th>
              <th>{{Type}}</th>
              <th>{{Logical ID}}</th>
              <th>{{Options}}</th>
              <th>{{Valeur}}</th>
              <th>{{Action}}</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

    </div>
  </div>
</div>

<?php include_file('desktop', 'JeeRemi', 'js', 'JeeRemi'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>