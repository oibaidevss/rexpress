jQuery(window).on('load', function() {

    jQuery('#sync > span').hide();
    jQuery('.response').hide();
    
    jQuery('._action #sync').on('click', function(){
        
        jQuery(this).attr('disabled', 'disabled'); // Disabled to prevent multiple clicks
        jQuery('#sync > span').show();
        jQuery('#sync').addClass('spin');

        jQuery('.response').append( "<p class='ongoing'>Syncing</p>" );

        var page = jQuery('input[name=page]').val();
        
        var _current = jQuery('._current');
        var _total   = jQuery('._total').text();
        
        var check    = false;

        if (confirm('Are you sure you want to sync Retail Express to the database?')) {
            // Save it!
            jQuery('.response').show();

            jQuery(function($) {
                $('._current').countTo({
                    from: 0,
                    to: _total,
                    speed: 10000,
                    refreshInterval: 2500,
                    onUpdate: function(value) {

                        jQuery.ajax({
                            type : "post",
                            url : frontend_ajax_object.ajaxurl,
                            data : { action: "create_woo_products", page_number: value },
                            beforeSend: function () { check = true; },
                            success : function(response) {            
                                
                                jQuery('.response').append( response );

                            }
                        });


                    },
                    onComplete: function(value) {
                        
                        jQuery('.response').append( "<p class='ongoing'></p>" );
                        
                        jQuery("._action #sync").removeAttr('disabled').text('Click to sync again.'); // Disabled to prevent multiple clicks
                        jQuery('._action #sync').removeClass('spin');
                        jQuery('._action #sync > span').hide();

                    }
                });
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


(function($) {
    $.fn.countTo = function(options) {
        // merge the default plugin settings with the custom options
        options = $.extend({}, $.fn.countTo.defaults, options || {});
        
        // how many times to update the value, and how much to increment the value on each update
        var loops = Math.ceil(options.speed / options.refreshInterval),
            increment = (options.to - options.from) / loops;
        
        return $(this).each(function() {
            var _this = this,
                loopCount = 0,
                value = options.from,
                interval = setInterval(updateTimer, options.refreshInterval);
            
            function updateTimer() {
                value += increment;
                loopCount++;
                $(_this).html(value.toFixed(options.decimals));
                
                if (typeof(options.onUpdate) == 'function') {
                    options.onUpdate.call(_this, value);
                }
                
                if (loopCount >= loops) {
                    clearInterval(interval);
                    value = options.to;
                    
                    if (typeof(options.onComplete) == 'function') {
                        options.onComplete.call(_this, value);
                    }
                }
            }
        });
    };
    
    $.fn.countTo.defaults = {
        from: 0,  // the number the element should start at
        to: 100,  // the number the element should end at
        speed: 1000,  // how long it should take to count between the target numbers
        refreshInterval: 100,  // how often the element should be updated
        decimals: 0,  // the number of decimal places to show
        onUpdate: null,  // callback method for every time the element is updated,
        onComplete: null,  // callback method for when the element finishes updating
    };
})(jQuery);