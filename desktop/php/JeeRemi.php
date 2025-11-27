<?php
if (!isConnect()) { throw new Exception(__('401 - Accès non autorisé', __FILE__)); }
?>

<div class="row">
  <div class="col-sm-12">
    <legend><i class="fas fa-cog"></i> JeeRemi - Administration</legend>

    <div class="form-group">
      <div class="col-sm-12">
        <a class="btn btn-success" id="jr_test"><i class="fas fa-plug"></i> Tester la connexion</a>
        <a class="btn btn-info" id="jr_sync"><i class="fas fa-sync"></i> Synchroniser mes REMI</a>
        <a class="btn btn-default" id="jr_refresh"><i class="fas fa-redo"></i> Rafraîchir la liste</a>
      </div>
    </div>

    <div id="jr_result" style="margin-top:10px"></div>

    <div id="jr_table" style="margin-top:20px"></div>

  </div>
</div>

<script>
jQuery(function($){
  function setResult(html){ $('#jr_result').html(html); }

  $('#jr_test').on('click', function(){
    $.ajax({
      type:'POST',
      url:'plugins/JeeRemi/core/ajax/JeeRemi.ajax.php',
      data:{ action:'testLogin', PHPSESSID: '<?php echo session_id(); ?>', username: $('.configKey[data-l1key=username]').val(), password: $('.configKey[data-l1key=password]').val() },
      dataType:'json',
      success:function(){ setResult('<div class="alert alert-success">Connexion OK</div>'); },
      error:function(xhr){ setResult('<div class="alert alert-danger">Erreur: '+xhr.responseText+'</div>'); }
    });
  });

  $('#jr_sync').on('click', function(){
    setResult('<i>Synchronisation en cours...</i>');
    $.ajax({
      type:'POST',
      url:'plugins/JeeRemi/core/ajax/JeeRemi.ajax.php',
      data:{ action:'syncRemi', PHPSESSID: '<?php echo session_id(); ?>' },
      dataType:'json',
      success:function(){ setResult('<div class="alert alert-info">Synchronisation terminée</div>'); loadTable(); },
      error:function(xhr){ setResult('<div class="alert alert-danger">Erreur synchro: '+xhr.responseText+'</div>'); }
    });
  });

  function loadTable(){
    $('#jr_table').html('<i>Chargement...</i>');
    $.ajax({
      type:'POST',
      url:'core/php/api/jeeAjax.php', // not used; fallback to plugin ajax via below
      error:function(){}, // ignore
      complete:function(){ // load via plugin ajax
        $.ajax({
          type:'POST',
          url:'plugins/JeeRemi/core/ajax/JeeRemi.ajax.php',
          data:{ action:'listRemi', PHPSESSID: '<?php echo session_id(); ?>' },
          dataType:'json',
          success:function(res){
            var html = '<table class="table table-condensed"><thead><tr><th>Nom</th><th>ID</th><th>Action</th></tr></thead><tbody>';
            if (!res || !res.remis || res.remis.length==0) {
              html += '<tr><td colspan="3"><em>Aucun REMI détecté</em></td></tr>';
            } else {
              res.remis.forEach(function(r){
                var id = (typeof r === 'string') ? r : (r.objectId? r.objectId : '');
                var name = (typeof r === 'object' && r.name) ? r.name : 'REMI';
                html += '<tr><td>'+name+'</td><td>'+id+'</td><td><a class="btn btn-xs btn-primary" href="index.php?v=d&p=eqLogic&m=JeeRemi&id='+id+'">Ouvrir</a></td></tr>';
              });
            }
            html += '</tbody></table>';
            $('#jr_table').html(html);
          },
          error:function(xhr){ $('#jr_table').html('<div class="alert alert-danger">Erreur: '+xhr.responseText+'</div>'); }
        });
      }
    });
  }

  $('#jr_refresh').on('click', loadTable);
  loadTable();
});
</script>