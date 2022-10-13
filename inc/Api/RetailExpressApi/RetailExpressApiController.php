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
            
            // add_action('init', array($this, 'create_woo_products'), 110);
            add_action("wp_ajax_create_woo_products", array($this, 'create_woo_products'));
            add_action("wp_ajax_nopriv_create_woo_products", array($this, 'no_access'));

        }else{

            $access_token = '';

            if($this->url != null && $this->apiKey != null){
                $access_token = $this->auth_token($this->url, $this->apiKey)->access_token;
            }

            if($access_token != ''){
                $this->set_cookies($access_token); 
            }

        }
    }

    public function auth_token($url, $apiKey) {

        $url = $url . 'v2/auth/token';
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

    public function set_cookies($value) {
        setcookie('auth_key', $value, time() + 3600, "/", "", false, false);
    }


    public function get_products ( $page = 1 ) {

        $page_size = 250;
        $inventory = true;


        $url    = $this->url;
        $apiKey = $this->apiKey;
        $auth   = $_COOKIE['auth_key'];
        

        $url = $url . "v2/products?page_number=$page&page_size=$page_size&include_inventory=true&supplie=true&include_zero_stock=false&sales_channel_id=1";

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
        $products = $this->get_products(1);
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
    public function no_access() {
        echo "Tricky bastard, get the fuck out!";
        die;
    }

    public function create_woo_products( ) {
        
        $products = $this->map_products_info();

        foreach ($products as $key => $product) {
        
            if( count( $product['parent'] ) > 0 ){

                // echo $product['product_name'] . " has been successfully updated!";
                echo "<p>" . $product['product_name'] . " variable product has been successfully added to the database. </p>";
            }else{ 
                
                // Check if product is existing!

                $check = wc_get_product_id_by_sku($product['sku']);

                if( !$check ) { 

                    $post_id = wp_insert_post( 
                        array( 
                            'post_title' => $product['product_name'],
                            'post_status' => 'publish',
                            'post_type' => 'product'
                        )
                    );
        
                    wp_set_object_terms( $post_id, 'simple', 'product_type' );
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


                }else{
                    
                }
                echo "<p>" . $product['product_name'] . " product has been successfully added to the database. </p>";
            }

        }

        wp_die();
    }

}
