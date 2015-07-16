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

                    };
                    return button;
                })(label,this));
            }
            return _buttons;
        },
        _registerUploaders : function() {

            var self = this;
            var _self = this.element;

            
            $("input:file[data-upload-command]",_self.klearModule("getPanel")).each(function() {
                
            	var $item = $("<div class='dialog-uploader' />");
                $item.attr("rel", $(this).attr("name")).data("command", $(this).data("upload-command"));
                
                $(this).replaceWith($item);
                
                // Objeto que encapsula métodos para habilitar/deshabilitar el botón de upload
                var buttonAcc = {
                    disable : function($context) {
                        $(".qq-upload-button", $context).addClass("ui-state-disabled");
                        $(".qq-upload-button", $context).append($('<div class="buttonHidder" />'));
                    },
                    enable : function($context) {
                        $(".qq-upload-button", $context).removeClass("ui-state-disabled");
                        $(".qq-upload-button .buttonHidder", $context).remove();
                    }
                };
                
                var requestData = {
                        file: $(self.options.parent).klearModule("option", "file"),
                        type : 'command',
                        command : $item.data('command')
                };

                var request = $.klear.buildRequest(requestData);
                

                new qq.FileUploader({
                    element: $item[0],
                    action: request.action,
                    params: request.data,
                    multiple: false,
                    messages: {
                        typeError: $.translate("{file} has invalid extension. Only {extensions} are allowed."),
                        sizeError: $.translate("{file} is too large, maximum file size is {sizeLimit}."),
                        minSizeError: $.translate("{file} is too small, minimum file size is {minSizeLimit}."),
                        emptyError: $.translate("{file} is empty, please select files again without it."),
                        onLeave: $.translate("The files are being uploaded, if you leave now the upload will be cancelled.")
                    },
                    template: '<div class="qq-uploader">'
                        + '<div class="klearMatrix_file newFile changed ui-state-highlight"></div>'
                        + '<div class="qq-upload-drop-area"><span></span></div>'
                        + '<div class="qq-upload-button ui-button ui-widget ui-state-default ui-corner-all"><span class="ui-icon ui-icon-folder-open inline"></span>' + $.translate("Upload File") + '</div>'
                        + '<ul class="qq-upload-list"></ul>'
                        + '</div>'
                        + '<input type="hidden" name="'+$item.attr("rel")+'" class="result" />',

                    onSubmit: function (id, fileName) {
                        buttonAcc.disable($(this._element));
                        return true;
                    },
                    onComplete : function(id, fileName, result) {
                        buttonAcc.enable($(this._element));
                        var $list = $(".qq-upload-list", $(this._element));

                        if (result.error) {
                            $list.html('<li>' + result.message + '</li>');
                            return;
                        }
                        
                        var fName = $(".qq-upload-file",$list).html();
                        var fSize = $(".qq-upload-size",$list).html();
                        
                        $("input.result", $(this._element)).val(result.code);
                        $(".newFile", $(this._element)).html(fName + ' (' + fSize + ')').show();
                        $list.html('');
                    },
                    onCancel: function(id, fileName) {
                        buttonAcc.enable($(this._element));
                        $list.html('');
                    },
                    onError: function(id, fileName, reason) {
                        buttonAcc.enable($(this._element));
                    }
                });
            });
        	
        },
        _init: function() {

            var self = this;
            
            $(this.element).moduleDialog("setAsLoading");
            $(this.element).moduleDialog("option", "buttons", this._getButtons());
            $(this.element).moduleDialog("updateContent",this._getDialogContent(),function() {
                self._registerBaseEvents();
                self._registerFieldsEvents();
                self._registerUploaders();
            });

            $(this.element).moduleDialog("updateTitle", this._getTitle());
            
            var options = this._getOptions();
            $.each(options, function(optionName, value) {
                $(self.element).moduleDialog("option", optionName, value);
            });
        }
    });

    $.widget.bridge("klearMatrixGenericDialog", $.klearmatrix.genericdialog);

})(jQuery);
