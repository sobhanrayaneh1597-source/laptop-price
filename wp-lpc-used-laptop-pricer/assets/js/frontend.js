(function($){
  function fillSelect($sel, items, placeholder){
    $sel.empty();
    if (placeholder) $sel.append($('<option>').val('').text(placeholder));
    items.forEach(function(it){
      if (typeof it === 'string') {
        $sel.append($('<option>').val(it).text(it));
      } else {
        $sel.append($('<option>').val(it.id).text(it.model));
      }
    });
  }

  var $brand, $model, $configs, $cond, $btn, $res;
  $(document).ready(function(){
    $brand = $('#lpcfa-brand');
    $model = $('#lpcfa-model');
    $configs = $('#lpcfa-configs');
    $cond = $('#lpcfa-condition');
    $btn = $('#lpcfa-calc');
    $res = $('#lpcfa-result');

    $brand.on('change', function(){
      var brand = $(this).val();
      $model.prop('disabled', true);
      fillSelect($model, [], LPCFA.i18n.loading);
      if (!brand) return;
      $.get(LPCFA.ajax_url, { action: 'lpc_get_models', brand: brand, nonce: LPCFA.nonce }, function(resp){
        if (resp.success) {
          $model.prop('disabled', false);
          fillSelect($model, resp.data.models, LPCFA.i18n.select_model);
        }
      });
    });

    $model.on('change', function(){
      var modelId = $(this).val();
      if (!modelId) return;
      $.get(LPCFA.ajax_url, { action: 'lpc_get_model_configs', model_id: modelId, nonce: LPCFA.nonce }, function(resp){
        if (resp.success) {
          var c = resp.data.components;
          fillSelect($('#lpcfa-cpu'), c.cpu || [], 'CPU');
          fillSelect($('#lpcfa-ram'), c.ram || [], 'RAM');
          fillSelect($('#lpcfa-gpu'), c.gpu || [], 'GPU');
          fillSelect($('#lpcfa-ssd'), c.ssd || [], 'SSD');
          fillSelect($('#lpcfa-hdd'), c.hdd || [], 'HDD');
          $cond.empty();
          var conditions = resp.data.conditions || {};
          Object.keys(conditions).forEach(function(name){
            $cond.append($('<option>').val(name).text(name));
          });
          $configs.show();
          $res.hide();
        }
      });
    });

    $btn.on('click', function(e){
      e.preventDefault();
      var payload = {
        action: 'lpc_calculate',
        nonce: LPCFA.nonce,
        model_id: $model.val(),
        cpu: $('#lpcfa-cpu').val(),
        ram: $('#lpcfa-ram').val(),
        gpu: $('#lpcfa-gpu').val(),
        ssd: $('#lpcfa-ssd').val(),
        hdd: $('#lpcfa-hdd').val(),
        condition: $('#lpcfa-condition').val()
      };
      $.post(LPCFA.ajax_url, payload, function(resp){
        if (resp.success) {
          $('#lpcfa-price').text(resp.data.price);
          $('#lpcfa-lower').text(resp.data.lower);
          $('#lpcfa-upper').text(resp.data.upper);
          $res.show();
        }
      });
    });
  });
})(jQuery);