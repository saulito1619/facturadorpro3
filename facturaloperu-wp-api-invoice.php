<?php
/**
 * Plugin Name: Facturaloperu-wp-api-invoice
 * Plugin URI: http://facturaloperu.com
 * Description: Envio de Facturas a la Sunat por medio del Facturador PRO
 * Version: 1.0.1
 * Author: FacturaloPeru
 * Author URI: http://facturaloperu.com
 * Requires at least: 4.0
 * Tested up to: 5.0.1
 *
 * Text Domain: Facturaloperu-wp-api-invoice
 * Domain Path: /languages/
 */


defined( 'ABSPATH' ) or die( '¡Sin trampas!' );

/*
 * functions.php
 *
 */
require_once( __DIR__ . '/includes/json-generate.php');
require_once( __DIR__ . '/includes/send-invoice.php');
require_once( __DIR__ . '/includes/send-option.php');
require_once( __DIR__ . '/includes/admin/api-config.php');
require_once( __DIR__ . '/includes/query-document.php');