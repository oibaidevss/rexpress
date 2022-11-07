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
            add_action( 'admin_init', array( $this, 'rexpressAPISettings' ));
        }

    }

    // ---------------------------------------------
    public function rexpressAPISettings() {

        register_setting( 'rex-settings-group', 'rex__api_url' );
        register_setting( 'rex-settings-group', 'rex__api_api' );

        register_setting( 'rex-settings-group', 'rex__last_updated' );
        register_setting( 'rex-settings-group', 'rex__retail_express' );
        register_setting( 'rex-settings-group', 'rex__admin_email' );

        add_settings_section( 
            'rex-settings-options', 
            'Api Connection', 
            array( $this, 'rex_options' ), 
            'rexpress_settings'
        );

        add_settings_field( 
            'rex-api-url', 
            'API URL', 
            array( $this, 'rex__subscriber_api_url' ), 
            'rexpress_settings',
            'rex-settings-options' 
        );

        add_settings_field( 
            'rex-api', 
            'Subscriber API', 
            array( $this, 'rex__subscriber_api' ), 
            'rexpress_settings',
            'rex-settings-options' 
        );
        
        add_settings_field( 
            'rex-update-retail-express', 
            'Update Retail Express', 
            array( $this, 'rex__retail_express' ), 
            'rexpress_settings',
            'rex-settings-options'
        );
        
        add_settings_field( 
            'rex-admin-email', 
            'Email', 
            array( $this, 'rex__admin_email' ), 
            'rexpress_settings',
            'rex-settings-options'
        );

        add_settings_field( 
            'rex-update-last-updated', 
            'Last Updated', 
            array( $this, 'rex__last_updated' ), 
            'rexpress_settings',
            'rex-settings-options'
        );

    }

    public function rex_options() {
        echo '';
    }

    public function rex__subscriber_api_url() {
        $url = esc_attr(get_option( 'rex__api_url' )); 
        echo '<input type="text" name="rex__api_url"  value="'. $url .'" placeholder="https://example.com" />';
    }

    public function rex__subscriber_api() {
        $key = esc_attr(get_option( 'rex__api_api' )); 
        echo '<input type="text" name="rex__api_api"  value="'. $key .'" placeholder="" />';
    }

    public function rex__admin_email() {
        $email = esc_attr(get_option( 'rex__admin_email' )); 
        echo '<input type="email" name="rex__admin_email"  value="'. $email .'" placeholder="example@example.com" />';
    }

    public function rex__retail_express() {
        $data = get_option( 'rex__retail_express' ); 

        $checked = $data != '' ? 'checked' : 'unchecked';
        
        $el = "";
        $el .= "<input class='wppd-ui-toggle' id='retail_express' type='checkbox'  name='rex__retail_express[retail_express]' $checked value='1'/>";
        $el .= '<label for="retail_express">Send an update to Retail Express Administrator on every WooCommerce transactions.</label>';

        echo $el;
    }

    public function rex__last_updated() {
        $data = esc_attr(get_option( 'rex__last_updated' )); 
        
        $el = "<strong>$data</strong>";
        $el .= '<label for="rex__last_updated"> When was the <strong>"Sync Product"</strong> last run.</label>';

        echo $el;
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