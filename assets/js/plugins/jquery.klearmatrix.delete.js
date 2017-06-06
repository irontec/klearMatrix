;(function($) {

    var __namespace__ = "klearmatrix.delete";

    $.widget("klearmatrix.delete", $.klearmatrix.module, {
        options: {
            data : null,
            parent : null,
            moduleName: 'delete'
        },
        _super: $.klearmatrix.module.prototype,
        _create : function() {
            this._super._create.apply(this);
        },

        _doAction : function(moduleDialogCaller) {

            var $refParent = $(this.options.parent);
            var $self = $(this.element);
            var self = this;

            var _ids = [];
            $(".deleteable-item",$(moduleDialogCaller)).each(function() {
                _ids.push($(this).data("id"));
            });

            $self
                .moduleDialog("setAsLoading")
                .moduleDialog("option","buttons",[]);

            $.klear.request(
                    {
                        file: $refParent.klearModule("option","file"),
                        type: 'dialog',
                        execute: 'delete',
                        dialog : $(this.element).data("dialogName"),
                        pk : _ids
                    },
                    function(data) {

                        $self.moduleDialog("updateContent",data.message);

                        if (data.error) {
                            //TO-DO: FOK OFF
                        } else {
                            if (!$.isArray(data.pk)) data.pk = [data.pk];
                            var lastInterval, callback = null;

                            $.each(data.pk,function(idx,_pk) {

                                if ($(self.options.caller).data("parentHolderSelector")) {

                                    var $caller = $(self.options.caller);
                                    var item = self._resolveParentHolder($caller);

                                    var callback = function() {
                                        $caller.parents("ul:eq(0)").parent().find(".ghostListCounter input").trigger("doSum");
                                    };
                                } else {
                                    var item = "tr[data-id='"+_pk+"']";
                                }

                                $(item,$refParent.klearModule("getPanel")).slideUp(function() {
                                    if (typeof callback == 'function') callback();
                                    $(this).remove();
                                    clearTimeout(lastInterval);
                                    lastInterval = setTimeout(function() {
                                        $("th.multiItem", $refParent.klearModule("getPanel")).trigger('refreshButtons');
                                    }, 400);
                                });
                            });

                            if (self.mustBeClosed) {
                                $self.moduleDialog("close");
                            }
                        }

                        $self.moduleDialog("option","buttons",
                                 [
                                      {
                                        text: $.translate("Close"),
                                        click: function() { $(this).moduleDialog("close"); }
                                    }
                                ]
                        );


                    },
                    function(data) {
                        self.standardError(data);
                     }
            );
        },
        _init: function() {

            var $appliedTemplate = this._loadTemplate("klearmatrixDelete");
            var self = this;

            this.mustBeClosed = false;

            $(this.element).moduleDialog("option","buttons",
                 [
                      {
                        text: $.translate("Cancel"),
                        click: function() { $(this).moduleDialog("close"); }
                    },
                    {
                        text: $.translate("Delete"),
                        class: 'deleteDialogButton',
                        click: function() {
                            self._doAction.apply(self,[this]);
                        }
                    }
                ]
            );

            if (this.options.data && this.options.data.title) {
                $(this.element).moduleDialog("updateTitle",this.options.data.title);
            }

            $(this.element).moduleDialog("updateContent",$appliedTemplate,function() {

                $autoClose = $("form.autoclose-dialog", $(this.element));
                $autoClose
                    .form()
                    .appendTo($(this.uiDialog).find(".ui-dialog-buttonpane"));

                var $secureDeleteInput = $("#secure-delete-input", $(this.element));
                if ($secureDeleteInput.length) {

                    var deleteDialogButton = $(".deleteDialogButton", $(this.element).parent());
                    $secureDeleteInput.on('input', function () {

                        var isValid = ($(this).attr('data-expected-value') === $(this).val());

                        if (isValid) {
                            deleteDialogButton.trigger('enable');
                        } else {
                            deleteDialogButton.trigger('disable');
                        }
                    });

                    deleteDialogButton.on("disable", function () {
                        $(this)
                            .attr('disabled', 'disabled')
                            .css({ opacity: 0.5 });
                    }).on("enable", function () {
                        $(this)
                            .removeAttr('disabled')
                            .css({ opacity: 1 });
                    });
                    deleteDialogButton.trigger('disable');
                }

                var $autoCloseCheckbox = $("input[name=autoclose]",$autoClose);
                $autoCloseCheckbox.on('change',function(e) {
                    // El cliente ha usado autoclose, guardamos su valor
                    if (localStorage) {
                        localStorage.setItem('klearmatrix.autoclose',$(this).is(":checked"));
                    }
                    self.mustBeClosed = $(this).is(":checked");
                    $autoCloseCheckbox.not($(this)).trigger('toggleValue');
                });

                // En la carga de la pantalla, comprobamos si existe la preferencia sobre autoclose
                // Preferencia que se setea automáticamente si el usuario la utiliza
                if (localStorage && localStorage.getItem('klearmatrix.autoclose') != null) {
                    var savedVal = localStorage.getItem('klearmatrix.autoclose') == 'true';
                    self.mustBeClosed = savedVal;
                    $autoCloseCheckbox.trigger('forceValue', savedVal);
                }
            });

        },
    });

})(jQuery);

