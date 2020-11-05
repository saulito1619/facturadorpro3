<?php

// funcion que detecta cuando el estatus es completed
//function mysite_woocommerce_payment_complete( $order_id ) {
    //$order = wc_get_order(  $order_id );

    // The text for the note
    //$note = __("This is my note's text…");

    // Add the note
    //$order->add_order_note( $note );

    // Save the data
    //$order->save();
//}
//add_action( 'woocommerce_order_status_completed', 'mysite_woocommerce_payment_complete' );


add_action( 'add_meta_boxes', 'ep_add_meta_boxes' );
if ( ! function_exists( 'ep_add_meta_boxes' ) )
{
    function ep_add_meta_boxes()
    {
        add_meta_box( 'ep_sunat_fields', 'Factura Enviada', 'ep_add_sunat_fields', 'shop_order');
    }
}

// agrego contenido al box
if ( ! function_exists( 'ep_add_sunat_fields' ) )
{
    function ep_add_sunat_fields()
    {
        global $post;

        // CONSULTO EL DATO DEL CAMPO "SI SE HA ENVIADO LA FACTURA"
        $meta_field_data = get_post_meta( $post->ID, '_send_invoice', true ) ? get_post_meta( $post->ID, '_send_invoice', true ) : '';
        // CONSULTO EL DATO ALMACENADO UNA RESPUESTA DE LA API
        $meta_field_response = get_post_meta( $post->ID, '_ep_sunat_api_response', true ) ? get_post_meta( $post->ID, '_ep_sunat_api_response', true ) : '';
        // CONSULTO LOS DATOS DE LA ORDEN
        $pedido = get_post_meta( $post->ID, '', true );
        // print_r('<pre>'.json_encode($pedido, JSON_PRETTY_PRINT).'</pre>');
        $order = wc_get_order( $post->ID );
        // CONSULTO EL ESTATUS ACTUAL
        $order_status = $order->get_status();

        //VALIDO SI SE HA ENVIADO FACTURA
        $checked = '';
        if ('on' == $meta_field_data) {
            $checked = 'checked="checked"';
        }

        // -- si el status es completed
        if ('completed' == $order_status) {
            $checked = 'checked="checked"';
        }

        // SI YA SE HA ALMACENADO UNA RESPUESTA DE LA API
        if (true == $meta_field_response) {
            //CONSULTO CADA CAMPO QUE SE ALMACENA CUANDO SE GUARDA UNA RESPUESTA DE LA API
            $meta_field_api_number = get_post_meta( $post->ID, '_ep_sunat_api_number', true ) ? get_post_meta( $post->ID, '_ep_sunat_api_number', true ) : '';
            $meta_field_api_xml = get_post_meta( $post->ID, '_ep_sunat_api_xml', true ) ? get_post_meta( $post->ID, '_ep_sunat_api_xml', true ) : '';
            $meta_field_api_pdf = get_post_meta( $post->ID, '_ep_sunat_api_pdf', true ) ? get_post_meta( $post->ID, '_ep_sunat_api_pdf', true ) : '';
            $meta_field_api_cdr = get_post_meta( $post->ID, '_ep_sunat_api_cdr', true ) ? get_post_meta( $post->ID, '_ep_sunat_api_cdr', true ) : '';
            // MUESTRO LOS VALORES DE LA RESPUESTA DE LA API
            echo '
            <table style="margin-bottom: 20px;"><tbody>
                <tr>
                    <td><label style="">Numero de Factura Sunat</label></td>
                    <td>'.$meta_field_api_number.'</td>
                </tr>
                <tr>
                    <td><label style="">XML</label></td>
                    <td><a href="'.$meta_field_api_xml.'">'.$meta_field_api_xml.'</a></td>
                </tr>
                <tr>
                    <td><label style="">PDF</label></td>
                    <td><a href="'.$meta_field_api_pdf.'">'.$meta_field_api_pdf.'</a></td>
                </tr>
                <tr>
                    <td><label style="">CDR</label></td>
                    <td><a href="'.$meta_field_api_cdr.'">'.$meta_field_api_cdr.'</a></td>
                </tr>
            </tbody></table>';
        } else {

            // SI LA CASILLA ESTA ACTIVA
            if ('' != $checked) {
                // CARGO EL ARRAY CON LOS DATOS DE LA ORDEN (JSON VALORES ESTATICOS)

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

                // CONCATENO EL NOMBRE
                $full_name = $customer_first_name.' '.$customer_last_name;

                // RECORRO LOS ITEMS
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
                        "codigo_producto_sunat" => "",
                        "unidad_de_medida" => "NIU",
                        "cantidad" => $item_quantity,
                        "valor_unitario" => $product->price,
                        "codigo_tipo_precio" => "01",
                        "precio_unitario" => round($product->price*1.18, 2),
                        "codigo_tipo_afectacion_igv" => "10",
                        "total_base_igv" => $order->get_line_subtotal($item_data),
                        "porcentaje_igv" => 18,
                        "total_igv" => $order->get_line_tax($item_data),
                        "total_impuestos" => $order->get_line_tax($item_data),
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

                // codifico el array a json
                $erjsonencode = json_encode($array);
                // ENVIO A LA API
                $response = wp_remote_post(  get_option('facturaloperu_api_config_url'), array(
                    'method' => 'POST',
                    'timeout' => 15,
                    'httpversion' => '1.0',
                    'headers' => array(
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer '.get_option('facturaloperu_api_config_token'),
                    ),
                    'sslverify' => false,
                    'body' => $erjsonencode,
                ));

                if ( is_wp_error( $response ) ) {
                   $error_message = $response->get_error_message();
                   echo "Something went wrong: $error_message";
                } else {
                    $body = wp_remote_retrieve_body( $response );
                    $data = json_decode($body);
                    // SI EXISTE EL ARRAY DATA EN LA RESPUESTA
                    if ($data->data) {
                        // CREO UN ARRAY CON LOS DATOS A GUARDAR
                        $api_response = [
                            'api_number' => $data->data->number,
                            'api_xml'    => $data->links->xml,
                            'api_pdf'    => $data->links->pdf,
                            'api_cdr'    => $data->links->cdr,
                            'api_json'   => $erjsonencode,
                        ];

                        echo '<input type="hidden" name="ep_sunat_api_number" value="'.$api_response['api_number'].'">';
                        echo '<input type="hidden" name="ep_sunat_api_xml" value="'.$api_response['api_xml'].'">';
                        echo '<input type="hidden" name="ep_sunat_api_pdf" value="'.$api_response['api_pdf'].'">';
                        echo '<input type="hidden" name="ep_sunat_api_cdr" value="'.$api_response['api_cdr'].'">';
                        echo '<input type="hidden" name="ep_sunat_api_json" value="'.$api_response['api_json'].'">';
                        echo '<input type="hidden" name="ep_sunat_meta_fields_api_nonce" value="' . wp_create_nonce() . '">';

                        // envio / actualizo la pagina con los valores ya incertados en los inputs
                        echo   "<script>
                                window.onload = function(){
                                    document.post.submit();
                                }
                                </script>";

                    } else {
                        print_r($data);
                    }

                }


            }

            // Pending payment – Order received, no payment initiated. Awaiting payment (unpaid).
            // Failed – Payment failed or was declined (unpaid). Note that this status may not show immediately and instead show as Pending until verified (e.g., PayPal).
            // Processing – Payment received (paid) and stock has been reduced; order is awaiting fulfillment. All product orders require processing, except those that only contain products which are both Virtual and Downloadable.
            // Completed – Order fulfilled and complete – requires no further action.
            // On-Hold – Awaiting payment – stock is reduced, but you need to confirm payment.
            // Cancelled – Cancelled by an admin or the customer – stock is increased, no further action required.
            // Refunded – Refunded by an admin – no further action required.

            if ('completed' == $order_status) {
                if ('' == $checked) {
                    echo '<button type="button" onclick="myFunction()" class="button generate-items">Enviar</button>';
                    echo '<script>
                            function myFunction() {
                                document.getElementById("send_invoice").checked = true;
                                document.post.submit();
                            }
                            </script>';
                }
                // datos del post -> print_r(json_encode($erpost))
                echo '<input type="hidden" name="ep_sunat_meta_field_nonce" value="' . wp_create_nonce() . '">';
                echo '<p style="display:none;">
                    <input type="checkbox" name="send_invoice" id="send_invoice" '.$checked.'>
                </p>';
            } else {
                echo "Estará disponible al momento de que el cliente cancele el producto";
            }

        }

        // SI EXISTE UNA RESPUESTA
        if (isset($api_response)) {
            // MUESTRO LOS DATOS DE LA RESPUESTA
            echo "<br>";
            echo '
            <table style="margin-bottom: 20px;"><tbody>
                <tr>
                    <td><label style="">Numero de Factura Sunat</label></td>
                    <td><input type="hidden" name="ep_sunat_api_number" value="'.$api_response['api_number'].'">'.$api_response['api_number'].'</td>
                </tr>
                <tr>
                    <td><label style="">XML</label></td>
                    <td><a href="'.$api_response['api_xml'].'">'.$api_response['api_xml'].'</a><input type="hidden" name="ep_sunat_api_xml" value="'.$api_response['api_xml'].'"></td>
                </tr>
                <tr>
                    <td><label style="">PDF</label></td>
                    <td><a href="'.$api_response['api_pdf'].'">'.$api_response['api_pdf'].'</a><input type="hidden" name="ep_sunat_api_pdf" value="'.$api_response['api_pdf'].'"></td>
                </tr>
                <tr>
                    <td><label style="">CDR</label></td>
                    <td><a href="'.$api_response['api_cdr'].'">'.$api_response['api_cdr'].'</a><input type="hidden" name="ep_sunat_api_cdr" value="'.$api_response['api_cdr'].'"></td>
                </tr>
                <input type="hidden" name="ep_sunat_api_json" value="'.$api_response['api_json'].'">
            </tbody></table>';

            echo '<input type="hidden" name="ep_sunat_meta_fields_api_nonce" value="' . wp_create_nonce() . '">';

            echo ' <button type="button" onclick="document.post.submit();" class="button generate-items">Salvar Respuesta</button>';
        }

    }
}

