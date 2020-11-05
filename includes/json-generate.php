<?php

// JSON ENVIADO A LA SUNAT
add_action( 'add_meta_boxes', 'erplugin_response_add_meta_boxes' );
if ( ! function_exists( 'erplugin_response_add_meta_boxes' ) )
{
    function erplugin_response_add_meta_boxes()
    {
        add_meta_box( 'erplugin_response_json', 'JSON Generado', 'erplugin_response_json_api', 'shop_order', 'side', 'core' );
    }
}
if ( ! function_exists( 'erplugin_response_json_api' ) )
{
    function erplugin_response_json_api()
    {
        global $post;

        $order = wc_get_order( $post->ID );

        // DATA CUSTOMER
        $customer_first_name = get_post_meta( $post->ID, '_billing_first_name', true );
        $customer_last_name  = get_post_meta( $post->ID, '_billing_last_name', true );
        $customer_email      = get_post_meta( $post->ID, '_billing_email', true );
        $customer_company    = get_post_meta( $post->ID, '_billing_company', true );
        $customer_address    = get_post_meta( $post->ID, '_billing_address_1', true );
        $customer_city       = get_post_meta( $post->ID, '_billing_city', true );
        $customer_state      = get_post_meta( $post->ID, '_billing_state', true );
        $customer_postcode   = get_post_meta( $post->ID, '_billing_postcode', true );
        $customer_ruc        = get_post_meta( $post->ID, '_billing_nif', true );
        $api_document_type   = get_post_meta( $post->ID, 'api_document_type', true );

        $document_type = (strlen($customer_ruc) == 8) ? '1' : '6';

        if ('factura' == $api_document_type) {
            $serie = 'F001';
            $doc_type = '01';
        } else {
            $serie = 'B001';
            $doc_type = '03';
        }

        $full_name = $customer_first_name.' '.$customer_last_name;

        $items = [];
        foreach ($order->get_items() as $item_id => $item_data) {
            // Get an instance of corresponding the WC_Product object
            $product = $item_data->get_product();
            $product_name = $product->get_name(); // Get the product name
            $item_quantity = $item_data->get_quantity(); // Get the item quantity
            $item_total = $item_data->get_total(); // Get the item line total
            $t = $order->get_line_tax($item_data) + $order->get_line_subtotal($item_data);

            $item = [
                "codigo_interno" => "",
                "descripcion" => $product_name,
                "unidad_de_medida" => "NIU",
                "cantidad" => $item_quantity,
                "valor_unitario" => $product->price,
                "codigo_tipo_precio" => "01",
                "precio_unitario" => round($product->price*1.18, 2),
                "codigo_tipo_afectacion_igv" => "10",
                "total_base_igv" => $order->get_line_subtotal($item_data),
                "porcentaje_igv" => 18,
                "total_igv" => $order->get_line_tax($item_data),
                "total_impuesto" => $order->get_line_tax($item_data),
                "total_valor_item" => $order->get_line_subtotal($item_data),
                "total_item" => $t,
            ];

            $items[] = array_merge($item);
        }

        $array = array(
            "serie_documento" => $serie,
            "numero_documento" => "#",
            "fecha_de_emision" => date("Y-m-d", strtotime($order->get_date_created())),
            "hora_de_emision" => date("h:i:s", strtotime($order->get_date_created())),
            "codigo_tipo_documento" => $doc_type,
            "codigo_tipo_operacion" => '0101',
            "codigo_tipo_moneda" => $order->currency,
            "fecha_de_vencimiento" => date("Y-m-d", strtotime($order->get_date_created()."+ 1 month")),
            "numero_orden_de_compra" => "",
            "datos_del_emisor" => array(
                "codigo_del_domicilio_fiscal" => "0000",
            ),
            "datos_del_cliente_o_receptor" => array(
                "codigo_tipo_documento_identidad" => $document_type,
                "numero_documento" => $customer_ruc,
                "apellidos_y_nombres_o_razon_social" => $customer_company != '' ? $customer_company : $full_name,
                "codigo_pais" => "PE",
                "ubigeo" => $customer_postcode != '' ? $customer_postcode : 150101,
                "direccion" => $customer_address,
                "correo_electronico" => $customer_email != '' ? $customer_email : '',
                "telefono" => ""
            ),
            "totales" => array(
                "total_operaciones_gravadas" =>  $order->total - $order->total_tax,
                "total_operaciones_inafectas" => 0.00,
                "total_operaciones_exoneradas" => 0.00,
                "total_igv" => $order->total_tax,
                "total_impuestos" => $order->total_tax,
                "total_valor" => $order->total - $order->total_tax,
                "total_venta" => $order->total,
            ),
            "items" => $items,
            "extras" => array(
                "forma_de_pago" => $order->payment_method_title,
                "observaciones" => "probando"
            ),
        );

        $erjsonencode = json_encode($array, JSON_PRETTY_PRINT);


        global $post;
        $order = wc_get_order( $post->ID );
        $order_status = $order->get_status();

        if ('auto-draft' != $order_status) {
            echo '<textarea style="width:100%;min-height:250px;">'.$erjsonencode.'</textarea>';
        } else {
            echo 'Se generará al guardar';
        }

    };

}

