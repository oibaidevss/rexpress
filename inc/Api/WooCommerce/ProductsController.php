<?php

namespace Inc\Api\WooCommerce;

use Carbon\carbon;

class ProductsController
{

    public function register()
    {
        $option = get_option( 'rex_update_retail_express' );
        add_action('phpmailer_init', array($this, 'mailtrap'));

        if( $option['retail_express'] ){
            add_action( 'woocommerce_order_status_completed', array($this, 'rex_woocommerce_order_status_completed'), 10, 10 );
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
        $headers[] = 'From: Admin <wordpress@'. $_SERVER['SERVER_NAME'] .'>';

        $subject = "WooCommerce Completed Transaction - Order Details";

        $el = '<html>';
        $el .= '<head><title>WooCommerce Order Details</title></head>';
        $el .= '<body>';
            $el .= '<p>Order from: '. $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(). '</p>';
            $el .= '<table border="1" cellspacing="3" width="60%">';
                $el .= '<tr>';
                    $el .= '<th> SKU </th>';
                    $el .= '<th> Product Name </th>';
                    $el .= '<th> Qty </th>';
                $el .= '</tr>';
                foreach($order->get_items() as $item_id => $item):

                    $product = wc_get_product($item->get_product_id());
                    
                    $el .= '<tr>';
                        $el .= '<td>';
                            $el .=  $product->get_sku();
                        $el .= '</td>';
                        $el .= '<td>';
                            $el .= $item->get_name();
                        $el .= '</td>';
                        $el .= '<td>';
                            $el .= $item->get_quantity();
                        $el .= '</td>';
                    $el .= '</tr>';

                endforeach;
            $el .= '</table>';

            $el .= '<p>Please manually update retail express to match data from WooCommerce.</p>';
            $el .= '<p>Thank you, <br />';
            $el .= 'Admin</p>';
            
        $el .= '</body>';
        $el .= '</html>';

        $message = $el;

        $mail = wp_mail( $to, $subject, $message, $headers );
        
        return $mail;
    }

    function mailtrap($phpmailer) {
        $phpmailer->isSMTP();
        $phpmailer->Host = 'smtp.mailtrap.io';
        $phpmailer->SMTPAuth = true;
        $phpmailer->Port = 2525;
        $phpmailer->Username = '38d06446ecd02b';
        $phpmailer->Password = 'c412da4f84d53b';
    }


}