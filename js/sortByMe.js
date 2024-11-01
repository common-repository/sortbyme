jQuery(document).ready(function()
{
   jQuery('tbody#the-list').sortByMe();
   jQuery('.save').bind('click', function()
   {
       window.setTimeout("location.reload()", 800);
   });
});

(function($) 
{
    var fixHelper = function(e, ui) {
        ui.children().each(function() {
            $(this).width($(this).width());
        });
        return ui;
    };
    var methods = 
    {
        init: function(options)
        {
            var options = $.extend({}, $.fn.sortByMe.defaults, options);
            
            return this.each(function() 
            {  
                var $$ = $(this);
                $$.find('th').append('</div />').addClass('sortByMe');  
                
                $$.sortable(
                {
                    axis: "y",
                    containment: $$.parent(),
                    handle: $('.sortByMe div'),
                    distance: 0,
                    opacity:0.5,
                    scroll: true,
                    helper:fixHelper,
                    stop: function(event, ui)
                    {
                        $$.find('tr').removeClass('alternate');
                        $$.find('tr:nth-child(odd)').addClass('alternate');
                    },
                    update:function()
                    {
                        callAjax($(this));
                    }
                }).disableSelection();
                
                var callAjax = function($$)
                {
                    jQuery.ajax({
                        type: 'POST',
                        url: SortByMe.ajaxurl,
                        dataType: 'json',
                        data: {
                                action : SortByMe.action,
                                _ajax_nonce : SortByMe.nonce,
                                order : $$.sortable('serialize')
                        },
                        success : function(data){
                            
                        },
                        error : function(XMLHttpRequest, textStatus, errorThrown) {
                            
                        }
                    });
                }
                
            });
        }
    };
    $.fn.sortByMe = function(options, method) 
    {
    if(methods[method]) 
        return methods[method].apply( this, Array.prototype.slice.call(arguments, 1));
    else if(typeof method === 'object' || ! method)
        return methods.init.apply(this, arguments);
    else
        $.error( 'La methode '+ method+' n\'existe pas');
    }
    
    //PARAMS
    $.fn.sortByMe.defaults = 
    {
        
    };
})(jQuery);


