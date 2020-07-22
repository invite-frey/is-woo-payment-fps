<?php
if (!defined('ABSPATH')) {
    return;
}

if( !class_exists('WC_Gateway_Invite_FPS_Payment_Gateway') ){
    class WC_Gateway_Invite_FPS_Payment_Gateway extends WC_Payment_Gateway {
        public function __construct() {
            $this->id = ITS_WPF_PLUGIN_ID;
            $this->icon = plugins_url('assets/fps.png', __FILE__); 
            $this->has_fields = true;
            $this->method_title = __('Hong Kong Faster Payment System (FPS)',ITS_WPF_PLUGIN_ID);
            $this->method_description = __("Hong Kong interbank real time transfer using account holder ids and QR codes.",ITS_WPF_PLUGIN_ID);
            $this->supports = array(
                'products'
            );
            $this->init_form_fields();
            $this->init_settings();
            $this->title = $this->get_option( 'title' );
            $this->description = $this->get_option( 'description' );
            $this->enabled = $this->get_option( 'enabled' );
            $this->account_id_type = $this->get_option( 'account_id_type' );
            $this->account_fps_id = $this->get_option( 'account_fps_id' );
            $this->account_bank_code = $this->get_option( 'account_bank_code' );
            $this->ask_to_pay = $this->get_option( 'ask_to_pay' );
            $this->fps_payment_reference_guide = $this->get_option( 'fps_payment_reference_guide' );
    
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        }
    
        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title'       => __('Enable/Disable',ITS_WPF_PLUGIN_ID),
                    'label'       => __('Enable Hong Kong Faster Payment System',ITS_WPF_PLUGIN_ID),
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no'
                ),
                'title' => array(
                    'title'       => __('Title',ITS_WPF_PLUGIN_ID),
                    'type'        => 'text',
                    'description' => __('This controls the title which the user sees during checkout.',ITS_WPF_PLUGIN_ID),
                    'default'     => __('Hong Kong FPS',ITS_WPF_PLUGIN_ID),
                    'desc_tip'    => true
                ),
                'description' => array(
                    'title'       => __('Description',ITS_WPF_PLUGIN_ID),
                    'type'        => 'textarea',
                    'description' => __('This controls the description which the user sees during checkout.',ITS_WPF_PLUGIN_ID),
                    'default'     => __('Pay with HK Faster Payment System (FPS). Scan the presented QR code with your bank\'s app or enter the payee FPS id.',ITS_WPF_PLUGIN_ID),
                ),
                'account_id_type' => array(
                    'title'       => __('Account Id Type',ITS_WPF_PLUGIN_ID),
                    'type'        => 'select',
                    'options'     => array(
                        '02'    => __('FPS ID',ITS_WPF_PLUGIN_ID),
                        '03'    => __('Mobile Phone Number',ITS_WPF_PLUGIN_ID),
                        '04'     => __('Email Address',ITS_WPF_PLUGIN_ID)
                    ),
                    'default'    => '02'
                ),
                'account_fps_id' => array(
                    'title'       => __('Account Id',ITS_WPF_PLUGIN_ID),
                    'type'        => 'text',
                    'description' => __('E-mail address, phone number or specific FPS id',ITS_WPF_PLUGIN_ID),
                ),
                'account_bank_code' => array(
                    'title'       => __('Bank Code',ITS_WPF_PLUGIN_ID),
                    'type'        => 'text',
                    'description' => __('Three number Hong Kong bank code.',ITS_WPF_PLUGIN_ID)
                ),
                'fps_payment_reference_guide' => array(
                    'title'       => __('Payment Reference Guide',ITS_WPF_PLUGIN_ID),
                    'type'        => 'textarea',
                    'default'     => __('Please input the payment reference number below after payment has been completed.',ITS_WPF_PLUGIN_ID),
                    'description' => __('Instructions visible to the customer for providing payment reference number after payment. This is not visble of Ask to Pay is active.',ITS_WPF_PLUGIN_ID)
                ),
                'ask_to_pay' => array(
                    'title'       => __('Ask to Pay Enabled',ITS_WPF_PLUGIN_ID),
                    'label'       => __('Ask to Pay Enabled',ITS_WPF_PLUGIN_ID),
                    'type'        => 'checkbox',
                    'description' => __('The ask to pay function must be enabled by your bank in order to use payment reference numbers.',ITS_WPF_PLUGIN_ID),
                    'default'     => 'no'
                ),
            );
        }
    
        private function fps_data($reference){
    
            global $woocommerce;
            
            $currency_code = get_woocommerce_currency();
    
            //echo $currency_code . '<br>';
    
            $fps_currencies = array(
                "HKD" => "344",
                "CNY" => "156"
            );
    
            $fps_currency = $fps_currencies[$currency_code] ?? null;
    
            if( !$fps_currency )
                return null;
    
            $data = array(
                "account"   => $this->account_id_type,
                "bank_code" => $this->account_bank_code,
                "fps_id"    => $this->account_id_type === "02" ? $this->account_fps_id : "",
                "mobile"    => $this->account_id_type === "03" ? $this->account_fps_id : "",
                "email"     => $this->account_id_type === "04" ? $this->account_fps_id : "",
                "mcc"       => "0000",
                "curr"      => $fps_currency,
                "amount"    => '' . $this->get_order_total(),
                "reference" => $this->ask_to_pay === 'yes' ? $reference : ""
    
            );
    
            return $data;
        }
    
        private function urlencode_array($array){
            $url = "";
            $delimiter = "";
    
            foreach ($array as $key => $value) {
                if($value !== ""){
                    $url .= $delimiter . $key . '=' .  urlencode($value);
                    $delimiter = "&";
                }
            }
    
            return $url;
        }
    
        public function payment_fields() {
    
            global $wp;
    
            $fps_ref_string = $wp->query_vars['order-pay'] ?? $this->random_strings(5);
            $fps_data = $this->fps_data($fps_ref_string);
            $fps_data['currency'] = $fps_data['curr'];
            $qrcode = new ITS_FPS_QRCodeData($fps_data);
    
            if( !$fps_data ){
                echo __("This payment method is only available for HKD payments",ITS_WPF_PLUGIN_ID);
                return;
            }
    
            $qr_code_url = add_query_arg(
                '_wpnonce',
                wp_create_nonce(ITS_WPF_PLUGIN_ID),
                plugins_url('fps-qrcode.php', __FILE__)  . '?generate_fps_qrcode=' . urlencode($qrcode->getDataString())                
            );

            if ( $this->description ) {
                echo wpautop( wp_kses_post( $this->description ) );
            }
    
            echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';
            
            ?>
            <div class="form-row form-row-wide">
                FPS id: <strong><?php echo $this->account_fps_id ?></strong>
            </div>
    
            <?php
    
            if ($this->ask_to_pay === 'no') {?>
                <div class="form-row form-row-wide">
                    <label>FPS Payment Reference <span class="required">*</span><br><small><?php echo $this->fps_payment_reference_guide?></small></label>
                    <input id="its_wpf_payment_ref" name="its_wpf_payment_ref" type="text" autocomplete="off" value="<?php echo $fps_ref_string ?>">
                </div>
            <?php
            }else{?>
                <input id="its_wpf_payment_ref"  name="its_wpf_payment_ref" type="hidden" value="<?php echo $fps_ref_string ?>">
            <?php
            }
            ?>
            <div class="form-row form-row-wide" style="text-align: center;">
                <img src="<?php echo $qr_code_url?>">
            </div>
    
            
            <?php    
            
            echo '<div class="clear"></div></fieldset>';
        }
    
        public function validate_fields() {
    
            if( empty( $_POST[ 'its_wpf_payment_ref' ]) ) {
                wc_add_notice(  __('Payment reference is required!',ITS_WPF_PLUGIN_ID), 'error' );
                return false;
            }else{
                $trimmed = preg_replace('/\s+/', '', sanitize_text_field($_POST[ 'its_wpf_payment_ref' ]) );
    
                if (strlen($trimmed) === 0) {
                    wc_add_notice(  __('Invalid payment reference!',ITS_WPF_PLUGIN_ID), 'error' );
                    return false;
                }
            }
            return true;
        }
    
        public function process_payment( $order_id ) {
            global $woocommerce;
            $order = new WC_Order( $order_id );
    
            $order->update_status('on-hold', __( 'Awaiting manual confirmation of FPS payment.', ITS_WPF_PLUGIN_ID ));
            $order->set_transaction_id(sanitize_text_field($_POST[ 'its_wpf_payment_ref' ]));
            $order->save();
    
            $woocommerce->cart->empty_cart();
    
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url( $order )
            );
        }
    
        private function random_strings($length_of_string) { 
      
            // md5 the timestamps and returns substring 
            // of specified length 
            return substr(md5(time()), 0, $length_of_string); 
        } 
    }
}



?>