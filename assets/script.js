jQuery(window).on('load', function() {

    var btn     = jQuery('#sync'); 
    var res     = jQuery('.response');

    btn.find('span').hide();
    res.hide();

    btn.on('click', function(){
        
        swal({
            title: "Are you sure?",
            text: "You will not be able to undo this action.",
            icon: "warning",
            buttons: true,
            dangerMode: true,
          })
          .then((action) => {
            if (action) {
                jQuery(this).attr('disabled', 'disabled'); // Disabled to prevent multiple clicks
            
                btn.find('span').show();
                btn.addClass('spin');
                

                jQuery(function($) {
                    $.ajax({
                        type : "GET",
                        contentType: "application/json",
                        dataType: "json",
                        url : frontend_ajax_object.ajaxurl,
                        data : { action: "create_woo_products" },
                        success : function(response) {   
                            res.empty().show();
                            
                            btn.find('span').hide();

                            btn.removeAttr('disabled');

                            btn.removeClass('spin');

                            counter = 0;
                            jQuery.each(response, function(i, v){
                                jQuery.each(v, function ( k, value ){
                                    res.append("<p class='value'> " + value.sku + " " + value.name + " has been <span class='"+ value.type +"'>" +  value.type  + "</span></p>");
                                    counter++;
                                })
                            });

                            res.append("<p class='value'> There are "+ counter +" products that have been updated/created.</p>");    
                            
                            swal({
                                title: "Awesome",
                                text: "You've succesfully updated the products.",
                                icon: "success",
                                button: "Aww yiss!",
                            });
                        }
                    });
                });
            } else {
                console.log('canceled');
            }
          });
        


    });
   
    
    if(jQuery('body').hasClass('rexpress_page_rexpress_actions')){

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