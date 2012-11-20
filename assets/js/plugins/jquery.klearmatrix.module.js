;(function($) {

    var __namespace__ = "klearmatrix.module";

    $.widget("klearmatrix.module", {
        options: {
            moduleName: 'module'
        },
        _create: function(){

            if (!this.instances) {
                $.extend($.klearmatrix[this.options.moduleName], {
                    instances: []
                });
            }
            $.klearmatrix[this.options.moduleName].instances.push(this.element);

        },
        _getOtherInstances: function(){

            var element = this.element;

            return $.grep($.klearmatrix[this.options.moduleName].instances, function(el){
                return el !== element;
            });
        },
        destroy: function(){
            // remove this instance from $.klearmatrix.mywidget.instances
            var element = this.element,
            position = $.inArray(element, $.klearmatrix[this.options.moduleName].instances);


            // if this instance was found, splice it off
            if(position > -1){
                $.klearmatrix[this.options.moduleName].instances.splice(position, 1);
            }

            // call the original destroy method since we overwrote it
            $.Widget.prototype.destroy.call( this );
        },
        _setOptions: function() {
            $.Widget.prototype._setOptions.apply(this, arguments);
        },

        _setOption: function(key, value) {
            $.Widget.prototype._setOption.apply(this, arguments);
        },

        _parseDefaultItems : function() {

            if (!this.options.data.title) {
                return;
            }

            
            this.options.data.title =  $.klearmatrix.template.helper._parseDefaultValues({
                title: this.options.data.title,
                replaceParentPerItem : false,
                defaultLang : this.options.data.defaultLang,
                parentIden: this.options.data.parentIden,
                columns: this.options.data.columns,
                values: this.options.data.values,
                idx: 0
            });

        },
        getData : function(value) {
            
            if (this.options.data[value]) {
                return this.options.data[value]
            }
            
            return this.options.data; 
        },
        _loadTemplate : function(tmplName) {

            var $tmplObj = $.tmpl(
                            tmplName,
                            this.options.data,
                            $.klearmatrix.template.helper
                            );

            this._parseDefaultItems();
            return $tmplObj;

        },
        _getClearText : function($items) {
            var retValues = [];            
            $items.each(function() {
                
                if (!$(this).is(".multilang")) {
                    retValues.push($(this).contents().first().text());
                    return;
                }

                if ($(".multilangValue", $(this)).length>0) {
                    if ($(".selected", $(this)).length == 1) {
                        retValues.push($(".selected", $(this)).contents().first().text());
                    } else {
                        retValues.push($(".multilangValue:eq(0)", $(this)).contents().first().text());
                    }
                } 
            });
            
            return retValues.join(' ');
        },

        _resolveParentHolder : function(element) {

            // Si es un módulo con parent
            if (this.options.moduleParent) {
                var modulecheck = this.options.moduleParent;
            } else {
                var modulecheck = this.options.moduleName;
            }

            switch (modulecheck) {
                case 'list':
                    return $(element).parents("tr:eq(0)");
                break;
                case 'new':
                case 'edit':
                    return $(element).parents("form:eq(0)");

                break;
                default:

                    if ($(element).data("parentHolderSelector")) {
                        return $(element).parents($(element).data("parentHolderSelector"));
                    } else {
                        throw 'no parentHolder found for option';
                    }
                break;
            }

        },

        _registerBaseEvents : function() {

            var self = this.element;
            var _self = this;

            $(self.klearModule("getPanel"))
                .off('click.closeTab')
                .on('click.closeTab', '.closeTab', function(e) {
                e.preventDefault();
                e.stopPropagation();

                self.klearModule("close");
            });

            $('select:not(.multiselect, .notcombo)', this.element.klearModule("getPanel"))
                .combobox({
                    'selected' : function(event, ui) {
                        $(this).trigger("manualchange")
                    }
                });

            $('a.option.screen', this.element.klearModule("getPanel"))
                .on('mouseup.screenOption')
                .on('mouseup.screenOption', function(e) {

                e.preventDefault();
                e.stopPropagation();

                var _container = self.klearModule("getContainer");

                var _iden = "#tabs-" + self.klearModule("option", "file")
                            + '_' + $(this).data("screen");

                if ($(this).data("multiinstance")) {
                    _iden += '_' + Math.round(Math.random(1000, 9999)*100000);
                } else {
                    _iden += '_' + $(this).parents("tr:eq(0)").data("id");
                }

                if ($(_iden).length > 0) {
                    _container.tabs('select', _iden);
                    return;
                }

                var _newIndex = self.klearModule("option", "tabIndex")+1;
                var _menuLink = $(this);

                var _parentHolder = _self._resolveParentHolder(this);

                if ($(this).hasClass("_fieldOption")) {
                    _menuLink.addClass("ui-state-highlight");
                }

                var tabTitle = ($(".default", _parentHolder).length>0) ?
                        _self._getClearText($(".default", _parentHolder)) : $(this).tooltip("close").attr("title");

                _container.one( "tabspostadd", function(event, ui) {

                    var $tabLi = $(ui.tab).parent("li");

                    // Seteamos como menuLink <- enlace "generador", el enlace que lanza el evento
                    $tabLi.klearModule("option", "menuLink", _menuLink);
                    $tabLi.klearModule("option", "parentScreen", self);
                    $tabLi.klearModule("option", "title", tabTitle);

                    // Actualizamos el file, al del padre (En el constructor se pasa "sucio")
                    $tabLi.klearModule("option", "file", self.klearModule("option", "file"));

                    // Seteamos el valor para dispatchOptions
                    var _dispatchOptions = {
                        screen : _menuLink.data("screen"),
                        pk : _parentHolder.data("id"),
                        post : {
                            callerScreen : _self.options.data.screen
                        }
                    };

                    // Si la pantalla llamante tiene condición (parentId -- en data --
                    // enviarlos a la nueva pantalla
                    if (_self.options.data.parentId) {
                        _dispatchOptions.post.parentId = _self.options.data.parentId;
                        _dispatchOptions.post.parentScreen = _self.options.data.parentScreen;
                    }


                    // hioghlight on hover
                    _menuLink.data("relatedtab", $tabLi);

                    $tabLi.klearModule("option", "dispatchOptions", _dispatchOptions)
                        .klearModule("reload");


                });

                // Klear open in background
                $.klear.checkNoFocusEvent(e, $(self.klearModule("getPanel")).parent(), $(this));

                _container.tabs( "add", _iden, tabTitle, _newIndex);

            })
                .off('click.screenOption')
                .on('click.screenOption', function(e) {
                // Paramos el evento click, que salta junto con mouseup al hacer click con botón izquierdo
                e.preventDefault();
                e.stopPropagation();
            });



            /*
             * Capturar opciones de diálogo.
             */
            $('a.option.dialog', this.element.klearModule("getPanel"))
                .off('click.dialogOptions')
                .on('click.dialogOptions', function(e, data) {

                e.preventDefault();
                e.stopPropagation();

                var external = data && data.external || false

                var _container = self.klearModule("getContainer");

                var _parentHolder = _self._resolveParentHolder(this);

                var $caller = $(this);
                $(self).klearModule("showDialog",
                        '<br />',
                        {
                            title: $(this).attr("title") || '',
                            template : '<div class="ui-widget">{{html text}}</div>'                            
                        });

                var $_dialog = $(self).klearModule("getModuleDialog");
                $_dialog.moduleDialog("setAsLoading");
                $_dialog.data("dialogName", $(this).data("dialog"));
                var _postData = {
                    callerScreen : _self.options.data.screen
                };

                // Si la pantalla llamante tiene condición (parentId -- en data --
                // enviarlos a la nueva pantalla

                if (_self.options.data.parentId) {
                    _postData.parentId = _self.options.data.parentId;
                    _postData.parentScreen = _self.options.data.parentScreen;
                }

                if (data && typeof data.params != undefined) {
                    $.extend(_postData, data.params);
                }

                if ($(this).data("params")) {
                    $.extend(_postData, $(this).data("params"));
                }

                $.klear.request(
                        {
                            file: self.klearModule("option", "file"),
                            type: 'dialog',
                            dialog : $_dialog.data("dialogName"),
                            pk : _parentHolder.data("id"),
                            post: _postData,
                            external: external
                        },
                        function(response) {
                            if (external) {
                                $_dialog.moduleDialog("close");
                            } else {
                                $_dialog[response.plugin]({data : response.data, parent: self, caller: $caller});
                            }
                        },
                        function() {
                            console.log(arguments);

                        }
                );


            })
                .off('mouseup.dialogOptions')
                .on('mouseup.dialogOptions', function(e) {
                // Paramos el evento mouseup, para no llegar al tr
                e.preventDefault();
                e.stopPropagation();
            });

            /*
             * Capturar opciones de command (Siempre request externo -- no callback available!
             * TO-DO: Callback JS? when-ever need will be implemented
             */

            $('a.option.command', this.element.klearModule("getPanel"))
                .off('click.commandAction')
                .on('click.commandAction', function(e, data) {

                e.preventDefault();
                e.stopPropagation();

                var external = data && data.external || false;
                external = $(this).data("external")? true: external;

                var _container = self.klearModule("getContainer");

                switch (_self.options.moduleName) {
                    case 'list':
                        var _parentHolder = $(this).parents("tr:eq(0)");
                        $(this).on('mouseup', function(e) {
                            // Paramos el evento mouseup, para no llegar al tr
                            e.preventDefault();
                            e.stopPropagation();
                        });

                    break;
                    case 'new':
                    case 'edit':
                        var _parentHolder = $(this).parents("form:eq(0)");
                    break;
                }

                var _postData = {
                    callerScreen : _self.options.data.screen
                };

                // Si la pantalla llamante tiene condición (parentId -- en data --
                // enviarlos a la nueva pantalla
                if (_self.options.data.parentId) {
                    _postData.parentId = _self.options.data.parentId;
                    _postData.parentScreen = _self.options.data.parentScreen;
                }

                if (data && typeof data.params != undefined) {
                    $.extend(_postData, data.params);
                }

                $.klear.request(
                        {
                            file: self.klearModule("option", "file"),
                            type: 'command',
                            command : $(this).data("command"),
                            pk : _parentHolder.data("id"),
                            post: _postData,
                            external: external
                        },
                        function(response) {

                        },
                        function() {
                            console.log(arguments);

                        }
                );

            });


            $("[title]:not(.fieldInfo-box)", this.element.klearModule("getPanel")).tooltip();

            $(".fieldInfo-box", this.element.klearModule("getPanel")).toggle(function(){

                var $self = $(this);

                var $box = $self.parent().find('.fieldInfo-boxinfo');
                if ($box.length<=0) {
                    $box = $('<div />', {
                        'class' : 'fieldInfo-boxinfo ui-state-highlight ui-corner-all',
                        html: '<p>' + $self.attr('title') + '</p>'
                    });
                    $box.hide();
                    $self.parent().prepend($box);
                }
                $box.slideDown('slow');
            },
            function(){

                var $box = $(this).parent().find('.fieldInfo-boxinfo:eq(0)');
                $box.slideUp('slow', function(){
                    $(this).remove();
                });
            });

            return this;
        },
        
        standardError : function(data) {
            var self = this;
            var $_dialog = $(self.element).klearModule("getModuleDialog");
            var $message = $('<div><div class="dialogMessage">' + data.message + '</div></div>');

            $_dialog.moduleDialog(
                "option", 
                "buttons",
                 [
                    {
                        text: $.translate("Close", [__namespace__]),
                        click: function() {
                            $_dialog.moduleDialog("close");
                        }
                    }
                ]
            );
            var _extraCode  = '';
            
            if (typeof data.exceptionCode != 'undefined') {
                var errorDesc = $.klear.fetchErrorByCode(data.exceptionCode);
                if (errorDesc) {
                	_extraCode = ' (' + data.exceptionCode + ')';
                    $message.html(errorDesc.replace(/%message%/, data.message));
                }
            }

            if (typeof data.traceString != 'undefined') {
                var $showTrace = $('<p><a class="show-trace" href="#">Show trace string</a></p>');
                var $trace = $('<div class="trace">' + data.traceString.replace(/\n/g, '<br />') + '</div>');
                var $traceDiv = $('<div />').append($showTrace).append($trace);
                
                $message.append($showTrace).append($trace);
                
                $trace.hide();
                $showTrace.on('click', function(e) {
                    e.preventDefault();
                    $trace.toggle();
                    $_dialog.moduleDialog('option', 'width', 800);
                });
                
            }
                

            $_dialog.moduleDialog("option", "title", $.translate("Error", [__namespace__])  + _extraCode);
            $_dialog.moduleDialog("updateContent", $message);
        }

    });


})(jQuery);
