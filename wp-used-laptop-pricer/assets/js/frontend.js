(function($){
  function fillBrands() {
    $.post(ULPAjax.url, { action: 'ulp_get_brands', nonce: ULPAjax.nonce }, function(res){
      if (res && res.success) {
        var $brand = $('#ulp-brand');
        $brand.empty();
        $brand.append('<option value="">' + 'انتخاب برند' + '</option>');
        (res.data.brands || []).forEach(function(b){
          $brand.append('<option value="'+ b +'">'+ b +'</option>');
        });
      }
    });
  }

  function fillModels(brand) {
    $.post(ULPAjax.url, { action: 'ulp_get_models', nonce: ULPAjax.nonce, brand: brand }, function(res){
      var $model = $('#ulp-model');
      $model.empty();
      if (res && res.success) {
        $model.append('<option value="">' + 'انتخاب مدل' + '</option>');
        (res.data.models || []).forEach(function(m){
          $model.append('<option value="'+ m.id +'">'+ m.model +'</option>');
        });
      }
    });
  }

  $(document).on('change', '#ulp-brand', function(){
    var brand = $(this).val();
    if (brand) fillModels(brand);
  });

  $('#ulp-form').on('submit', function(e){
    e.preventDefault();
    var payload = {
      action: 'ulp_calculate_price',
      nonce: ULPAjax.nonce,
      model_id: $('#ulp-model').val(),
      condition: $('#ulp-condition').val(),
      cpu: $('#ulp-cpu').val(),
      ram_gb: $('#ulp-ram').val(),
      gpu: $('#ulp-gpu').val(),
      storage_type: $('#ulp-storage-type').val(),
      storage_size: $('#ulp-storage-size').val()
    };
    $.post(ULPAjax.url, payload, function(res){
      if (res && res.success) {
        var d = res.data;
        $('#ulp-base').text(d.formatted.base_price);
        $('#ulp-depr').text(d.formatted.depreciation_amount);
        $('#ulp-after-depr').text(d.formatted.price_after_depreciation);
        $('#ulp-condition-mul').text(d.condition_multiplier);
        $('#ulp-components').text(d.formatted.components_delta);
        $('#ulp-final').text(d.formatted.final_price);
        $('#ulp-range').text(d.formatted.lower_bound + ' - ' + d.formatted.upper_bound);
        $('#ulp-result').show();
      } else {
        alert((res && res.data && res.data.message) ? res.data.message : 'خطا در محاسبه');
      }
    });
  });

  $(document).ready(function(){
    fillBrands();
  });
})(jQuery);