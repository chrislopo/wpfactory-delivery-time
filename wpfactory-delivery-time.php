<?php

/**
 * Plugin Name:       Delivery Time for Woocommerce
 * Description:       Display the delivery time on the product or archive page
 * Version:           1.0
 * Author:            Chris Lopo
 * Author URI:        https://github.com/chrislopo
 */

/**
 * Create the Delivery time section beneath the products
 **/
if (!function_exists('wcdeliverytime_add_section')) {
    add_filter( 'woocommerce_get_sections_shipping', 'wcdeliverytime_add_section' );
    function wcdeliverytime_add_section( $sections ) {

        $sections['wcdeliverytime'] = __( 'Delivery time', 'woocommerce' );
        return $sections;

    }
}

/**
 * Add settings to the Delivery time section
 * 
 * display this field using get_option( 'wcdeliverytime_value' )
 */

if (!function_exists('wcdeliverytime_all_settings')) {
    add_filter( 'woocommerce_get_settings_shipping', 'wcdeliverytime_all_settings', 10, 2 );
    function wcdeliverytime_all_settings( $settings, $current_section ) {
    
        if ( $current_section == 'wcdeliverytime' ) {
            $settings_deliverytime = array();
    
            $settings_deliverytime[] = array(
                'name' => __( 'Default delivery time', 'woocommerce' ),
                'type' => 'title',
                'desc' => __( 'The following option is used to display the default delivery time for the products', 'woocommerce' ), 
                'id' => 'wcdeliverytime'
            );
    
            $settings_deliverytime[] = array(
                'name'     => __( 'Delivery time', 'woocommerce' ),
                'desc_tip' => __( 'Enter the number of days', 'woocommerce' ),
                'id'       => 'wcdeliverytime_value',
                'type'     => 'number',
                'desc'     => __( 'This is the default value when delivery time is empty' ),
            );
            
            $settings_deliverytime[] = array(
                'name'     => __( 'Display on', 'woocommerce' ),
                'id'       => 'wcdeliverytime_display',
                'desc_tip' => __( 'Select where delivery time will be displayed', 'woocommerce' ),
                'type'     => 'multiselect',
                'desc'     => __( 'This is where the delivery time information will be displayed', 'woocommerce' ),
                'default'  => get_option( 'wcdeliverytime_display', 'single' ),
                'options' => array(
                    'single' => __('Single product page', 'woocommerce'),
                    'archive' => __('Product archive page', 'woocommerce')
                )
            );
    
            $settings_deliverytime[] = array(
                'name'     => __( 'Color', 'woocommerce' ),
                'desc_tip' => __( 'Select the color of the text', 'woocommerce' ),
                'id'       => 'wcdeliverytime_color',
                'type'     => 'color',
                'desc'     => __( 'The color that the delivery time information will be displayed', 'woocommerce' ),
            );
            
            $settings_deliverytime[] = array( 'type' => 'sectionend', 'id' => 'wcdeliverytime' );

            return $settings_deliverytime;

        } else {

            return $settings;

        }
    
    }
}

/**
 * Add delivery time to the product's Shipping Tab
 * 
 */

if (!function_exists('action_wc_product_options_deliverytime')) {

    add_action( 'woocommerce_product_options_shipping', 'action_wc_product_options_deliverytime', 10, 0 );

    function action_wc_product_options_deliverytime(){ 

        $deliverytime[] = array(
            'label'         => __( 'Delivery time', 'woocommerce' ),
            'placeholder'   => __( 'Delivery time in days', 'woocommerce' ),
            'id'            => '_deliverytime',
            'type'          => 'number',
            'desc_tip'      => true,
            'description'   => __( 'Leave empty or 0 to display default value or add -1 to hide this information', 'woocommerce' ),
        );

        $deliverytime[] = array(
            'label'         => __( 'Delivery time description', 'woocommerce' ),
            'placeholder'   => __( 'Simple description for the information', 'woocommerce' ),
            'id'            => '_deliverytime_description',
            'type'          => 'text',
            'desc_tip'      => true,
            'description'   => __( 'Add here any relevant information related to the delivery time', 'woocommerce' ),
        );

        foreach($deliverytime as $dt) {
            woocommerce_wp_text_input( $dt );
        }
    }
}


/**
 * 
 * Save the delivery time data
 * 
 */

if (!function_exists('action_woocommerce_product_save_deliverytime')) {

    add_action( 'woocommerce_admin_process_product_object', 'action_woocommerce_product_save_deliverytime', 10, 1 );

    function action_woocommerce_product_save_deliverytime( $product ) {

        if( isset($_POST['_deliverytime']) ) {
            $product->update_meta_data( '_deliverytime', sanitize_text_field( $_POST['_deliverytime'] ) );
        }

        if( isset($_POST['_deliverytime_description']) ) {
            $product->update_meta_data( '_deliverytime_description', sanitize_text_field( $_POST['_deliverytime_description'] ) );
        }
    }    
}

