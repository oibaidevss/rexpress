<?php

namespace Inc\Api\WooCommerce;

use Carbon\carbon;

class AdminProductsTable
{

    public function register()
    {
        // add_action( 'manage_product_posts_custom_column', 'updated_at', 10, 2 );
        add_filter( 'manage_edit-product_columns', array($this, 'add_product_column'), 1, 1 );
        add_action( 'manage_product_posts_custom_column', array($this, 'add_product_column_content'), 10, 2 );
    }


    function add_product_column( $columns ) {
        //add column
        unset( $columns['date'] );  

        $columns['updated_at'] = __( 'Updated at', 'woocommerce' );
    
        return $columns;
    }

    function add_product_column_content( $column, $postid ) {
        if ( $column == 'updated_at' ) {
            
            if(get_post_meta( $postid, '_updated_at' ) != null )
            {
                $dt = Carbon::parse( get_post_meta( $postid, '_updated_at', true ) );
                echo $dt->diffForHumans();
            }
        }
    }

    
}
