jQuery(document).ready(function($) {
    $('#billing_nif').blur(function() {
        var abs_url = wc_cart_fragments_params.ajax_url
        var lgt = $(this).val().length
        var number = $(this).val()

        $(this).nextAll().remove();
        var type = '';

        if (lgt == 8) {
            type = 'dni';
            // $(this).after('<span style="color:red; font-size:.8rem;">' + $(this).val() + '</span>');
        } else if (lgt == 11) {
            type = 'ruc';
            // $(this).after('<span style="color:red; font-size:.8rem;">' + $(this).val() + '' + lgt +'</span>');
        } else {
            $(this).after('<span style="color:red; font-size:.8rem;">Debe contener 8 digitos para DNI / 11 digitos para RUC</span>');
            return
        }

        $.ajax({
            type: "POST",
            url: abs_url,
            data: {
                'action': 'api_service_rucdni',
                'type': type,
                'number': number
            },
            success: function(data){
                var obj = JSON.parse(data);
                if (obj.success == false) {
                    $('#billing_nif').after('<span style="color:red; font-size:.8rem;">' + obj.message + '</span>');
                    return
                } else if (obj.success == true) {

                    if (obj.data.hasOwnProperty('address')) {
                        var ruc = obj.data;
                        $('#billing_first_name').val('');
                        $('#billing_last_name').val('');
                        $('#billing_company').val(ruc.name);
                        $('#billing_postcode').val(ruc.district_id);
                        $('#billing_city').val(ruc.department);
                        $("#billing_state option:contains(" + ruc.province + ")").attr('selected', 'selected');
                        $('#billing_address_1').val(ruc.address);
                        $('#billing_phone').val(ruc.phone);

                    } else {
                        var dni = obj.data;

                        $('#billing_first_name').val(dni.names);
                        $('#billing_last_name').val(dni.first_name+' '+dni.last_name);
                        $('#billing_company').val('');
                        $('#billing_postcode').val('');
                        $('#billing_city').val('');
                        $('#billing_address_1').val('');
                        $('#billing_phone').val('');
                    }
                }

            }
        });

    });

});
