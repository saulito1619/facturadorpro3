<?php
// Añade pagina al menu administrador
function api_config_menu(){
    add_submenu_page( 'woocommerce', 'Ajustes FacturaloPeru Api', 'FacturaloPeru Api', 'administrator', 'facturaloperu-api-config-settings', 'facturaloperu_api_config_page_settings');
}

add_action('admin_menu', 'api_config_menu');

function add_admin_page() {
    add_menu_page(
        'Ajustes FacturaloPeru Api',
        'FacturaloPeru Api',
        'manage_options',
        'facturaloperu-api',
        'facturaloperu_api_config_page_settings'
    );
}

// html con el formulario de opciones
function facturaloperu_api_config_page_settings(){
    $default_tab = null;
    $tab = isset($_GET['tab']) ? $_GET['tab'] : $default_tab;
    ?>
    <div class="wrap">
        <h2>Configuración de conexión de Woocommerce con FacturaloPeru</h2>
        <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
          <a href="?page=facturaloperu-api-config-settings" class="nav-tab <?php if($tab===null):?>nav-tab-active<?php endif; ?>">General</a>
          <a href="?page=facturaloperu-api-config-settings&tab=conection" class="nav-tab <?php if($tab==='conection'):?>nav-tab-active<?php endif; ?>">Conexión API</a>
          <a href="?page=facturaloperu-api-config-settings&tab=guide" class="nav-tab <?php if($tab==='guide'):?>nav-tab-active<?php endif; ?>">Guía</a>
        </nav>

        <div class="tab-content">
        <?php
            switch($tab) :
                case 'conection':
                    ?>

                    <form method="POST" action="options.php">
                        <?php
                            settings_fields('facturaloperu-api-config-settings-group');
                            do_settings_sections('facturaloperu-api-config-settings-group');
                        ?>
                        <table class="form-table">
                            <tr valign="top">
                                <th class="titledesc">
                                    <label>API_TOKEN</label>
                                </th>
                                <td class="forminp forminp-text">
                                    <input type="text" name="facturaloperu_api_config_token" id="facturaloperu_api_config_token" value="<?php echo get_option('facturaloperu_api_config_token'); ?>" style="min-width: 400px" class="input-text regular-input">
                                </td>
                            </tr>
                            <tr>
                                <th class="titledesc">
                                    <label>API_URL</label>
                                </th>
                                <td class="forminp forminp-text">
                                    <input type="text" name="facturaloperu_api_config_url" id="facturaloperu_api_config_url" value="<?php echo get_option('facturaloperu_api_config_url'); ?>" style="min-width: 400px" class="input-text regular-input">
                                </td>
                            </tr>
                        </table>
                        <?php submit_button(); ?>
                    </form>

                    <?php
                    break;
                case 'guide':
                    ?>

                    <h1>Guía</h1>

                    <?php
                    break;
                default:
                    ?>

                    <h1>General</h1>

                    <?php
                    break;
            endswitch;
        ?>
        </div>

    </div>

    <?php
}

function facturaloperu_api_config_settings(){
    register_setting('facturaloperu-api-config-settings-group', 'facturaloperu_api_config_token');
    register_setting('facturaloperu-api-config-settings-group', 'facturaloperu_api_config_url');
}

add_action('admin_init', 'facturaloperu_api_config_settings');


function facturaloperu_api_config_settings_action($content){
    global $post;

    if ($post && $post->pots_type == 'post' && !is_singular('post')) {
        $token = get_option('facturaloperu_api_config_token');
        $url = get_option('facturaloperu_api_config_url');
        return $content;
    }
}

// add_filter('the_content', 'facturaloperu_api_config_settings_action');
