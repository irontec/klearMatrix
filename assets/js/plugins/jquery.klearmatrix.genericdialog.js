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
        _getButtons : function() {
            var _buttons = [];

            for(var label in this.options.data.buttons) {

                _buttons.push((function(label,self) {

                    var button = {
                        text: label,
                        click : function() {
                            if (self.options.data.buttons[label].recall) {

                                var extraData = {
                                        params: self.options.data.buttons[label].params,
                                        external: self.options.data.buttons[label].external
                                };
                                
                                var configuredParams = {};
                                // Metemos en la petición todos los campos del formulario.
                                $.each($("input,select,textarea",$(self.element)), function() {
                                	configuredParams[$(this).attr("name")] = $(this).val();
                                });
                                
                                $.extend(extraData.params,configuredParams);
                                
                                self.options.caller.trigger("click",extraData);
                            }
                            $(this).moduleDialog("close");
                        }
                    }
                    return button;
                })(label,this));

            }

            return _buttons;

        },
        _init: function() {

            var self = this;

            $(this.element).moduleDialog("option","buttons",this._getButtons());
            $(this.element).moduleDialog("updateContent",this._getDialogContent());

        }

    });

    $.widget.bridge("klearMatrixGenericDialog", $.klearmatrix.genericdialog);

})(jQuery);
