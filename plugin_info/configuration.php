<?php
//require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');

if (!isConnect('admin')) {
        throw new Exception('{{401 - Accès non autorisé}}');
}

?>

<div class="row">
  <div class="col-sm-12 text-center">
    <legend><i class="fas fa-user-circle"></i> Compte UrbanHello</legend>
    <div class="form-group">
      <label class="col-sm-4 control-label">Login (email)</label>
      <div class="col-sm-8">
        <input class="configKey form-control" data-l1key="username" placeholder="email@domain.tld"/>
      </div>
    </div>
    <div class="form-group">
      <label class="col-sm-4 control-label">Mot de passe</label>
      <div class="col-sm-8">
        <input type="password" class="configKey form-control" data-l1key="password" placeholder="••••••"/>
      </div>
    </div>
    <div class="form-group">
      <label class="col-sm-4 control-label"></label>
      <div class="col-sm-8">
        <a class="btn btn-info" id="JeeRemiSync"><i class="fas fa-sync"></i> Synchroniser mes REMI</a>
      </div>
    </div>
    <div id="JeeRemiConfigResult"></div>
  </div>
</div>

<script>
jQuery(function($) {
  function showResult(html) {
    $('#JeeRemiConfigResult').html(html);
  }

  $('#JeeRemiSync').on('click', function() {
    $.ajax({
      type: 'POST',
      url: 'plugins/JeeRemi/core/ajax/JeeRemi.ajax.php',
      data: { action: 'syncRemi', PHPSESSID: '<?php echo session_id(); ?>' },
      dataType: 'json',
      success: function(res) {
        showResult('<div class="alert alert-info">Synchronisation terminée. Vérifiez les équipements.</div>');
      },
      error: function(xhr) {
        showResult('<div class="alert alert-danger">Erreur synchro: ' + xhr.responseText + '</div>');
      }
    });
  });
});
</script>
