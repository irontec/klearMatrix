;(function load($) {

    if (!$.klear.checkDeps(['$.klearmatrix.module'],load)) {
        return;
    }

    var __namespace__ = "klearmatrix.genericdialog";
    

    
    $.widget("klearmatrix.genericdialog", $.klearmatrix.module,  {
        options: {
            data : null,
            moduleName: 'genericdialog'
        },
        _super: $.klearmatrix.module.prototype,
        _create : function() {
            this._super._create.apply(this);
        },
        _getDialogContent : function() {

            return this.options.data.message;
        },
        
        _getTitle : function() {

            return this.options.data.title;
        },
        
        _getOptions :  function() {
            
            return this.options.data.options || [];
        },
        
        _getButtons : function() {
            var _buttons = [];

            for(var label in this.options.data.buttons) {

                _buttons.push((function(label,self) {

                    var button = {
                        text: label,
                        click : function() {
                        	
                        	$(this).moduleDialog("close");
                        	
                            if (self.options.data.buttons[label].recall) {

                                var extraData = {
                                        params: self.options.data.buttons[label].params || {},
                                        external: self.options.data.buttons[label].external || false
                                };

                                var configuredParams = {};
                                // Metemos en la petición todos los campos del formulario.

                                $.each($("input,select,textarea",$(self.element)), function() {
                                	if ($(this).attr("type") == 'radio' || 
                                			$(this).attr("type") == 'checkbox') {
                                			
                                		if (!$(this).is(":checked")) {
                                			return;
                                		}
                                	}
                                	configuredParams[$(this).attr("name")] = $(this).val();
                                });


                                $.extend(extraData.params,configuredParams);
                                self.options.caller.trigger("click",extraData);

                            }

                            if (self.options.data.buttons[label].reloadParent) {
                            	$(self.options.parent).klearModule("reDispatch");
                            }

                            
                        }
                    	
                    }
                    return button;
                })(label,this));
            }
            return _buttons;
        },
        _init: function() {
        	
        	

            var self = this;
            $(this.element).moduleDialog("option", "buttons", this._getButtons());
            $(this.element).moduleDialog("updateContent",this._getDialogContent());
            $(this.element).moduleDialog("updateTitle", this._getTitle());
            
            var options = this._getOptions();
            $.each(options, function(optionName, value) {
                $(self.element).moduleDialog("option", optionName, value);
            });

        }

    });
    
    $.widget.bridge("klearMatrixGenericDialog", $.klearmatrix.genericdialog);

})(jQuery);
