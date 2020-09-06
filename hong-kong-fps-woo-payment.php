<?php 
/**
 * @package Hong_Kong_FPS_Woo_Payment
 * @version 1.3
 */
/*
Plugin Name: Hong Kong FPS Woo Payment
Plugin URI: https://github.com/invite-frey/is-woo-payment-fps
Description: Woocommerce payment method enabling Hong Kong FPS payments. Displays QR code and FPS payent if to user. Requires manual confirmation.
Author: Frey Mansikkaniemi, invITe Services
Version: 1.3
Author URI: http://frey.hk/
License: GPLv3
*/

if (!defined('ABSPATH')) {
    return;
}

/**
 * Check if WooCommerce is active
 **/
if (
    !in_array(
        'woocommerce/woocommerce.php',
        apply_filters('active_plugins', get_option('active_plugins'))
    )
) {
    add_action('admin_notices', function () {
        ?>
            <div class="error notice">
                <strong><?php __('Woo Payment FPS requires WooCommerce to be installed & activated.',ITS_WPF_PLUGIN_ID)?></strong>
            </div>
        <?php
    });

    return;
}


define('ITS_WPF_PLUGIN_ID','its_wpf_payment_gateway');


/**
 * Register payment gateway class
 */

add_filter( 'woocommerce_payment_gateways', 'its_wpf_add_class' );

function its_wpf_add_class( $methods ){
    $methods[] = 'WC_Gateway_Invite_FPS_Payment_Gateway';
    return $methods;
}


/**
 * Reqister query var for qrcode image generation
 */

add_filter( 'query_vars', 'its_wpf_qrcode_add_var' );
function its_wpf_qrcode_add_var( $vars )
{
    $vars[] = 'generate_fps_qrcode';
    $vars[] = '_wpnonce';
    return $vars;
}

/**
 * Print out qr code when the right query var is found
 */

add_action( 'init', 'its_wpf_qrcode_catch', 0 );
function its_wpf_qrcode_catch()
{   
    if(isset($_REQUEST['generate_fps_qrcode']) && isset($_REQUEST['_wpnonce']))
    {
        $qrcode_string = $_REQUEST['generate_fps_qrcode'];
        $nonce = $_REQUEST['_wpnonce'];
        if( $qrcode_string && $nonce && wp_verify_nonce( $nonce, ITS_WPF_PLUGIN_ID ) )
        {
            require_once('libs/phpqrcode.php');
            ob_clean(); //Clean the output buffer before printing out image
            header('Content-Type: image/png');        
            QRcode::png($qrcode_string,false,QR_ECLEVEL_H);
            exit();
        }
    }
}

/**
 * Change text on the Pay order button.
 */

add_filter('woocommerce_available_payment_gateways', 'its_wpf_pay_order_label');
function its_wpf_pay_order_label($gateways) {
    if($gateways[ITS_WPF_PLUGIN_ID]) {
        $gateways[ITS_WPF_PLUGIN_ID]->order_button_text = __('Confirm FPS Payment Completed',ITS_WPF_PLUGIN_ID);
    }
    return $gateways;
}

/**
 * Change text on the Thank You page.
 */

add_filter('woocommerce_thankyou_' . ITS_WPF_PLUGIN_ID,  'its_wpf_thankyou', 10, 1);
function its_wpf_thankyou($order_id){
    global $woocommerce;
    $order = wc_get_order( $order_id );

    echo '<p>';
    echo __('Your payment will be confirmed manually.',ITS_WPF_PLUGIN_ID);
    echo '</p><p>';
    echo __('FPS Transaction Reference: ',ITS_WPF_PLUGIN_ID) . $order->get_transaction_id();
}

/**
 * Load payment gateway class
 */

add_action( 'plugins_loaded', 'its_wpf_init_gateway' );

function its_wpf_init_gateway(){
    require_once 'wc-gateway-invite-fps-payment-gateway-class.php';
}

/**
 * Load class that generates qrcode data.
 */

add_action('init', 'its_wpf_init_fps_qrcode_class');

function its_wpf_init_fps_qrcode_class(){
    require_once 'its-fps-qrcodedata-class.php';
}

?>

