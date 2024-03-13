<?php
/**
 * Plugin Name: Product Quantity Settings
 * Plugin URI:  https://github.com/jjmontalban/woo-quantity-settings
 * Description: Create a new option in product creation. You can to set a minimum, a maximum one or a quantity range for products.
 * Author:      JJMontalban
 * Author URI:  https://jjmontalban.github.io
 * Text Domain: pqs
 * Domain Path: /lang
 * Version:     1.1.0
 * 
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

//Translations
add_action('plugins_loaded', 'wqs_plugin_load_textdomain');

function wqs_plugin_load_textdomain()
{
    $text_domain	= 'pqs';
    $path_languages = basename( dirname(__FILE__) ) . '/lang/';
    
    load_plugin_textdomain( $text_domain, false, $path_languages );
}

/**
 * Clase PQS_Admin_Notice
 *
 * Desactiva el plugin si no se cumplen las dependencias.
 */         
class PQS_Admin_Notice {
	
    /**
     * Constructor.
     *
     * Registra el hook de activación.
     */
	public function __construct() {
		register_activation_hook( __FILE__, array( $this, 'pqs_install' ) );
	}
	
    /**
     * Método pqs_install.
     *
     * Desactiva el plugin y muestra un aviso si el plugin dependiente no está activo.
     */
    public function pqs_install() {
        if ( ! class_exists( 'WooCommerce' ) ) 
        {
            $this->pqs_deactivate_plugin();
            wp_die( sprintf(__( 'This plugin requires Woocommerce to be installed and activated. You can download WooCommerce latest version %1$s or go back to %2$s', 'pqs' ), 
                '<strong><a href="https://downloads.wordpress.org/plugin/woocommerce.latest-stable.zip">Woocommerce</a></strong>', 
                '<strong><a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">plugins</a></strong>' 
                ) );
        }
    }

    /**
     * Método pqs_deactivate_plugin.
     *
     * Función para desactivar el plugin.
     */
    protected function pqs_deactivate_plugin() {
        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        deactivate_plugins( plugin_basename( __FILE__ ) );
        if ( isset( $_GET['activate'] ) ) {
            unset( $_GET['activate'] );
        }
    }

}
new PQS_Admin_Notice();


function pqs_enqueue_scripts() {
    wp_enqueue_script(
        'pqs-scripts',
        plugins_url('product-quantity-settings/scripts.js'),
        array('jquery'),
        '1.0.0',
        true
    );
}
add_action('wp_enqueue_scripts', 'pqs_enqueue_scripts');


/**
 * Esta función se llama cuando se dispara la acción 'woocommerce_product_options_pricing'. Añade campos de entrada
 * para la cantidad mínima, máxima y el rango de cantidad, que se guardan como metadatos del producto.
 */
add_action( 'woocommerce_product_options_pricing', 'pqs_qty_add_product_field' );

function pqs_qty_add_product_field() 
{
    global $product_object;

    $values = $product_object->get_meta( '_qty_args' );

    echo '</div>
            <div class="options_group quantity hide_if_grouped">
                <style>div.qty-args.hidden { display:none; }</style>';

    woocommerce_wp_checkbox( array( // Checkbox.
        'id'            => 'qty_args',
        'label'         => __( 'Quantity settings', 'pqs' ),
        'value'         => empty( $values ) ? 'no' : 'yes',
        'description'   => __( 'Activate the configuration of the quantities field.', 'pqs' ),
    ) );

    echo '<div class="qty-args hidden">';

    woocommerce_wp_text_input( array(
            'id'                => 'qty_min',
            'type'              => 'number',
            'label'             => __( 'Minimum amount', 'pqs' ),
            'placeholder'       => '',
            'desc_tip'          => 'true',
            'description'       => __( 'Minimum amount allowed (>0)', 'pqs' ),
            'custom_attributes' => array( 'step'  => 'any', 'min'   => '0'),
            'value'             => isset( $values['qty_min']) && $values['qty_min'] > 0 ? ( int ) $values['qty_min'] : 0,
    ) );

    woocommerce_wp_text_input( array(
            'id'                => 'qty_max',
            'type'              => 'number',
            'label'             => __( 'Maximum Quantity', 'pqs' ),
            'placeholder'       => '',
            'desc_tip'          => 'true',
            'description'       => __( 'Set the maximum allowed quantity limit (a number greater than 0). Value -1 is unlimited', 'pqs' ),
            'custom_attributes' => array( 'step'  => 'any', 'min'   => '-1'),
            'value'             => isset($values['qty_max']) && $values['qty_max'] > 0 ? (int) $values['qty_max'] : -1,
    ) );

    woocommerce_wp_text_input( array(
            'id'                => 'qty_step',
            'type'              => 'number',
            'label'             => __( 'Quantity Range', 'pqs' ),
            'placeholder'       => '',
            'desc_tip'          => 'true',
            'description'       => __( 'Show range amount allowed', 'pqs' ),
            'custom_attributes' => array( 'step'  => 'any', 'min'   => '1'),
            'value'             => isset($values['qty_step']) && $values['qty_step'] > 1 ? ( int ) $values['qty_step'] : 1,
    ) );

    echo '</div>';
}


