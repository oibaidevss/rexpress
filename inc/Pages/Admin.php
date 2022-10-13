<?php
/**
* @package REXpressPlugin
*/

namespace Inc\Pages;

use \Inc\Api\SettingsApi;
use \Inc\Base\BaseController;
use \Inc\Api\Callbacks\AdminCallbacks;

class Admin extends BaseController
{
    public $settings;

    public $callbacks;

    public $pages = array();

    public $subpages = array();

    public function register() {

        $this->settings = new SettingsApi();

        $this->callbacks = new AdminCallbacks();

        $this->setPages();

        $this->setSubpages();


        $this->settings
            ->addPages( $this->pages )
            ->withSubPage( 'Dashboard' )
            ->addSubPages( $this->subpages )
            ->register();
    }

    public function setPages() {
        $this->pages = array(
            array(
                'page_title' => 'REXpress Plugin',
                'menu_title' => 'REXpress',
                'capability' => 'manage_options',
                'menu_slug' => 'rexpress_plugin',
                'callback' => array( $this->callbacks, 'adminDashboard'),
                'icon_url' => 'dashicons-controls-repeat',
                'position' => 110,
            )
        );
    }

    public function setSubpages() {
        $this->subpages = array(
            array(
                'parent_slug' => 'rexpress_plugin',
                'page_title' => 'Settings',
                'menu_title' => 'Settings',
                'capability' => 'manage_options',
                'menu_slug' => 'rexpress_settings',
                'callback' => array( $this->callbacks, 'adminSettings'),
            )
        );
    }

}
