<?php
// agrego el js a la pagina
function add_scripts_checkout() {
    wp_enqueue_script(
        'checkout-script',
        plugin_dir_url( __FILE__ ) . '/js/checkout.js',
        array( 'jquery' )
    );

    wp_localize_script('my-script', 'myScript', array(
        'pluginsUrl' => plugins_url(),
    ));
}
add_action( 'woocommerce_after_checkout_form', 'add_scripts_checkout');


// permiso de uso para usuarios registrado y no registrados
add_action( 'wp_ajax_api_service_rucdni', 'api_service_rucdni' );
add_action( 'wp_ajax_nopriv_api_service_rucdni', 'api_service_rucdni' );

// funcion llamada desde el js
function api_service_rucdni() {
    $type = $_POST['type'];
    $number = intval( $_POST['number'] );

    $api_url = get_option('facturaloperu_api_config_url');
    $api_token = get_option('facturaloperu_api_config_token');

    $service_url = 'http://'.parse_url($api_url, PHP_URL_HOST).'/api/services/'.$type.'/'.$number;

    // ENVIO A LA API
    $response = wp_remote_get( $service_url, array(
        'timeout' => 15,
        'httpversion' => '1.0',
        'sslverify' => false,
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.$api_token
        )
    ));

    if ( is_wp_error( $response ) ) {
        $error_message = $response->get_error_message();
        echo "Something went wrong: $error_message";
    } else {
        $body = wp_remote_retrieve_body( $response );
        echo $body;
    }

    wp_die();
}