// FUNCION QUE SE EJECUTA AL GUARDAR/ACTUALIZAR UNA ORDEN
add_action( 'save_post', 'ep_save_sunat_field', 10, 1 );
if ( ! function_exists( 'ep_save_sunat_field' ) )
{

    function ep_save_sunat_field( $post_id ) {

        // We need to verify this with the proper authorization (security stuff).

        // CHEQUEO SI EL CAMPO EXISTE (CHECKBOX)
        if ( ! isset( $_POST[ 'ep_sunat_meta_field_nonce' ] ) ) {
            return $post_id;
        }
        $nonce = $_REQUEST[ 'ep_sunat_meta_field_nonce' ];

        //Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $nonce ) ) {
            return $post_id;
        }

        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }

        // ACTUALIZO EL VALOR DEL CAMPO
        update_post_meta( $post_id, '_send_invoice', $_POST[ 'send_invoice' ] );
    }
}

// FUNCION QUE SE EJECUTA AL GUARDAR/ACTUALIZAR UNA ORDEN
add_action( 'save_post', 'ep_save_sunat_field_response', 10, 1 );
if ( ! function_exists( 'ep_save_sunat_field_response' ) )
{

    function ep_save_sunat_field_response( $post_id ) {

        // We need to verify this with the proper authorization (security stuff).

        // SI EXISTE EL CAMPO (HIDDEN CON DATOS DE RESPUESTA DE API)
        if ( ! isset( $_POST[ 'ep_sunat_meta_fields_api_nonce' ] ) ) {
            return $post_id;
        }
        $nonce = $_REQUEST[ 'ep_sunat_meta_fields_api_nonce' ];

        //Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $nonce ) ) {
            return $post_id;
        }

        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }

        // GUARDO LOS CAMPOS
        update_post_meta( $post_id, '_ep_sunat_api_response', true );
        update_post_meta( $post_id, '_ep_sunat_api_number', $_POST[ 'ep_sunat_api_number' ] );
        update_post_meta( $post_id, '_ep_sunat_api_xml', $_POST[ 'ep_sunat_api_xml' ] );
        update_post_meta( $post_id, '_ep_sunat_api_pdf', $_POST[ 'ep_sunat_api_pdf' ] );
        update_post_meta( $post_id, '_ep_sunat_api_cdr', $_POST[ 'ep_sunat_api_cdr' ] );
        update_post_meta( $post_id, '_ep_sunat_api_json', $_POST[ 'ep_sunat_api_json' ] );
    }
}