/**
 * Inserta un script JavaScript en el pie de página del admin que muestra u oculta los campos de configuración de cantidad
 * en las páginas de edición de productos, dependiendo de si la casilla de verificación 'qty_args' está marcada o no.
 *
 * Este script utiliza jQuery para añadir o quitar la clase 'hidden' del div 'qty-args', que contiene los campos de configuración de cantidad.
 */
add_action( 'admin_footer', 'pqs_product_type_selector_filter' );

function pqs_product_type_selector_filter() 
{
    global $pagenow, $post_type;

    if( in_array( $pagenow, array( 'post-new.php', 'post.php' ) ) && $post_type === 'product' ) :
        ?>
        <script>
        jQuery(function($) {
            if( $('input#qty_args').is(':checked') && $('div.qty-args').hasClass('hidden') ) {
                $('div.qty-args').removeClass('hidden')
            }
            $('input#qty_args').click(function() {
                if( $(this).is(':checked') && $('div.qty-args').hasClass('hidden')) {
                    $('div.qty-args').removeClass('hidden');
                } else if( ! $(this).is(':checked') && ! $('div.qty-args').hasClass('hidden')) {
                    $('div.qty-args').addClass('hidden');
                }
            });
        });
        </script>
        <?php
    endif;
}


/**
 * Recoge los valores de los campos de configuración de cantidad del formulario de edición del producto 
 * y los guarda como metadatos del producto.
 *
 * @param WC_Product $product El objeto del producto que se está guardando.
 */
add_action( 'woocommerce_admin_process_product_object', 'pqs_save_product_quantity_settings' );

function pqs_save_product_quantity_settings( $product ) 
{
    if ( isset( $_POST['qty_args'] ) ) {
        $product->update_meta_data( '_qty_args', array(
            'qty_min' => isset( $_POST['qty_min'] ) && $_POST['qty_min'] > 0 ? ( int ) wc_clean( $_POST['qty_min'] ) : 0,
            'qty_max' => isset( $_POST['qty_max'] ) && $_POST['qty_max'] > 0 ? ( int ) wc_clean($_POST['qty_max'] ) : -1,
            'qty_step' => isset ($_POST['qty_step'] ) && $_POST['qty_step'] > 1 ? ( int ) wc_clean( $_POST['qty_step'] ) : 1,
        ) );
    } else {
        $product->update_meta_data( '_qty_args', array() );
    }
}


/**
 * Modifica los argumentos de entrada de cantidad en el front-end basándose en la configuración de cantidad del producto.
 *
 * Recoge los valores de la configuración de cantidad del producto y los utiliza para modificar los argumentos de cantidad, 
 * que determinan cómo se muestra el campo cantidad en el front-end.
 *
 * @param array $args Los argumentos actuales de cantidad.
 * @param WC_Product $product El objeto del producto para el que se está generando el campo cantidad.
 * @return array Los argumentos modificados de entrada de cantidad.
 */
add_filter( 'woocommerce_quantity_input_args', 'pqs_filter_quantity_input_args', 99, 2 );

function pqs_filter_quantity_input_args( $args, $product ) 
{
    if ( $product->is_type( 'variation' ) ) {
        $parent_product = wc_get_product( $product->get_parent_id() );
        $values  = $parent_product->get_meta( '_qty_args' );
    } else {
        $values  = $product->get_meta( '_qty_args' );
    }
    
    if ( ! empty( $values ) ) {
        // Min valor
        if ( isset( $values['qty_min'] ) && $values['qty_min'] > 1 ) {
            $args['min_value'] = $values['qty_min'];
            
            if( ! is_cart() ) {
                $args['input_value'] = $values['qty_min'];
            }
        }
        
        // Max valor
        if ( isset( $values['qty_max'] ) && $values['qty_max'] > 0 ) {
            $args['max_value'] = $values['qty_max'];
            
            if ( $product->managing_stock() && ! $product->backorders_allowed() ) {
                $args['max_value'] = min( $product->get_stock_quantity(), $args['max_value'] );
            }
        }
        
        // Step valor
        if ( isset( $values['qty_step'] ) && $values['qty_step'] > 1 ) 
        {
            $args['step'] = $values['qty_step'];
        }
    }
    
    return $args;
}


