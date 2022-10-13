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
            
            $data = $this->getProducts($this->url, $this->apiKey, $_COOKIE['auth_key']);
            
            $limit = 1;
            
            
            $arr = [];

            for ($i=1; $i <= $limit ; $i++) { 
                $data = $this->getProducts($this->url, $this->apiKey, $_COOKIE['auth_key'], $i)->data;

                array_push($arr, $data);
            }
            
            die;
        }else{
            $this->set_cookies($this->auth_token($this->url, $this->apiKey)->access_token); 
        }
    }

    public function auth_token(string $url, string $apiKey) {

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

    public function getProducts(string $url, string $apiKey, string $auth, $page = 1) {

        $page_size = 250;
        $inventory = true;

        $url = $url . "v2/products?page_number=$page&page_size=$page_size&include_inventory=true";

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

    public function set_cookies($value) {
        setcookie('auth_key', $value, time() + (86400 * 30), "/"); 
    }
}
