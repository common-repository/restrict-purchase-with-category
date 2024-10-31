<?php
/**
 * Plugin Name: Category Restriction WooCommerce
 * Author URI: https://www.themelocation.com
 * Description: Limit purchases of products to just one category.
 * Version: 1.0
 * Author: themelocation
 * GPL12
 */



// Check if WooCommerce is active

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {





    register_activation_hook( __FILE__, 'CRWCreateDB' );

    function CRWCreateDB() {

        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'crw_settings';

        if($wpdb->get_var("show tables like '$table_name'") != $table_name)
        {
            $sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		status smallint(5) NOT NULL,
		msg_note TEXT NOT NULL,
		UNIQUE KEY id (id)
	) $charset_collate;";

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );


            $wpdb->insert(
                $table_name,
                array(
                    'status' => 1
                )
            );

        }
        else
        {
              global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'crw_settings';

        $wpdb->query( "DROP TABLE IF EXISTS ".$table_name );


         $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        status smallint(5) NOT NULL,
        msg_note TEXT NOT NULL,
        UNIQUE KEY id (id)
    ) $charset_collate;";

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );


            $wpdb->insert(
                $table_name,
                array(
                    'status' => 1
                )
            );
            



        }


    }


    add_action( 'admin_action_crw10500', 'CRW10500AdminAction' );

    function CRW10500AdminAction()
    {

        global $wpdb;

        $table_name = $wpdb->prefix . 'crw_settings';
        $status = intval($_POST['status']);
        $wpdb->update(
            $table_name,
            array(
                'status' => $status
            ),
            array( 'id' => 1 )
        );

        // Handle request then generate response using echo or leaving PHP and using HTML
        wp_redirect( $_SERVER['HTTP_REFERER'] );
        exit();

    }
    function CRWProcessAjaxLoadScripts() {
        // load our jquery file that sends the $.post request
        wp_enqueue_script( "process-ajax", plugin_dir_url( __FILE__ ) . '/process-ajax.js', array( 'jquery' ) );
        // make the ajaxurl var available to the above script
        wp_localize_script( 'process-ajax', 'the_ajax_script', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'admin_url' => admin_url() ) );

    }
    add_action('wp_print_scripts', 'CRWProcessAjaxLoadScripts');


    function CRWTextAjaxProcessRequest() {
        // first check if data is being sent and that it is the data we want
        if ( isset( $_POST["post_var"] ) ) {
            // now set our response var equal to that of the POST var (this will need to be sanitized based on what you're doing with with it)
            $response = sanitize_text_field( $_POST["post_var"] );
            // send the response back to the front end

            if(!empty($response)){


                global $wpdb;
                $table_name = $wpdb->prefix . 'crw_settings';

                $wpdb->update(
                    $table_name,
                    array(
                        'msg_note' => $response
                    ),
                    array( 'id' => 1 )
                );

                echo "1";
            }
            else{
                echo "0";
            }


            die();
        }
    }
    add_action('wp_ajax_process_response', 'CRWTextAjaxProcessRequest');


    add_action( 'admin_menu', 'CRWPluginMenu' );

    /** Step 1. */
    function CRWPluginMenu() {
        add_options_page( 'CRW Options', 'Category Restriction WooCommerce', 'manage_options', 'crw10500', 'CRWPluginOptions' );
    }

    /** Step 3. */
    function CRWPluginOptions() {
        if ( !current_user_can( 'manage_options' ) )  {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'crw_settings';
        $setting = $wpdb->get_row("SELECT * FROM $table_name");

        echo '<h3>Category Restriction WooCommerce</h3>';
        $formHtml = "";

        $formHtml .= '<div style="text-align: center;">
        <form action="'.admin_url( 'admin.php' ).'" method="post">
        <input type="hidden" name="action" value="crw10500" />';

        if($setting->status == 1){
            $formHtml .= '<select name="status" id="status">
        <option selected="selected" value="1">Enable</option>
        <option value="0">Disable</option>
        </select>';
        }
        else{
            $formHtml .= '<select name="status" id="status">
        <option value="1">Enable</option>
        <option selected="selected" value="0">Disable</option>
        </select>';
        }


        $formHtml .= '<input type="submit" style="cursor: pointer;" value="Submit">
        </form>
        </div>';

        echo $formHtml;

        $Html = '';

        $Html .= '<div id="settingcontainer" style="text-align: center;">
        <form action="javascript:" id="catForm">';
        $Html .= '<h4>This message is display in disable categories</h4>';
        if(empty($setting->msg_note)){
            $Html .= '<textarea id="msgNote" style="width: 400px;
height: 200px;
border: 1px solid;" placeholder="This is message box add custom message for disable categories..."></textarea>';
        }
        else{
            $Html .= '<textarea style="width: 400px;
height: 200px;
border: 1px solid;" id="msgNote">'.esc_html($setting->msg_note).'</textarea>';
        }

        $Html .= '<div style="margin: 20px 0;"><input style="background-color: #000; color: #FFFFFF; cursor: pointer;" type="submit" name="submit" value="Update" /></div>';

        $Html .= '</form></div>';

        if($setting->status == 1){
            echo $Html;
        }


    }



    global $wpdb;
    $table_name = $wpdb->prefix . 'crw_settings';
    $setting = $wpdb->get_row("SELECT * FROM $table_name");

    if(isset($setting->status) && $setting->status ==1){

        add_action('woocommerce_before_single_product','CRWRemoveAddToCartIfProductFromDiffCat');

        if(!function_exists('CRWRemoveAddToCartIfProductFromDiffCat')){
            function CRWRemoveAddToCartIfProductFromDiffCat($status){



                $flag = true;
                global $post;
                $prod_terms = get_the_terms( $post->ID, 'product_cat' );
                $current_cat_Arr = array();
                $current_cat_counter = 0;
                foreach ($prod_terms as $prod_term) {
                    $product_cat_id = $prod_term->term_id;
                    $current_cat_Arr[$current_cat_counter] = $product_cat_id;
                    $current_cat_counter++;
                }

                global $woocommerce;
                $items = $woocommerce->cart->get_cart();
                if(empty($items)) {
                    $flag = false;
                }
                else{
                    $cartcat_Arr = array();
                    $cart_cat_counter = 0;
                    foreach ($items as $item ) {
                        $product = $item['data'];
                        $terms = get_the_terms( $product->id, 'product_cat' );
                        foreach ($terms as $aterm)
                        {
                            $cartcat_Arr[$cart_cat_counter] = $aterm->term_id;
                            $cart_cat_counter++;

                        }

                    }

                     if(count(array_intersect($current_cat_Arr,$cartcat_Arr)) == 0 && $flag){

                        add_action('woocommerce_before_single_product_summary','CRWDisplayErrorMessage');
                        // remove add to cart button
                        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);


                    }
                }

            }

        }

        if(!function_exists('CRWDisplayErrorMessage')){
            function CRWDisplayErrorMessage(){
                global $wpdb;
                $table_name = $wpdb->prefix . 'crw_settings';
                $setting = $wpdb->get_row("SELECT * FROM $table_name");
                if(empty($setting->msg_note)){
                    echo do_shortcode('<div class="woocommerce-message">Sorry you are not alow to order in this category.</div>');
                }
                else{
                    echo do_shortcode('<div class="woocommerce-message">'.esc_html($setting->msg_note).'</div>');
                }
            }

        }

        if(!function_exists('CRWDisplayErrorMessageShop')){
            function CRWDisplayErrorMessageShop(){
                global $wpdb;
                $table_name = $wpdb->prefix . 'crw_settings';
                $setting = $wpdb->get_row("SELECT * FROM $table_name");
                if(empty($setting->msg_note)){
                    echo do_shortcode('<div style="font-size:12px; border: 1px solid; color: red; text-align: center;">Sorry you are not alow to order in this category.</div>');
                }
                else{
                    echo do_shortcode('<div style="font-size:12px; border: 1px solid; color: red; text-align: center;">'.esc_html($setting->msg_note).'</div>');
                }
            }

        }

        

        add_action( 'woocommerce_after_shop_loop_item', 'CRWRemoveAddToCartButtons', 1 );

        if(!function_exists('CRWRemoveAddToCartButtons')){

            function CRWRemoveAddToCartButtons($status) {



                global $post;
                $terms = wp_get_post_terms( $post->ID, 'product_cat' );
                $termCounter = 0;
                $categories = array();
                foreach ( $terms as $term ){
                    $categories[$termCounter] = $term->term_id;
                    $termCounter++;
                }


                global $woocommerce;
                $items = $woocommerce->cart->get_cart();

                if(empty($items)) {
                    $flag = false;
                }
                else{
                    $cartcat_Arr = array();
                    $cart_cat_counter = 0;
                    foreach ($items as $item ) {
                        $product = $item['data'];
                        $terms = get_the_terms( $product->id, 'product_cat' );
                        foreach ($terms as $aterm)
                        {
                            $cartcat_Arr[$cart_cat_counter] = $aterm->term_id;
                            $cart_cat_counter++;

                        }

                    }
                    if ( count ( array_intersect($categories, $cartcat_Arr) ) == 0 ) {

                        remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart' );
                        add_action('woocommerce_after_shop_loop_item','CRWDisplayErrorMessageShop');

                    }
                    else{
                        remove_action( 'woocommerce_after_shop_loop_item', 'CRWDisplayErrorMessageShop' );
                        add_action('woocommerce_after_shop_loop_item','woocommerce_template_loop_add_to_cart');
                    }


                }




            }

        }

    }


}
