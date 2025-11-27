<?php
if (!isConnect()) {
    throw new Exception(__('401 - Accès non autorisé', __FILE__));
}
?>

<div class="row">
  <div class="col-sm-6">
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
        <a class="btn btn-success" id="JeeRemiTestLogin"><i class="fas fa-plug"></i> Tester la connexion</a>
        <a class="btn btn-info" id="JeeRemiSync"><i class="fas fa-sync"></i> Synchroniser mes REMI</a>
      </div>
    </div>

    <div id="JeeRemiConfigResult"></div>
  </div>

  <div class="col-sm-6">
    <legend><i class="fas fa-list"></i> REMI détectés (aperçu)</legend>
    <div id="JeeRemiDetectedContainer">
      <button class="btn btn-default" id="JeeRemiRefreshDetected">Rafraîchir la liste</button>
      <div id="JeeRemiDetectedTable" style="margin-top:10px"></div>
    </div>
  </div>
</div>

<script>
jQuery(function($){

  function showResult(html){ $('#JeeRemiConfigResult').html(html); }

  // Tester la connexion
  $('#JeeRemiTestLogin').on('click', function(){
    var username = $('.configKey[data-l1key=username]').val();
    var password = $('.configKey[data-l1key=password]').val();
    $.ajax({
      type: 'POST',
      url: 'plugins/JeeRemi/core/ajax/JeeRemi.ajax.php',
      data: { action: 'testLogin', PHPSESSID: '<?php echo session_id(); ?>', username: username, password: password },
      dataType: 'json',
      success: function(res){ showResult('<div class="alert alert-success">Connexion OK</div>'); },
      error: function(xhr){ showResult('<div class="alert alert-danger">Erreur AJAX: '+xhr.responseText+'</div>'); console.log(xhr.responseText); }
    });
  });

  // Synchroniser
  $('#JeeRemiSync').on('click', function(){
    $.ajax({
      type: 'POST',
      url: 'plugins/JeeRemi/core/ajax/JeeRemi.ajax.php',
      data: { action: 'syncRemi', PHPSESSID: '<?php echo session_id(); ?>' },
      dataType: 'json',
      success: function(res){ showResult('<div class="alert alert-info">Synchronisation terminée. Vérifiez les équipements.</div>'); },
      error: function(xhr){ showResult('<div class="alert alert-danger">Erreur synchro: '+xhr.responseText+'</div>'); console.log(xhr.responseText); }
    });
  });

  // Detected Remi list (from API)
  function loadDetected() {
    $('#JeeRemiDetectedTable').html('<i>Chargement...</i>');
    $.ajax({
      type:'POST',
      url:'plugins/JeeRemi/core/ajax/JeeRemi.ajax.php',
      data: { action:'listRemi', PHPSESSID: '<?php echo session_id(); ?>' },
      dataType:'json',
      success:function(res){
        if (!res || !res.remis) { $('#JeeRemiDetectedTable').html('<div class="alert alert-warning">Aucun REMI détecté ou erreur.</div>'); return; }
        var html = '<table class="table table-condensed"><thead><tr><th>Nom</th><th>ID</th></tr></thead><tbody>';
        res.remis.forEach(function(r){
          html += '<tr><td>'+ (r.name ? r.name : 'REMI') +'</td><td>'+r.objectId+'</td></tr>';
        });
        html += '</tbody></table>';
        $('#JeeRemiDetectedTable').html(html);
      },
      error:function(xhr){ $('#JeeRemiDetectedTable').html('<div class="alert alert-danger">Erreur: '+xhr.responseText+'</div>'); console.log(xhr.responseText); }
    });
  }

  // initial load
  $('#JeeRemiRefreshDetected').on('click', loadDetected);
  loadDetected();

});
</script>