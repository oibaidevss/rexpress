jQuery(window).on('load', function() {

    jQuery('#sync > span').hide();
    jQuery('.response').hide();
    
    jQuery('#sync').on('click', function(){
        
        if (confirm('Are you sure you want to sync Retail Express to the database?')) {
            // Save it!
            console.log('syncing...');
            
            jQuery.ajax({
                type : "post",
                url : frontend_ajax_object.ajaxurl,
                data : { action: "create_woo_products" },
                beforeSend : function () {
                    jQuery('#sync').addClass('spin');
                    jQuery('#sync > span').show();
                    jQuery('.response').show();
                },
                success : function(response) {
                    jQuery('#sync').removeClass('spin');
                    jQuery('#sync > span').hide();

                    jQuery('.response').append(response);
                    copnsojQuery('')
                }
            })

        } else {
            // Do nothing!
            console.log('canceled');
        }
    });
      
});