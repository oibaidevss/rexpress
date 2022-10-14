<?php
/**
* @package REXpressPlugin
*/

namespace Inc\Api\RetailExpressApi;

use \Inc\Base\BaseController;

class RetailExpressApiController 
{

    public $url;
    public $apiKey;
    
    function register(){
        $this->url    = esc_attr(get_option( 'rex__api_url' ));
        $this->apiKey = esc_attr(get_option( 'rex__api_api' ));
        
        if(isset($_COOKIE['auth_key'])) {
            
            add_action("wp_ajax_create_woo_products", array($this, 'create_woo_products'));
            add_action("wp_ajax_nopriv_create_woo_products", array($this, 'no_access'));
            
            add_action("wp_ajax_get_total_records", array($this, 'get_total_records'));
            add_action("wp_ajax_nopriv_get_total_records", array($this, 'no_access'));
            
            if($this->url == null || $this->apiKey == null){
                add_action( 'init', array($this, 'unset_cookies') );
            }
            
        }else{
            
            $access_token = '';

            if($this->url != null && $this->apiKey != null){
                $access_token = $this->auth_token($this->url, $this->apiKey)->access_token;
            }else{
                add_action( 'init', array($this, 'unset_cookies') );
            }

            if($access_token != ''){
                $cookies = $this->set_cookies($access_token);
                print_r($cookies);
            }

        }
    }

