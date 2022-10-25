<?php
/**
* @package REXpressPlugin
*/


namespace Inc;

final class init
{
    /** 
     * Store all the classes inside the array
     * @return array ful list of classes
     */
    public static function get_services() {
        return [
            Pages\Admin::class,
            Base\Enqueue::class,
            Base\SettingsLinks::class,
            Api\RetailExpressApi\RetailExpressApiController::class,
            Api\WooCommerce\AdminProductsTable::class
        ];
    }

    /**
     * Loop through the classes, initialize them, 
     * and call the register() method if it exists
     * @return 
     */
    public static function register_services() {
        foreach ( self::get_services() as $class ) {
            $service = self::instantiate( $class );
            if ( method_exists( $service, 'register') ) {
                $service->register();
            }
        }
    }

    /**
     * Initialize the class
     * @param class $class class from the services array
     * @return class instance new instance of the class
     */
    private static function instantiate( $class ) {
        return new $class();
    }
}