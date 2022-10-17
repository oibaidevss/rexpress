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

            // add_action( 'init', array($this, 'create_woo_products') );

            

        }else{
            
            $access_token = '';

            if($this->url != null && $this->apiKey != null){
                $access_token = $this->auth_token($this->url, $this->apiKey)->access_token;
            }else{
                add_action( 'init', array($this, 'unset_cookies') );
            }

            if($access_token != ''){
                $cookies = $this->set_cookies($access_token);
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

    public function map_products(){
       
        $products = $this->get_products();

        $simple = [];
        
        foreach ($products->data as $key => $product) {

            if ( count($product->custom_properties)  == 0 ) {

                $simple[$key]['product_name'] = $product->short_description; 
                
                $simple[$key]['product_category'] = $product->product_type->name; 
                
                $simple[$key]['stock'] = $product->inventory[0]->quantity_available;
                
                $simple[$key]['sku'] = $product->supplier_sku;
                
                $simple[$key]['price'] = $product->sell_price_inc;

            }
        
        }

        return $simple;
    }

    public function map_variable_products(){
        $products = $this->get_products();

        $variations = []; 
        $variables  = [];

        $temp_variable = [];
        $temp_variations = [];

        // variations 
        foreach ($products->data as $key => $variation) {
            if ( count($variation->custom_properties)  != 0 ) {
                $variations[] = array(
                    'key' => $key,
                    'name' => $variation->short_description,
                    'category' => $variation->product_type->name,
                    'sku'   => $variation->supplier_sku,
                    'price' => $variation->sell_price_inc,
                    'stock' => $variation->inventory[0]->quantity_available,
                    'parent' => $variation->custom_properties[0]->value,
                    'attributes'    => array(
                        'size' => $variation->short_description,
                        $variation->attributes => $variation->attribute->name,
                    ),
                );
            }
        }

        $arr_variations = array();
        foreach ($variations as $key => $variation)
        {
            $arr_variations[$key] = $variation['parent'];
        }
        array_multisort($arr_variations, SORT_DESC, $variations);
        
        // variables
        foreach ($products->data as $key => $variable) {
            if ( count($variable->custom_properties)  != 0 ) {
                // ? Check if the SKU is not yet added in the $temp Array.
                if( !in_array( $variable->custom_properties[0]->value, $temp_variable ) ) {
                   
                    $temp_variable[] = $variable->custom_properties[0]->value;

                    $variables[$key]['parent'] = $variable->custom_properties[0]->value;
                    $variables[$key]['name'] = $variable->short_description;
                    $variables[$key]['description'] = $variable->short_description;
                    $variables[$key]['sku'] = $variable->custom_properties[0]->value;
                    $variables[$key]['categories'] = "";    

                    foreach ($variations as $variation_key => $variation) {
                        if( $variables[$key]['parent'] === $variation['parent'] ){
                            $temp_variations[] = $variation;
                        }
                    }

                    $variables[$key]['variations'] = $temp_variations;
                    
                    $temp_variations = [];
                }
            }
        }

        // return $variations;

        return $variables;
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
        
        // Create Simple Products    
        $this->create_simple_products();

        // Create Variable Parent Products
        $this->create_variable_products();
        
        wp_die();
    }


    // Simple Products 
    public function create_simple_products() {
        
        $products = $this->map_products();

        foreach ($products as $key => $product) {
            $check = wc_get_product_id_by_sku($product['sku']);
            
            if( $check == 0 ) {
                    
                $args = array( 
                    'post_title' => $product['product_name'],
                    'post_status' => 'publish',
                    'post_type' => 'product'
                );
                
                $post_id = wp_insert_post( $args );
                
                wp_set_object_terms( $post_id, 'simple', 'product_type' );
                wp_set_object_terms( $post_id, $product_data['categories'], 'product_cat'); // Set up its categories
                
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
    

                
                wc_update_product_stock($post_id, $product['stock'] < 0 ? 0:$product['stock'], 'set');
                
                echo "<p>" . $product['product_name'] . " product has been successfully added to the database. </p>";

            }else{
                
                $post_id = $check;
                update_post_meta( $post_id, '_price', $product['price'] );
                wc_update_product_stock($post_id, $product['stock'] < 0 ? 0:$product['stock'], 'set');
                echo "<p>" . $product['product_name'] . " product has been successfully updated from the database. </p>";


            }
        }
    }

    public function create_variable_products() {

        $products = $this->map_variable_products();
        // echo "<pre>"; print_r($products); echo "</pre>";
        // die;
        
        foreach ($products as $key => $product_data) {
            # code...
            $check = wc_get_product_id_by_sku($product_data['sku']);
            
            if( $check == 0 ) {

                $post = array( 
                    'post_content' => $product_data['description'],
                    'post_status'  => 'publish',
                    'post_title'   => $product_data['name'],
                    'post_parent'  => '',
                    'post_type'    => 'product'
                );
                 
                $post_id = wp_insert_post($post); // Insert the post returning the new post id
                
                update_post_meta($post_id, '_sku', $product_data['sku']); // Set its SKU
                update_post_meta( $post_id,'_visibility','visible'); // Set the product to visible, if not it won't show on the front end

                wp_set_object_terms($post_id, $product_data['categories'], 'product_cat'); // Set up its categories
                wp_set_object_terms($post_id, 'variable', 'product_type'); // Set it to a variable product type
            
                $this->insert_product_attributes($post_id, array( "size", "colour" ), $product_data['variations']); // Add attributes passing the new post id, attributes & variations
                $this->insert_product_variations($post_id, $product_data['variations']); // Insert variations passing the new post id & variations
                
                echo "<p>" . $product_data['name'] . " product variable has been successfully added to the database. </p>";
            }
        }
        
    
    }

    function insert_product_attributes ($post_id, $available_attributes, $variations)
    {

        foreach ($available_attributes as $attribute) // Go through each attribute
        {
            $values = array(); // Set up an array to store the current attributes values.
    
            foreach ($variations as $variation) // Loop each variation in the file
            {
                $attribute_keys = array_keys($variation['attributes']); // Get the keys for the current variations attributes
    
                foreach ($attribute_keys as $key) // Loop through each key
                {
                    if ($key === $attribute) // If this attributes key is the top level attribute add the value to the $values array
                    {
                        $values[] = $variation['attributes'][$key];
                    }
                }
            }

            $values = array_unique($values); // Filter out duplicate values
    
            wp_set_object_terms($post_id, $values, 'pa_' . $attribute);
        }
    
        $product_attributes_data = array(); // Setup array to hold our product attributes data
    
        foreach ($available_attributes as $attribute) // Loop round each attribute
        {
            $product_attributes_data['pa_'.$attribute] = array( // Set this attributes array to a key to using the prefix 'pa'
    
                'name'         => 'pa_'.$attribute,
                'value'        => '',
                'is_visible'   => '1',
                'is_variation' => '1',
                'is_taxonomy'  => '1'
    
            );
        }
    
        update_post_meta($post_id, '_product_attributes', $product_attributes_data); // Attach the above array to the new posts meta data key '_product_attributes'
    }
    
    function insert_product_variations ($post_id, $variations)
    {
        foreach ($variations as $index => $variation)
        {
            $variation_post = array( // Setup the post data for the variation
    
                'post_title'  => 'Variation #'.$index.' of '.count($variations).' for product#'. $post_id,
                'post_name'   => 'product-'.$post_id.'-variation-'.$index,
                'post_status' => 'publish',
                'post_parent' => $post_id,
                'post_type'   => 'product_variation',
                'guid'        => home_url() . '/?product_variation=product-' . $post_id . '-variation-' . $index
            );
    
            $variation_post_id = wp_insert_post($variation_post); // Insert the variation
            

            
            
            foreach ($variation['attributes'] as $attribute => $value) // Loop through the variations attributes
            {
                
                $attribute_term = get_term_by('name', $value, 'pa_'.$attribute); // We need to insert the slug not the name into the variation post meta
                
                update_post_meta($variation_post_id, 'attribute_pa_'.$attribute, $attribute_term->slug);
            }
    
                            
            update_post_meta( $variation_post_id, '_manage_stock', 'yes' ); 
            wc_update_product_stock($variation_post_id, $variation['stock'] < 0 ? 0:$variation['stock'], 'set');


            update_post_meta($variation_post_id, '_price', $variation['price']);
            update_post_meta($variation_post_id, '_regular_price', $variation['price']);
        }
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
