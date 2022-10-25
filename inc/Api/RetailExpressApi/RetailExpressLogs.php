<?php
/**
* @package REXpressPlugin
*/

namespace Inc\Api\RetailExpressApi;

class RetailExpressLogs
{
    

    // public $logs = [];
    
    public function register() 
    {
        // print_r($this->logs);
    }

    function create_log_file($logs){


        $file = fopen( $this->plugin_path ."logs/". time(). '_logs.csv', 'a');

        foreach($logs as $key => $type){

            foreach($type as $log){
                echo fwrite($file, "Hello World. Testing!" . "\n");
            }
        }


        fclose($file);


    }
    
}