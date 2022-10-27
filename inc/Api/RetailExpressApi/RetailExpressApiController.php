<?php
/**
* @package REXpressPlugin
*/

namespace Inc\Api\RetailExpressApi;

use \Inc\Base\BaseController;
use \Inc\Api\RetailExpressApi\RetailExpressLogs;
use Carbon\Carbon;

class RetailExpressApiController extends BaseController 
{

    private $url;
    private $apiKey;
    private $time;
    private $logs    = [];
    private $checker = [];

    protected $count = 0; 

    function register(){
        
        $this->time = Carbon::now()->toDateTimeString();

        $this->url    = esc_attr(get_option( 'rex__api_url' ));
        $this->apiKey = esc_attr(get_option( 'rex__api_api' ));

        if ( isset( $_GET['settings-updated'] ) && $_GET['page'] == 'rexpress_settings') {
                
            add_action( 'init', array($this, 'unset_cookies') ); // Unset Cookies

        }
        
        if(isset($_COOKIE['auth_key'])) {
            
            add_action("wp_ajax_create_woo_products", array($this, 'create_woo_products'));
            add_action("wp_ajax_nopriv_create_woo_products", array($this, 'no_access'));
            
            if($this->url == null || $this->apiKey == null){
                add_action( 'init', array($this, 'unset_cookies') );
            }
        }else{


            $get_access_token = $this->auth_token($this->url, $this->apiKey);
    
            if($this->auth_token($this->url, $this->apiKey) != NULL){        
                $this->set_cookies($get_access_token->access_token, $get_access_token->expires_on);            
            }
            
            if($this->auth_token($this->url, $this->apiKey) == NULL && $_GET['page'] == 'rexpress_settings'){
                add_action('admin_notices', function(){
                    echo '<div class="notice notice-error is-dismissible">';
                        echo "<p><strong>Error!</strong> you've entered an invalid URL or API key!</p>";
                    echo '</div>';
                });
            }

        }
    }

   

