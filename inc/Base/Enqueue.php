<?php
/**
* @package REXpressPlugin
*/

namespace Inc\Base;

use \Inc\Base\BaseController;

class Enqueue extends BaseController
{
    public function register() {
         add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
    }   

    function enqueue() {
        wp_enqueue_style('REXstyle',  $this->plugin_url . 'assets/style.css', '', $this->mix_get_random() );
        wp_enqueue_script('REXscript', $this->plugin_url . 'assets/script.js', array( 'jquery' ), $this->mix_get_random() );
        wp_localize_script( 'REXscript', 'frontend_ajax_object',
		array( 
			'ajaxurl' => admin_url( 'admin-ajax.php' )
		)
	);
    }


    function mix_get_random() {
        $randomizr = rand(000, 999);
        return $randomizr;
    }


}