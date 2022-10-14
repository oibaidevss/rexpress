jQuery(window).on('load', function() {

    jQuery('#sync > span').hide();
    jQuery('.response').hide();
    
    jQuery('#sync').on('click', function(){
        
        var page = jQuery('input[name=page]').val();
        
        if( page !=  jQuery('._current').text() ){

        }else{
            jQuery(this).attr('disabled', 'disabled');
        }

        if (confirm('Are you sure you want to sync Retail Express to the database?')) {
            // Save it!
            console.log('syncing...');


            jQuery.ajax({
                type : "post",
                url : frontend_ajax_object.ajaxurl,
                data : { action: "create_woo_products", page_number: page },
                beforeSend : function () {
                    jQuery('#sync').addClass('spin');
                    jQuery('#sync > span').show();
                    jQuery('.response').show();
                },
                success : function(response) {
                    jQuery('#sync').removeClass('spin');
                    jQuery('#sync > span').hide();

                    jQuery('._current').text( parseInt(page) + 1 );
                    jQuery('input[name=page]').val( parseInt(page) + 1 );
                    jQuery('.response').append(response);
                }
            });
            
            

        } else {
            // Do nothing!
            console.log('canceled');
        }

    });
   
    
    if(jQuery('body').hasClass('rexpress_page_rexpress_actions')){
        console.log('options page');

        
        jQuery('._action').hide();

        jQuery.ajax({
            type : "post",
            url : frontend_ajax_object.ajaxurl,
            data : { action: "get_total_records" },
            success : function(response) {
                jQuery('._action').show();
                jQuery('.total_records').text(response);
                jQuery('._total').text( Math.ceil(parseInt(response) / 250) );
            }
        })


    }
});