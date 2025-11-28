<?php
if (!isConnect()) {
    throw new Exception(__('401 - Accès non autorisé', __FILE__));
}

$venv_python = realpath(__DIR__ . '/../resources/python_venv/bin/python3');
$dep_ok = false;
if ($venv_python && file_exists($venv_python) && is_executable($venv_python)) {
    $out = trim(shell_exec(escapeshellcmd($venv_python) . ' --version 2>&1'));
    if ($out !== '') {
        $dep_ok = true;
    }
}
if (!$dep_ok) {
    echo '<div class="alert alert-warning">
            Les dépendances ne sont pas encore installées.<br>
            Merci d\'aller sur l\'onglet <strong>Dépendances</strong> pour les installer.
          </div>';
    return;
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