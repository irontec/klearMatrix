(function load($, undefined) {

    if (!$.klear.checkDeps([
                '$.fn.wymeditor',
                'WYMeditor.editor.prototype.resizable',
                'WYMeditor.editor.prototype.hovertools',
                'WYMeditor.editor.prototype.fullscreen'],
                load
    )) {

        return;
    }

    $.widget('klearmatrix.wym', {
        options: {},

        _init:function() {

            var availableSettings = {
                "_contentTab": true,
                "lang" : true,
                "basePath" : true,
                "jQueryPath" : true,
                "skinPath" : true,
                "wymPath" : true,
                "logoHtml" : true,
                "stylesheet": true,
                "plugins" : true,
            };

            var editorOptions = {

                toolsItems: [
                    {'name': 'Bold', 'title': 'Strong', 'css': 'wym_tools_strong'},
                    {'name': 'Italic', 'title': 'Emphasis', 'css': 'wym_tools_emphasis'},
                    {'name': 'Superscript', 'title': 'Superscript', 'css': 'wym_tools_superscript'},
                    {'name': 'Subscript', 'title': 'Subscript', 'css': 'wym_tools_subscript'},
                    {'name': 'InsertOrderedList', 'title': 'Ordered_List', 'css': 'wym_tools_ordered_list'},
                    {'name': 'InsertUnorderedList', 'title': 'Unordered_List', 'css': 'wym_tools_unordered_list'},
                    {'name': 'Indent', 'title': 'Indent', 'css': 'wym_tools_indent'},
                    {'name': 'Outdent', 'title': 'Outdent', 'css': 'wym_tools_outdent'},
                    {'name': 'Undo', 'title': 'Undo', 'css': 'wym_tools_undo'},
                    {'name': 'Redo', 'title': 'Redo', 'css': 'wym_tools_redo'},
                    {'name': 'CreateLink', 'title': 'Link', 'css': 'wym_tools_link'},
                    {'name': 'Unlink', 'title': 'Unlink', 'css': 'wym_tools_unlink'},
                    {'name': 'InsertTable', 'title': 'Table', 'css': 'wym_tools_table'},
                    {'name': 'Paste', 'title': 'Paste_From_Word', 'css': 'wym_tools_paste'},
                    {'name': 'ToggleHtml', 'title': 'HTML', 'css': 'wym_tools_html'},
                    {'name': 'Preview', 'title': 'Preview', 'css': 'wym_tools_preview'}
                ],
                postInit: function(wym) {

                    //postInit is executed after WYMeditor initialization
                    //'wym' is the current WYMeditor instance

                    //we generally activate plugins after WYMeditor initialization

                    //activate 'hovertools' plugin
                    //which gives advanced feedback to the user:

                    var pluginList = _self.options.plugins.split(",");

                    for (var idx in pluginList) {

                        if ("kleargallery" == pluginList[idx]) {

                            wym[pluginList[idx]]($(_self.options._contentTab));

                        } else {

                            wym[pluginList[idx]]();
                        }

                    }
                }
            };

            for (idx in this.options) {

                if(availableSettings[idx]) {

                    editorOptions[idx] = this.options[idx];
                }
            }

            var $el = $(this.element);
            var _self = this;

            if (this._isMultilang($el)) {

                //Reset label padding
                var fldLabel = $el.parent().prev();
                fldLabel.css("width", "auto");

                //Set width as long as posible
                var maxAvailableWidth = fldLabel.parent().width() - fldLabel.width() - 20;
                $el.parent().css("width", maxAvailableWidth);
            }


            $el.wymeditor(editorOptions);

            var contentTextarea = $el.prev();

            var contentIframe = $("<iframe id='iframeDialog"+ $el.attr("id") +"' data-name='dialog"+ $el.attr("id") +"'></iframe>");

            contentIframe.hide().on('showme', function(e, callback, callbackContext) {

                var contentTab = _self.options._contentTab;

                $(contentTab).klearModule("showDialog",
                    '<br />',
                    {
                        title: 'Dialog',
                        width: 450,
                        height: 350,
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

                contentIframe.css(
                    {
                        "width": $moduleDialog.width(),
                        "height" : $moduleDialog.height(),
                        'overflow' : 'hidden'
                    }
                );

                $moduleDialog.html("").append($(this).clone().show().attr("name", $(this).data("name")));

                var botonera = $moduleDialog.next();
                callback.call(callbackContext);
            });

            if($("#iframeDialog"+ $el.attr("id")).length == 0) {

                //TODO no matchea, pendiente de solucionar
                contentIframe.appendTo("body");
                return;
            }
        },

        _isMultilang: function ($el) {

            return $el.parent().prop("tagName").toLowerCase() == "dd";

        },
        _closeDialog : function (contentTab) {

            $_dialog = $(contentTab).klearModule("getModuleDialog");
            $_dialog.moduleDialog("close");
        }
    });

})(jQuery);