    // API Request
    public function auth_token($url, $apiKey) {

        $forwardSlash = substr($url, strlen($url)-1);

        $slash = $forwardSlash === "/" ? "":"/";

        $url = $url . $slash . 'v2/auth/token';

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
    public function get_products( $page = 1 ) {

        $page_size = 250;
        
        $inventory = true;

        $page = isset($_REQUEST['page_number']) ? $_REQUEST['page_number'] : $page;

        $url    = $this->url;
        
        $apiKey = $this->apiKey;
        
        $auth   = $_COOKIE['auth_key'];
        
        $forwardSlash = substr($url, strlen($url)-1);

        $slash = $forwardSlash === "/" ? "":"/";

        $url = $url . $slash . "v2/products?outlet_id=1&page_number=$page&include_inventory=true&page_size=$page_size&sales_channel_id=1";

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

    public function get_all_products( $type ) {
        
        $temp = [];

        $compiled = [];
        
        $products = $this->get_products(1);

        $info = [ 'total_records' => $products->total_records, 'page_size' => $products->page_size ];

        $limit = ceil($info['total_records'] / $info['page_size']);

        for ( $i = 1; $i <= $limit ; $i++ ) { 
            $products = $this->get_products($i);
            $temp[$i] = $products->data;
        }
        
        $count = 0;
        if( $type === "simple" ){
            foreach($temp as $temporary){
                foreach($temporary as $key => $product){
                    if ( count($product->custom_properties) == 0 ) {
                        $compiled[$count] = [
                            'name' => $product->short_description,
                            'category' => $product->product_type->name,
                            'stock' => $product->inventory[0]->quantity_available,
                            'sku' => str_replace("-", " ", $product->supplier_sku),
                            'price' => $product->sell_price_inc
                        ];
                        $count++;
                    }
                }
            }
        }
        
        if( $type === "variable" ){

            $variations = []; 
            $variables  = [];
    
            $temp_variable = [];
            $temp_variations = [];

            foreach ($temp as $key => $temporary) {
                foreach ($temporary as $key => $variation) {
                    if ( count($variation->custom_properties)  != 0 ) {
                        $variations[] = array(
                            'key' => $key,
                            'name' => $variation->short_description,
                            'category' => $variation->product_type->name,
                            'sku'   => str_replace("-", " ", $variation->supplier_sku),
                            'price' => $variation->sell_price_inc,
                            'stock' => $variation->inventory[0]->quantity_available,
                            'modified_on' => strtotime($variation->modified_on),
                            'parent' => $variation->custom_properties[0]->value,
                            'attributes'    => array(
                                'size' => $variation->short_description,
                            ),
                        );
                    }
                }
            }

            $arr_variations = array();
            foreach ($variations as $key => $variation)
            {
                $arr_variations[$key] = $variation['parent'];
            }
            array_multisort($arr_variations, SORT_DESC, $variations);

            foreach ($temp as $key => $temporary) {
                foreach ($temporary as $key => $variable) {
                    if ( count($variable->custom_properties)  != 0 ) {
                        if( !in_array( $variable->custom_properties[0]->value, $temp_variable ) ) {
                        
                            $temp_variable[] = $variable->custom_properties[0]->value;
                            
                            $compiled[$key] = [
                                'parent' => $variable->custom_properties[0]->value,
                                'name' => $variable->short_description,
                                'description' => $variable->short_description,
                                'sku' => str_replace("-", " ", $variable->custom_properties[0]->value),
                                'categories' => $variable->product_type->name,
                            ];
        
                            foreach ($variations as $variation_key => $variation) {
                                if( $compiled[$key]['parent'] === $variation['parent'] ){
                                    $temp_variations[] = $variation;
                                }
                            }
        
                            $compiled[$key]['variations'] = $temp_variations;
                            
                            $temp_variations = [];
                        }
                    }
                }
            }
        }

        return $compiled;

    }

    // For Ajax Request
    public function no_access() {
        echo "Please login to gain access.";
        die;
    }

    // For Ajax Request
    public function get_total_records() {
        $products = $this->get_products();
        $arr = [ 'total_records' => $products->total_records, 'page_size' => $products->page_size ];
    
        echo json_encode($arr);
        die;
    }

    // For Ajax Request
    public function create_woo_products( ) {
        
        update_option( 'rex_update_last_updated', date('m/d/Y h:i:s a', time()) );

        $this->logs = [];

        $simple   = $this->create_simple_products();
        $variable = $this->create_variable_products();

        echo json_encode($this->logs);
        
        die;
    }

    // Simple Products 
    public function create_simple_products() {
        
        $products = $this->get_all_products( 'simple' );
       
        $simple = [];

        foreach ($products as $key => $product) {
            $check = wc_get_product_id_by_sku($product['sku']);

            if( $check == 0 ) {
                    
                $args = array( 
                    'post_title' => $product['name'],
                    'post_status' => 'publish',
                    'post_type' => 'product'
                );
                
                $post_id = wp_insert_post( $args );
                
                wp_set_object_terms( $post_id, 'simple', 'product_type' );
                wp_set_object_terms( $post_id, $product_data['categories'], 'product_cat'); // Set up its categories
                
                update_post_meta( $post_id, '_regular_price', $product['price'] );
                update_post_meta( $post_id, '_visibility', 'visible' );
                update_post_meta( $post_id, '_stock_status', 'instock');
                update_post_meta( $post_id, 'total_sales', '0' );
                update_post_meta( $post_id, '_downloadable', 'no' );
                update_post_meta( $post_id, '_virtual', 'yes' );
                update_post_meta( $post_id, '_featublue', 'no' );
                update_post_meta( $post_id, '_sku', $product['sku'] );
                update_post_meta( $post_id, '_price', $product['price'] );
                update_post_meta( $post_id, '_manage_stock', 'yes' ); 
                update_post_meta( $post_id, '_backorders', 'no' );
    
                wc_update_product_stock($post_id, $product['stock'] < 0 ? 0:$product['stock'], 'set');
                
                $simple[$key] = [
                    'name' => $product['name'],
                    'sku' => $product['sku'],
                    'type' => 'created'
                ];

            }else{
                
                $post_id = $check;
                $post = wc_get_product( $post_id );

                if( ($post->get_stock_quantity() != $product['stock'] && $product['stock'] >= 0) || $post->get_price() != $product['price'] ) 
                {
                
                    update_post_meta( $post_id, '_price', $product['price'] );
                    update_post_meta( $post_id, '_regular_price', $product['price'] );
                    update_post_meta( $post_id, '_sale_price', '' );
                    update_post_meta( $post_id, '_updated_at', $this->time );

                    wc_update_product_stock($post_id, $product['stock'] < 0 ? 0:$product['stock'], 'set');
                    
                    $simple[$key] = [
                        'name' => $product['name'],
                        'sku' => $product['sku'],
                        'stock' => $post->get_stock_quantity() . " == " . $product['stock'],
                        'price' => $post->get_price() . " == " . $product['price'],
                        'type' => 'updated'
                    ];
                }
                

            }
        }

        $this->logs['simple'] = $simple;
    }

    // Variable Products
    public function create_variable_products() {

        $products = $this->get_all_products( 'variable' );
        
        $variable = [];

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
                
                add_post_meta( $post_id, '_updated_at', $this->time, true );

                update_post_meta($post_id, '_sku', $product_data['sku']); // Set its SKU

                update_post_meta( $post_id,'_visibility','visible'); // Set the product to visible, if not it won't show on the front end

                wp_set_object_terms( $post_id, $product_data['categories'], 'product_cat'); // Set up its categories
                wp_set_object_terms($post_id, 'variable', 'product_type'); // Set it to a variable product type
            
                $this->insert_product_variations($post_id, $product_data['variations']); // Insert variations passing the new post id & variations
                $this->insert_product_attributes($post_id, array( "size" ), $product_data['variations']); // Add attributes passing the new post id, attributes & variations
                
                $variable[$key] = [
                    'name' => $product_data['name'],
                    'sku' => $product_data['sku'],
                    'type' => 'created',
                ];

            }else{

                $post_id = $check;
                
                $variations = $this->insert_product_variations($post_id, $product_data['variations']); // Insert variations passing the new post id & variations
                $attributes = $this->insert_product_attributes($post_id, array( "size" ), $product_data['variations']); // Add attributes passing the new post id, attributes & variations
                if(!empty($attributes) || !empty($variations)){
                    update_post_meta( $post_id, '_updated_at', $this->time );
                    
                    $variable[$key] = [
                        'name' => $product_data['name'],
                        'sku'  => $product_data['sku'],
                        'type' => 'updated',
                    ];  
                } 
            }
        }
        
        $this->logs['variable'] = $variable;
    
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
        $arr = [];
        foreach ($variations as $index => $variation)
        {
            $check = wc_get_product_id_by_sku($variation['sku']);

            if( $check == 0 ) {

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

                update_post_meta($variation_post_id, '_sku', $variation['sku']); // Set its SKU

                update_post_meta($variation_post_id, '_price', $variation['price']);
                update_post_meta($variation_post_id, '_regular_price', $variation['price']);

                add_post_meta( $post_id, '_updated_at', $this->time, true );

                $arr[$index] = [
                    'name' => $variation['name'],
                    'sku'  => $variation['sku'],
                    'type' => 'created',
                ];  

            } else {

                $post_id = $check;
                $product = wc_get_product( $post_id );

                foreach ($variation['attributes'] as $attribute => $value) // Loop through the variations attributes
                {
                    $attribute_term = get_term_by('name', $value, 'pa_'.$attribute); // We need to insert the slug not the name into the variation post meta
                    update_post_meta($post_id, 'attribute_pa_'.$attribute, $attribute_term->slug);
                }
                
                if( ($product->get_stock_quantity() != $variation['stock'] && $variation['stock'] >= 0 )|| $product->get_price() != $variation['price'] ) {

                    
                    wc_update_product_stock($post_id, $variation['stock'], 'set');
                    
                    update_post_meta( $post_id, '_regular_price', $variation['price']);
                    
                    update_post_meta( $post_id, '_price', $variation['price'] );
                    
                    update_post_meta( $post_id, '_updated_at', $this->time );

                    $arr[$index] = [
                        'name' => $variation['name'],
                        'sku' => $variation['sku'],
                        'type' => 'updated',
                    ];  
                }
                
                
            }
        }

        $this->logs['variations'] = $arr;
    }

    // Handle Cookies
    public function set_cookies($value, $expiry) {
        setcookie('auth_key', $value, strtotime($expiry), "/", "", false, false);
    }

    public function unset_cookies() { 
        unset($_COOKIE['auth_key']); 
        setcookie('auth_key', null, -1, '/'); 
    }

}
