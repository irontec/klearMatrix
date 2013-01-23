(function load($, undefined) {

    if (!$.klear.checkDeps(['$.fn.wymeditor'], load)) {

        return;
    }

    $.widget('klearmatrix.wym', {
        options: {},

        _init:function() {

            var availableSettings = {
                "_contentTab": true,
                "lang" : true,
                "basePath" : true,
                "skinPath" : true,
                "wymPath" : true,
                "logoHtml" : true,
            };

            var editorOptions = {};

            for (idx in this.options) {

                if(availableSettings[idx]) {

                    editorOptions[idx] = this.options[idx];
                }
            }

            var $el = $(this.element);
            var _self = this;

            $el.wymeditor(editorOptions);

            var contentTextarea = $el.prev();
            var contentIframe = $("<iframe id='iframeDialog"+ $el.attr("id") +"' data-name='dialog"+ $el.attr("id") +"'></iframe>");

            contentIframe.hide().on('showme', function(e, callback, callbackContext) {

                var contentTab = _self.options._contentTab;

                $(contentTab).klearModule("showDialog",
                    '<br />',
                    {
                        title: 'Dialog',
                        template : "",
                        buttons : [
                            {
                                text: "Enviar",
                                click: function() {

                                    var iframe_$ = $(this).find("iframe").get(0).contentWindow.$;
                                    iframe_$("form").submit();
                                    _self._closeDialog(contentTab);
                                }
                            },
                            {
                                text: "Cancelar",
                                click: function() {

                                    _self._closeDialog(contentTab);
                                }
                            },
                        ]
                    }
                );

                var $moduleDialog = $(_self.options._contentTab).klearModule("option","moduleDialog");

                $moduleDialog.parent().css({"width": "700px", "height" : "500px"});
                $moduleDialog.css({"width": "650px", "height" : "400px"});
                contentIframe.css({"width": "650px", "height" : "400px"});

                $moduleDialog.html("").append($(this).clone().show().attr("name", $(this).data("name")));

                var botonera = $moduleDialog.next();
                callback.call(callbackContext);

            }).appendTo("body");
        },

        _closeDialog : function (contentTab) {

            $_dialog = $(contentTab).klearModule("getModuleDialog");
            $_dialog.moduleDialog("close");
        }
    });

})(jQuery);
