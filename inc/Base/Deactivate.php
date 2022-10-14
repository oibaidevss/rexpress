<?php
/**
* @package REXpressPlugin
*/

namespace Inc\Base;

class Deactivate
{
    public static function deactivate(){
        flush_rewrite_rules();
        unset($_COOKIE['auth_key']);
    }   
}