    // API Request
    public function auth_token($url, $apiKey) {

        $forwardSlash = substr($url, strlen($url)-1);

        if($forwardSlash !== '/'){
            $forwardSlash = '/';
        }

        $url = $url . $forwardSlash . 'v2/auth/token';
        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        # Request headers
        $headers = array(
            'Cache-Control: no-cache',
            "x-api-key: $apiKey",
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $resp = curl_exec($curl);
        curl_close($curl);
        
        return json_decode($resp);

    }

    // API Request
    public function get_products() {

        $page_size = 250;
        
        $inventory = true;

        $page = isset($_REQUEST['page_number']) ? $_REQUEST['page_number'] : 1;

        $url    = $this->url;
        
        $apiKey = $this->apiKey;
        
        $auth   = $_COOKIE['auth_key'];
        
        $forwardSlash = substr($url, strlen($url)-1);

        if($forwardSlash !== '/'){
            $forwardSlash = '/';
        }

        $url = $url . $forwardSlash . "v2/products?outlet_id=1&page_number=$page&include_inventory=true&page_size=$page_size&sales_channel_id=1";

        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        # Request headers
        $headers = array(
            'Cache-Control: no-cache',
            "x-api-key: $apiKey",
            "Authorization: Bearer $auth",
        );

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $resp = curl_exec($curl);
        curl_close($curl);
      

        return json_decode($resp);
        
    }

    public function map_products_info(){
       
        $products = $this->get_products();
        $total_records = $products->total_records;

        $limit = $total_records / 250;
        
        $arr = [];
        
        foreach ($products->data as $key => $product) {

            $arr[$key]['product_name'] = $product->short_description; 

            $arr[$key]['product_category'] = $product->product_type->name; 

            $arr[$key]['stock'] = $product->inventory[0]->quantity_available;

            $arr[$key]['sku'] = $product->supplier_sku;

            $arr[$key]['price'] = $product->sell_price_inc;

            $arr[$key]['parent'] = $product->custom_properties;

        }


        return $arr;
    }

    // For Ajax Request
    public function no_access() {
        echo "Please login to gain access.";
        die;
    }

    // For Ajax Request
    public function get_total_records() {
        $products = $this->get_products();
        $total_records = $products->total_records;
    
        echo $total_records;
        die;
    }

    // For Ajax Request
    public function create_woo_products( ) {
        
        $products = $this->map_products_info();

        foreach ($products as $key => $product) {
            
            $check = wc_get_product_id_by_sku($product['sku']);
            
            if( count( $product['parent'] ) == 0 ){
                
                // Check if product is existing!
                if( $check == 0 ) {
                    
                    $args = array( 
                        'post_title' => $product['product_name'],
                        'post_status' => 'publish',
                        'post_type' => 'product'
                    );

                    $post_id = wp_insert_post( $args );
                        
                    wc_update_product_stock($post_id, $product['stock'], 'set');
                    wp_set_object_terms( $post_id, 'simple', 'product_type' );
                    
                    update_post_meta( $post_id, '_regular_price', '' );

                    update_post_meta( $post_id, '_visibility', 'visible' );
                    update_post_meta( $post_id, '_stock_status', 'instock');
                    update_post_meta( $post_id, 'total_sales', '0' );
                    update_post_meta( $post_id, '_downloadable', 'no' );
                    update_post_meta( $post_id, '_virtual', 'yes' );
                    update_post_meta( $post_id, '_sale_price', '' );
            
                    update_post_meta( $post_id, '_purchase_note', '' );
                    update_post_meta( $post_id, '_featured', 'no' );
            
                    update_post_meta( $post_id, '_weight', '' );
                    update_post_meta( $post_id, '_length', '' );
                    update_post_meta( $post_id, '_width', '' );
                    update_post_meta( $post_id, '_height', '' );
                    update_post_meta( $post_id, '_sku', $product['sku'] );
            
                    update_post_meta( $post_id, '_product_attributes', array() );
                    update_post_meta( $post_id, '_sale_price_dates_from', '' );
                    update_post_meta( $post_id, '_sale_price_dates_to', '' );
                    update_post_meta( $post_id, '_price', $product['price'] );
                    update_post_meta( $post_id, '_sold_individually', '' );
                    update_post_meta( $post_id, '_manage_stock', 'yes' ); 
                    update_post_meta( $post_id, '_backorders', 'no' );

                    
                    echo "<p>" . $product['product_name'] . " product has been successfully added to the database. </p>";

                }else{
                    
                    $post_id = $check;

                }
            }

        }

        wp_die();
    }


    // Simple Products 
    public function create_simple_products() {

    }

    public function create_variable_products() {

        $post_id = wp_insert_post( 
            array( 
                'post_title' => $product['product_name'],
                'post_status' => 'publish',
                'post_type'   => 'product_variation',
                'post_parent' => $product['parent']
            )
        );

        wp_set_object_terms( $post_id, 'variable', 'product_type' );

        update_post_meta( $post_id, '_visibility', 'visible' );
        update_post_meta( $post_id, '_stock_status', 'instock');
        update_post_meta( $post_id, 'total_sales', '0' );
        update_post_meta( $post_id, '_downloadable', 'no' );
        update_post_meta( $post_id, '_virtual', 'yes' );
        update_post_meta( $post_id, '_regular_price', $product['price'] );
        update_post_meta( $post_id, '_sale_price', '' );
        
        update_post_meta( $post_id, '_purchase_note', '' );
        update_post_meta( $post_id, '_featured', 'no' );

        update_post_meta( $post_id, '_weight', '' );
        update_post_meta( $post_id, '_length', '' );
        update_post_meta( $post_id, '_width', '' );
        update_post_meta( $post_id, '_height', '' );
        update_post_meta( $post_id, '_sku', $product['sku'] );

        update_post_meta( $post_id, '_product_attributes', array() );
        update_post_meta( $post_id, '_sale_price_dates_from', '' );
        update_post_meta( $post_id, '_sale_price_dates_to', '' );
        update_post_meta( $post_id, '_price', '' );
        update_post_meta( $post_id, '_sold_individually', '' );
        update_post_meta( $post_id, '_manage_stock', 'yes' ); 
        wc_update_product_stock($post_id, $product['stock'], 'set');
        update_post_meta( $post_id, '_backorders', 'no' );

        return; 
    }


    // Handle Cookies
    public function set_cookies($value) {
        setcookie('auth_key', $value, time() + 3600, "/", "", false, false);
    }

    public function unset_cookies() { 
        unset($_COOKIE['auth_key']); 
        setcookie('auth_key', null, -1, '/'); 
    }

}
