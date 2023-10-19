<?php
/**
 * Plugin Name: Formulario de Contacto Personalizado
 * Description: Inserta un formulario de contacto con el shortcode [formulario_contacto]
 * Version: 1.0.1
 * Author: Saul Hernandez
 * License: GPL2
 */

// Shortcode para el formulario
add_shortcode('formulario_contacto', 'render_formulario_contacto');

function render_formulario_contacto() {
    $button_label = get_option('formulario_contacto_button_label', 'Enviar');
    
    $output = '<form action="" method="post" id="formulario_contacto">';

    // Añadir nonce al formulario
    $output .= wp_nonce_field( 'formulario_contacto_nonce_action', 'formulario_contacto_nonce_field', true, false );
    
    $output .= '<input type="text" name="nombre" required placeholder="Nombre">
        <input type="email" name="email" required placeholder="Correo electrónico">
        <textarea name="mensaje" required placeholder="Mensaje"></textarea>
        <input type="submit" value="' . esc_attr($button_label) . '">
    </form>';
    
    return $output;
}

// Guardar datos en la base de datos de WordPress
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nombre'])) {

    // Verificar el nonce antes de procesar el formulario
    if ( !isset( $_POST['formulario_contacto_nonce_field'] ) || !wp_verify_nonce( $_POST['formulario_contacto_nonce_field'], 'formulario_contacto_nonce_action' ) ) {
        die( '¡Acceso no permitido!' );
    }

    global $wpdb;

    $nombre = sanitize_text_field($_POST['nombre']);
    $email = sanitize_email($_POST['email']);
    $mensaje = sanitize_textarea_field($_POST['mensaje']);

    $wpdb->insert($wpdb->prefix . 'contactos', [
        'nombre' => $nombre,
        'email' => $email,
        'mensaje' => $mensaje
    ]);
}

// Menú del panel de administración
add_action('admin_menu', 'formulario_contacto_settings');

function formulario_contacto_settings() {
    add_menu_page('Data de Formulario', 'Data de Formulario', 'manage_options', 'data-formulario', 'formulario_contacto_admin_page', 'dashicons-feedback', 71);
    add_submenu_page('data-formulario', 'Mensajes de Contacto', 'Mensajes de Contacto', 'manage_options', 'data-formulario');
    add_submenu_page('data-formulario', 'Configuración de Formulario', 'Configuración', 'manage_options', 'formulario-contacto-config', 'formulario_contacto_options_page');
}

function formulario_contacto_options_page() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        update_option('formulario_contacto_button_label', $_POST['button_label']);
    }
    
    $button_label = get_option('formulario_contacto_button_label', 'Enviar');
    
    echo '<form method="post">
        <label>Etiqueta del botón:</label>
        <input type="text" name="button_label" value="' . esc_attr($button_label) . '">
        <input type="submit" value="Guardar">
    </form>';
}

function formulario_contacto_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'contactos';

    $messages = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
    
    echo '<div class="wrap">';
    echo '<h1>Mensajes de Contacto</h1>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>ID</th><th>Nombre</th><th>Email</th><th>Mensaje</th></tr></thead>';
    echo '<tbody>';
    
    foreach ($messages as $message) {
        echo '<tr>';
        echo '<td>' . esc_html($message['id']) . '</td>';
        echo '<td>' . esc_html($message['nombre']) . '</td>';
        echo '<td>' . esc_html($message['email']) . '</td>';
        echo '<td>' . esc_html($message['mensaje']) . '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

// Activación del plugin - Crea la tabla de contactos
register_activation_hook(__FILE__, 'formulario_contacto_install');

function formulario_contacto_install() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'contactos';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        nombre tinytext NOT NULL,
        email varchar(255) NOT NULL,
        mensaje text NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