/**
 * Filtra los argumentos del botón "Añadir al carrito" en las páginas de tienda y archivo.
 *
 * Modifica la cantidad predeterminada que se añade al carrito para que sea el valor mínimo
 * establecido en la configuración de cantidad del producto.
 *
 * @param array $args Los argumentos actuales del botón "Añadir al carrito".
 * @param WC_Product $product El objeto del producto para el que se está generando el botón.
 * @return array Los argumentos modificados del botón "Añadir al carrito".
 */
add_filter( 'woocommerce_loop_add_to_cart_args', 'pqs_filter_loop_add_to_cart_quantity_arg', 10, 2 );

function pqs_filter_loop_add_to_cart_quantity_arg( $args, $product ) 
{
    $values  = $product->get_meta( '_qty_args' );

    if ( ! empty( $values ) ) {
        // Min value
        if ( isset( $values['qty_min'] ) && $values['qty_min'] > 1 ) {
            $args['quantity'] = $values['qty_min'];
        }
    }

    return $args;
}


/**
 * Filtra las variaciones disponibles para modificar las cantidades mínima y máxima permitidas.
 *
 * Modifica la cantidad mínima y máxima que se puede seleccionar para las variaciones de un producto
 * en función de la configuración de cantidad del producto.
 *
 * @hooked woocommerce_available_variation
 *
 * @param array $data Los datos actuales de la variación.
 * @param WC_Product $product El objeto del producto para el que se están generando las variaciones.
 * @param WC_Product_Variation $variation La variación actual que se está generando.
 * @return array Los datos modificados de la variación.
 */
add_filter( 'woocommerce_available_variation', 'pqs_filter_available_variation_price_html', 10, 3);

function pqs_filter_available_variation_price_html( $data, $product, $variation ) 
{
    $values  = $product->get_meta( '_qty_args' );

    if ( ! empty( $values ) ) {
        if ( isset( $values['qty_min'] ) && $values['qty_min'] > 1 ) {
            $data['min_qty'] = $values['qty_min'];
        }

        if ( isset( $values['qty_max'] ) && $values['qty_max'] > 0 ) {
            $data['max_qty'] = $values['qty_max'];

            if ( $variation->managing_stock() && ! $variation->backorders_allowed() ) {
                $data['max_qty'] = min( $variation->get_stock_quantity(), $data['max_qty'] );
            }
        }
    }

    return $data;
}



/**
 * Filtra el HTML del campo de cantidad en la página del carrito para aplicar las restricciones de cantidad.
 *
 * @param string $product_quantity HTML para el campo de cantidad.
 * @param string $cart_item_key El identificador único del artículo en el carrito.
 * @param array $cart_item Los datos del artículo en el carrito.
 * @return string El HTML modificado para el campo de cantidad.
 */
add_filter( 'woocommerce_cart_item_quantity', 'pqs_cart_item_quantity', 10, 3 );

function pqs_cart_item_quantity( $product_quantity, $cart_item_key, $cart_item ) {
    $product = $cart_item['data'];
    if ( $product->is_type( 'variation' ) ) {
        $parent_product = wc_get_product( $product->get_parent_id() );
        $values  = $parent_product->get_meta( '_qty_args' );
    } else {
        $values  = $product->get_meta( '_qty_args' );
    }

    if ( ! empty( $values ) ) {
        $min = isset( $values['qty_min'] ) && $values['qty_min'] > 0 ? $values['qty_min'] : 1;
        $max = isset( $values['qty_max'] ) && $values['qty_max'] > 0 ? $values['qty_max'] : '';
        $step = isset( $values['qty_step'] ) && $values['qty_step'] > 1 ? $values['qty_step'] : 1;

        $product_quantity = woocommerce_quantity_input( array(
            'input_name'   => "cart[{$cart_item_key}][qty]",
            'min_value'    => $min,
            'max_value'    => $max,
            'step'         => $step,
            'input_value'  => $cart_item['quantity'],
        ), $product, false );

    }
    return $product_quantity;
}