/**
 * 
 * Display the delivery time on the single product page
 * 
 */

if (!function_exists('woocomerce_deliverytime_tab')) {

    add_filter( 'woocommerce_product_tabs', 'woocomerce_deliverytime_tab' );

    function woocomerce_deliverytime_tab( $tabs ) {

        global $product;

        $delivery_default       = get_option('wcdeliverytime_value');
        $delivery_product       = $product->get_meta('_deliverytime');
        $delivery_display       = get_option('wcdeliverytime_display') ? get_option('wcdeliverytime_display') : array();
        $delivery_time          = $delivery_product ? $delivery_product : $delivery_default;

        if ($delivery_product == 0) { $delivery_time = $delivery_default; }

        if ((in_array('single', $delivery_display)) && ($delivery_time > 0) && ($delivery_default > 0)) {
            $tabs['woocomerce_deliverytime_tab'] = array(
                'title'    => __('Delivery time', 'woocommerce'),
                'callback' => 'woocomerce_deliverytime_tab_content',
                'priority' => 50,
            );
        }

        return $tabs;
    }
}

if (!function_exists('woocomerce_deliverytime_tab_content')) {
    function woocomerce_deliverytime_tab_content( $slug, $tab ) {

        global $product;

        $delivery_default       = get_option('wcdeliverytime_value');
        $delivery_color         = get_option('wcdeliverytime_color');
        $delivery_product       = $product->get_meta('_deliverytime');
        $delivery_description   = $product->get_meta('_deliverytime_description');

        $delivery_time = $delivery_product ? $delivery_product : $delivery_default;
        if ($delivery_product == 0) { $delivery_time = $delivery_default; }

        if ($delivery_time == 1) {
            $days = __('day', 'woocommerce');
        } else {
            $days = __('days', 'woocommerce');
        }

        echo '<strong style="color: '. $delivery_color .'" class="wc-delivery-description">'. __('Delivery time', 'woocommerce') .': '. $delivery_time .' '. $days .'</strong><br>';

        if ($delivery_description) {
            wp_enqueue_script('jquery');

            echo '<span class="wc-delivery-description-text" style="display: none;"></span>';

            add_action('wp_footer', 'wc_delivery_description_script', 50);
            function wc_delivery_description_script() { ?>
                <style type="text/css">.wc-delivery-description { cursor: pointer; }</style>
                <script>jQuery(document).ready(function($) {
                    $(".wc-delivery-description").on("click", function() {
                        jQuery.ajax({
                            url: "<?php echo admin_url('admin-ajax.php'); ?>",
                            type: "post",
                            data: {
                                action: "get_wc_deliverytime_description_text",
                                productID: "<?php echo get_the_id(); ?>"
                            },
                            success: response => {
                                $(".wc-delivery-description-text").toggle().html(response);
                            }
                        })
                    });
                });</script>
            <?php }
        }
    }
}

/**
 * 
 * Return the description text via AJAX
 *
 */

if (!function_exists('get_wc_deliverytime_description_text')) {
    add_action('wp_ajax_get_wc_deliverytime_description_text', 'get_wc_deliverytime_description_text');
    add_action('wp_ajax_nopriv_get_wc_deliverytime_description_text', 'get_wc_deliverytime_description_text');

    function get_wc_deliverytime_description_text() {
        $id = $_POST['productID'];

        if (is_numeric($id)) {
            $product = wc_get_product($id);
            $delivery_description   = $product->get_meta('_deliverytime_description');

            echo $delivery_description;
        }

        die();
    }
}

/**
 * 
 * Display delivery time on archive
 * 
 */

if (!function_exists('get_wc_deliverytime_description_archive')) {
    add_action( 'woocommerce_after_shop_loop_item_title', 'get_wc_deliverytime_description_archive', 2 );
    function get_wc_deliverytime_description_archive() {
        global $product;

        $delivery_default       = get_option('wcdeliverytime_value');
        $delivery_product       = $product->get_meta('_deliverytime');
        $delivery_display       = get_option('wcdeliverytime_display') ? get_option('wcdeliverytime_display') : array();
        $delivery_time          = $delivery_product ? $delivery_product : $delivery_default;

        if ($delivery_product == 0) { $delivery_time = $delivery_default; }

        if ((in_array('archive', $delivery_display)) && ($delivery_time > 0) && ($delivery_default > 0)) {

            $delivery_time = $delivery_product ? $delivery_product : $delivery_default;
            if ($delivery_product == 0) { $delivery_time = $delivery_default; }
            if ($delivery_time == 1) {
                $days = __('day', 'woocommerce');
            } else {
                $days = __('days', 'woocommerce');
            }

            echo '<strong>'. __('Delivery time', 'woocommerce') .': '. $delivery_time .' '. $days .'</strong>';
        }
    }
}