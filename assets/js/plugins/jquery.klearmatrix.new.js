;(function load($) {

    if (!$.klear.checkDeps(['$.klearmatrix.edit'],load)) {
        return;
    }

    var __namespace__ = "klearmatrix.new";

    $.widget("klearmatrix.new", $.klearmatrix.edit, {
        options: {
            data : null,
            moduleName: 'new'
        },

        _super: $.klearmatrix.module.prototype,

        _create : function() {
            this._super._create.apply(this);
        },

        _init: function() {

            this.options.data.title = this.options.data.title || this.element.klearModule("option","title");
            $.extend(this.options.data,{randIden:Math.round(Math.random(1000,9999)*100000)});

            var tplName = (this.options.data.mainTemplate) ? this.options.data.mainTemplate : "klearmatrixNew";

            var $appliedTemplate = this._loadTemplate(tplName);

            var $container = $(this.element.klearModule("getPanel"));

            $container.append($appliedTemplate);

            var self = this;

            $container.one("focusin",function(e) {
                self.element.klearModule("showOverlay");

                e.preventDefault();
                e.stopPropagation();

                self._applyDecorators()
                    ._registerReDispatchSavers()
                    ._initFormElements()
                    ._registerBaseEvents()
                    ._registerEvents()
                    ._registerFieldsEvents()
                    ._registerMainActionEvent();

                self.element.klearModule("hideOverlay");
            });

            if ($container.is(":visible")) {
                $container.trigger("focusin");
            }

        },

        _doAction : function() {

            var self = this;
            var $self = $(this.element);

            var $dialog = $self.klearModule("getModuleDialog");
            var postData = self.options.theForm.serializeArray();

            var addAnotherOption = !self.options.theForm.data("disableaddanother");

            if (typeof this.options.data.parentId != 'undefined') {
                postData.push({ name:this.options.data.parentItem, value:this.options.data.parentId});
            }

            // Es una pantalla nueva "heredada" de una edición (pk será el elemento 'llamante'
            if (self.options.data.parentPk) {
                postData.push({name:'parentPk',value: self.options.data.parentPk});
            }

            $.klear.request(
                {
                    file: $self.klearModule("option","file"),
                    type: 'screen',
                    execute: 'save',
                    screen: self.options.data.screen,
                    post : postData
                },
                function(data) {

                    if (data.error) {
                        //TO-DO: FOK OFF
                        // Mostrar errores desde arriba
                    } else {
                        var $parentModule = $self.klearModule("option","parentScreen");
                        $parentModule.klearModule("reDispatch");

                        $("input,select,textarea",self.options.theForm).val('');
                        self._initSavedValueHashes();
                        self.options.theForm.trigger('updateChangedState');

                        if ($("input[name=autoclose]",$self.klearModule("getPanel")).is(":checked")) {
                            $dialog.moduleDialog("close");
                            $self.klearModule("close");
                            return
                        }
                    }

                    var _buttons = [{
                        text: $.translate("Close"),
                        click: function() {
                            $(this).moduleDialog("close");
                            $self.klearModule("close");
                        }
                    }];

                    if (addAnotherOption) {
                        _buttons.push(
                            {
                                text: $.translate("Add another record"),
                                click: function() {
                                    $self.klearModule("reDispatch");
                                }
                            }
                        );
                    } else {
                        // Al cerrar el dialogo, cerraremos también la pestaña
                        $dialog.moduleDialog("option","beforeClose",function() {
                            $self.klearModule("close");
                        });
                    }

                    $dialog.moduleDialog("option","buttons",_buttons);
                    $dialog.moduleDialog("updateContent",data.message);

                    var triggerData = {'data': data, 'postData': postData};
                    $self.trigger('postMainActionHook', triggerData);
                },
                // Error from new/index/save
                function(data) {
                    self.standardError(data);
                }
            );
        }
    });

    $.widget.bridge("klearMatrixNew", $.klearmatrix['new']);

})(jQuery);
