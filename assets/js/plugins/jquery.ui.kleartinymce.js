(function load($, undefined) {
    if (!$.klear.checkDeps(['$.fn.tinymce', 'window.tinymce'], load)) {
        return;
    }

    $.widget('ui.kleartinymce', {
        options: {
        },
        _init:function(){
            
            var $el = $(this.element);
            
            var options = this.options;
            
            var _self = this;
            
            var _contentChange = function(instance) {
                var changed = !(instance.startContent == instance.getBody().innerHTML); 
                if (changed) {
                    $("#"+instance.editorId).trigger('manualchange');
                }
            };
            
            var tinySettings = {
                onchange_callback : function(instance) {
                    _contentChange(instance);
                },
                handle_event_callback :function (event, instance) {
                    switch (event.type) {
                        case 'keyup':
                        case 'click':
                            _contentChange(instance);
                            break;
                    }
                    return true;
                },
                init_instance_callback : function (instance) {
                    
                }
            };
            
            $.extend(tinySettings, options);
            
            $el.tinymce(tinySettings);
            
        }
            
    });
})(jQuery);
