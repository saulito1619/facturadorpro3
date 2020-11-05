<?php

// add fields
add_action( 'woocommerce_after_checkout_billing_form', 'misha_select_field' );

// save fields to order meta
add_action( 'woocommerce_checkout_update_order_meta', 'misha_save_what_we_added' );

// select
function misha_select_field( $checkout ){

    woocommerce_form_field( 'api_document_type', array(
        'type'          => 'select',
        'required'  => true, // actually this parameter just adds "*" to the field
        'class'         => array('misha-field', 'form-row-wide'),
        'label'         => 'Seleccione Tipo de Comprobante',
        'label_class'   => 'misha-label',
        'options'   => array(
            ''      => 'Please select',
            'factura'  => 'Factura',
            'boleta'  => 'Boleta'
            )
    ), $checkout->get_value( 'api_document_type' ) );

}

// save field values
function misha_save_what_we_added( $order_id ){

    if( !empty( $_POST['api_document_type'] ) )
        update_post_meta( $order_id, 'api_document_type', sanitize_text_field( $_POST['api_document_type'] ) );

}

add_action('woocommerce_checkout_process', 'misha_check_if_selected');

function misha_check_if_selected() {

    // you can add any custom validations here
    if ( empty( $_POST['api_document_type'] ) )
        wc_add_notice( 'Por favor seleccione un tipo de comprobante', 'error' );

}