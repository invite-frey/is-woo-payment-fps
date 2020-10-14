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
            $this->write_qr_code_to_file = $this->get_option('write_qr_code_to_file');
    
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
                    'description' => __('E-mail address, phone number (+852-xxxxxxxx) or specific FPS id',ITS_WPF_PLUGIN_ID),
                ),
                'account_bank_code' => array(
                    'title'       => __('Bank Code',ITS_WPF_PLUGIN_ID),
                    'type'        => 'text',
                    'description' => __('Three digit Hong Kong bank code.',ITS_WPF_PLUGIN_ID)
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

        public function validate_text_field($key, $value){

            switch($key){
                
                case 'account_fps_id':
                    $fps_id = trim($value);

                    switch($this->get_option( 'account_id_type' )){
                        case '03':
                            if( preg_match('/^\+852\-[0-9]{8}$/', $fps_id) ){
                                return $fps_id;
                            }else{
                                function my_error_notice() {
                                    ?>
                                    <div class="error notice">
                                        <p><?php _e( 'The account id must be a HK phone number formatted: +852-xxxxxxxx.', ITS_WPF_PLUGIN_ID ); ?></p>
                                    </div>
                                    <?php
                                }
                                add_action( 'admin_notices', 'my_error_notice' );

                                function alert_border_account_id(){
                                    ?>
                                    <script language="javascript">
                                        document.querySelector('#woocommerce_its_wpf_payment_gateway_account_fps_id').style.borderColor = 'red';
                                    </script>
                                    <?php
                                }
                                add_action( 'admin_footer', 'alert_border_account_id');
                                return $this->account_fps_id;
                            }
                            break;
                        case '04':
                            
                            if( preg_match('/^(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){255,})(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){65,}@)(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22))(?:\.(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-[a-z0-9]+)*\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-[a-z0-9]+)*)|(?:\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\]))$/', $fps_id)){
                                return $fps_id;
                            }else{
                                function my_error_notice() {
                                    ?>
                                    <div class="error notice">
                                        <p><?php _e( 'The account id must be a valid e-mail address', ITS_WPF_PLUGIN_ID ); ?></p>
                                    </div>
                                    <?php
                                }
                                add_action( 'admin_notices', 'my_error_notice' );

                                function alert_border_account_id(){
                                    ?>
                                    <script language="javascript">
                                        document.querySelector('#woocommerce_its_wpf_payment_gateway_account_fps_id').style.borderColor = 'red';
                                    </script>
                                    <?php
                                }
                                add_action( 'admin_footer', 'alert_border_account_id');
                                return $this->account_fps_id;
                            }
                            break;
                        default:
                        return parent::validate_text_field($key,$value);
                            break;
                    }

                    break;
                case 'account_bank_code':
                    $account = trim($value);
                    if( preg_match('/^[0-9]{3}$/', $account) ){
                        return $account;
                    }else{
                        function my_error_notice() {
                            ?>
                            <div class="error notice">
                                
                                <p><?php _e( 'The bank code must consist of three digits, including leading zeros.', ITS_WPF_PLUGIN_ID ); ?></p>
                            </div>
                            <?php
                        }
                        add_action( 'admin_notices', 'my_error_notice' );

                        function alert_border_bank_code(){
                            ?>
                            <script language="javascript">
                                document.querySelector('#woocommerce_its_wpf_payment_gateway_account_bank_code').style.borderColor = 'red';
                            </script>
                            <?php
                        }
                        add_action( 'admin_footer', 'alert_border_bank_code');

                        return $this->account_bank_code;
                    }
                    break;
                default:
                    return parent::validate_text_field($key,$value);
                    break;

            }
            
            

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
                "amount"    => $this->get_order_total(),
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

        private function qrcode_file_url($data_string){
            $path = plugin_dir_path( __FILE__ ) . "qrcodes/";
            $filename =  md5($data_string) . '.png';

            if( !file_exists($path . $filename) ){
                require_once('libs/phpqrcode.php');  
                QRcode::png($data_string,$path . $filename,QR_ECLEVEL_H);
            }

            return plugin_dir_url( __FILE__ ) . "qrcodes/" . $filename;
        }
    
        public function payment_fields() {
    
            global $wp;
    
            $fps_ref_string = $wp->query_vars['order-pay'] ?? $this->random_strings(5);
            $fps_data = $this->fps_data($fps_ref_string);
        
            if( !$fps_data ){
                echo __("This payment method is only available for HKD payments",ITS_WPF_PLUGIN_ID);
                return;
            }

            $fps_data['currency'] = $fps_data['curr'];
            $qrcode = new ITS_FPS_QRCodeData($fps_data);
    
            $qr_code_url = $this->qrcode_file_url($qrcode->getDataString()); 

            if ( $this->description ) {
                echo wpautop( wp_kses_post( $this->description ) );
            }
    
            echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';
            
            ?>
            <div class="form-row form-row-wide">
                <?php __("FPS id:",ITS_WPF_PLUGIN_ID); ?> <strong><?php echo $this->account_fps_id ?></strong>
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
