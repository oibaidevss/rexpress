<?php

namespace Inc\Api\WooCommerce;

use Carbon\carbon;

class ProductsController
{

    public function register()
    {
        $option = get_option( 'rex_update_retail_express' );

        if( $option['retail_express'] ){
            add_action( 'woocommerce_order_status_completed', array($this, 'rex_woocommerce_order_status_completed'), 10, 1 );
        }
            
    }

    function rex_woocommerce_order_status_completed( $order_id ) {
        
        $order = wc_get_order( $order_id );

        $this->rex_email( $order ); // email the order information 

    }

    function rex_email( $order ){
        
        $to = esc_attr(get_option( 'rex__admin_email' )); 

        $headers[] = 'MIME-Version: 1.0' . "\r\n";
        $headers[] = 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        $headers[] = 'From: Admin <notification@wordpress.org>';

        $subject = "WooCommerce Completed Transaction - Order Details";

        $el = '<html>';
        $el .= '<head><title>WooCommerce Order Details</title></head>';
        $el .= '<body>';
            $el .= '<p>Orders from: '. $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(). '</p>';
            $el .= '<table>';
                foreach($order->get_items() as $item_id => $item):
                    $el .= '<tr>';
                        $el .= '<td>';
                            $el .= $item->get_name();
                        $el .= '</td>';
                        $el .= '<td>';
                            $el .= $item->get_quantity();
                        $el .= '</td>';
                        $el .= '<td>';
                            $el .= $item->get_meta( '_rex_product_id', true );
                        $el .= '</td>';
                    $el .= '</tr>';
                endforeach;
            $el .= '</table>';
        $el .= '</body>';
        $el .= '</html>';

        $message = "$el";

        wp_mail( $to, $subject, $message, $headers );

    }

}