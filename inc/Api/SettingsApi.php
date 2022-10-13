<?php
/**
* @package REXpressPlugin
*/

namespace Inc\Api;

class SettingsApi
{
    public $admin_pages = array();
    public $admin_subpages = array();
    
    public function register() {
        if ( ! empty($this->admin_pages ) ){
            add_action( 'admin_menu', array ( $this, 'addAdminMenu' ) );
        
            // Activate Custom Settings
            add_action( 'admin_init', array( $this, 'rexpressCustomSettings' ));
        }

    }

    // ---------------------------------------------
    public function rexpressCustomSettings() {

        register_setting('rex-settings-group', 'rex__api_url' );

        register_setting('rex-settings-group', 'rex__api_api' );

        add_settings_section( 'rex-settings-options', '', array( $this, 'rex_options' ), 'rexpress_settings' );

        add_settings_field( 'rex-api-url', 'API URL', array( $this, 'rex__subscriber_api_url' ), 'rexpress_settings','rex-settings-options' );

        add_settings_field( 'rex-api', 'Subscriber API', array( $this, 'rex__subscriber_api' ), 'rexpress_settings','rex-settings-options' );

    }

    public function rex_options() {
        echo '';
    }

    public function rex__subscriber_api_url() {
        $apiUrl = esc_attr(get_option( 'rex__api_url' )); 
        echo '<input type="text" name="rex__api_url"  value="'. $apiUrl .'" placeholder="https://example.com" />';
    }

    public function rex__subscriber_api() {
        $apiKey = esc_attr(get_option( 'rex__api_api' )); 
        echo '<input type="text" name="rex__api_api"  value="'. $apiKey .'" placeholder="" />';
    }

    // ---------------------------------------------------

    public function addPages( array $pages ) {

        $this->admin_pages = $pages; 

        return $this;
    
    }

    public function withSubPage( string $title = null ) {

        if ( empty( $this->admin_pages ) ) {
            return $this;
        }

        $admin_page = $this->admin_pages[0];

        $subpage = array(
            array(
                'parent_slug' => $admin_page['menu_slug'],
                'page_title' => $admin_page['page_title'],
                'menu_title' => ($title) ? $title : $admin_page['menu_title'],
                'capability' => $admin_page['capability'],
                'menu_slug' => $admin_page['menu_slug'],
                'callback' => $admin_page['callback'],
            )
        );

        $this->admin_subpages = $subpage;
        
        return $this; 
    }

    public function addSubpages( array $pages ) {
        $this->admin_subpages = array_merge( $this->admin_subpages, $pages );

        return $this;
    }

    public function addAdminMenu() {
        foreach ( $this->admin_pages as $page ) {
            add_menu_page(
                $page['page_title'],
                $page['menu_title'],
                $page['capability'],
                $page['menu_slug'],
                $page['callback'],
                $page['icon_url'],
                $page['position']
            );
        }

        foreach ( $this->admin_subpages as $page ) {
            add_submenu_page(
                $page['parent_slug'],
                $page['page_title'],
                $page['menu_title'],
                $page['capability'],
                $page['menu_slug'],
                $page['callback']
            );
        }
        
    }



}