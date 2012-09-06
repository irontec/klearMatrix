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
        _setOption : function(key, value) {
            $.Widget.prototype._setOption.apply(this,arguments);
        },

        _parseDefaultItems : function() {

        	if (!this.options.data.title) {
        		return;
        	}

            var defaultValue, defaultColumn, count = false;

            if ($.isArray(this.options.data.values) && this.options.data.values.length > 0) {
            	 for(var i in this.options.data.columns) {
            		 if (count === false) {
            			 defaultColumn = this.options.data.columns[i];
            			 count = true;
            		 }

            		 if (this.options.data.columns[i]['default']) {
            			 defaultColumn = this.options.data.columns[i];
            			 break;
            		 }
            	 }

            	 var defaultValue = this.options.data.values[0][defaultColumn.id];

            	 if (defaultColumn.multilang) {
            		 defaultValue = defaultValue[this.options.data.defaultLang];
            	 }

             } else {

            	 defaultValue = '';

             }

            this.options.data.title =  this.options.data.title
             								.replace(/\%parent\%/,this.options.data.parentIden)
             								.replace(/\%item\%/,defaultValue);


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
        _getClearText : function($item) {
            if (!$item.is(".multilang")) {
                return $item.contents().first().text()
            }

            if ($(".multilangValue",$item).length>0) {
                if ($(".selected",$item).length == 1) {
                    return $(".selected",$item).contents().first().text()
                } else {
                    return $(".multilangValue:eq(0)",$item).contents().first().text()
                }
            } else {
                return false;
            }

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
            	.on('click.closeTab','.closeTab',function(e) {
                e.preventDefault();
                e.stopPropagation();

                self.klearModule("close");
            });

            $('select:not(.multiselect,.notcombo)',this.element.klearModule("getPanel"))
            	.combobox({
            		'selected' : function(event,ui) {
            			$(this).trigger("manualchange")
            		}
            	});

            $('a.option.screen',this.element.klearModule("getPanel"))
            	.on('mouseup.screenOption')
            	.on('mouseup.screenOption',function(e) {

                e.preventDefault();
                e.stopPropagation();

                var _container = self.klearModule("getContainer");

                var _iden = "#tabs-" + self.klearModule("option","file")
                            + '_' + $(this).data("screen");

                if ($(this).data("multiinstance")) {
                    _iden += '_' + Math.round(Math.random(1000,9999)*100000);
                } else {
                    _iden += '_' + $(this).parents("tr:eq(0)").data("id");
                }

                if ($(_iden).length > 0) {
                    _container.tabs('select', _iden);
                    return;
                }

                var _newIndex = self.klearModule("option","tabIndex")+1;
                var _menuLink = $(this);

                var _parentHolder = _self._resolveParentHolder(this);

                if ($(this).hasClass("_fieldOption")) {
                    _menuLink.addClass("ui-state-highlight");
                }

                var tabTitle = ($(".default",_parentHolder).length>0) ?
                        _self._getClearText($(".default",_parentHolder)) : $(this).attr("title");

                _container.one( "tabspostadd", function(event, ui) {

                    var $tabLi = $(ui.tab).parent("li");

                    // Seteamos como menuLink <- enlace "generador", el enlace que lanza el evento
                    $tabLi.klearModule("option","menuLink",_menuLink);
                    $tabLi.klearModule("option","parentScreen",self);
                    $tabLi.klearModule("option","title",tabTitle);

                    // Actualizamos el file, al del padre (En el constructor se pasa "sucio")
                    $tabLi.klearModule("option","file",self.klearModule("option","file"));

                    // Seteamos el valor para dispatchOptions
                    var _dispatchOptions = {
                        screen : _menuLink.data("screen"),
                        pk : _parentHolder.data("id"),
                        post : {
                            callerScreen : _self.options.data.screen,
                        }
                    };

                    // Si la pantalla llamante tiene condición (parentId -- en data --
                    // enviarlos a la nueva pantalla
                    if (_self.options.data.parentId) {
                        _dispatchOptions.post.parentId = _self.options.data.parentId;
                        _dispatchOptions.post.parentScreen = _self.options.data.parentScreen;
                    }


                    // hioghlight on hover
                    _menuLink.data("relatedtab",$tabLi);

                    $tabLi.klearModule("option","dispatchOptions",_dispatchOptions)
                        .klearModule("reload");


                });

                // Klear open in background
                $.klear.checkNoFocusEvent(e, $(self.klearModule("getPanel")).parent(), $(this));

                _container.tabs( "add", _iden, tabTitle,_newIndex);

            })
            	.off('click.screenOption')
            	.on('click.screenOption',function(e) {
                // Paramos el evento click, que salta junto con mouseup al hacer click con botón izquierdo
                e.preventDefault();
                e.stopPropagation();
            });



            /*
             * Capturar opciones de diálogo.
             */
            $('a.option.dialog',this.element.klearModule("getPanel"))
            	.off('click.dialogOptions')
            	.on('click.dialogOptions',function(e,data) {

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
                    callerScreen : _self.options.data.screen,
                };

                // Si la pantalla llamante tiene condición (parentId -- en data --
	            // enviarlos a la nueva pantalla

	            if (_self.options.data.parentId) {
	            	_postData.parentId = _self.options.data.parentId;
	            	_postData.parentScreen = _self.options.data.parentScreen;
	            }

	            if (data && typeof data.params != undefined) {
		            $.extend(_postData,data.params);
	            }

	            if ($(this).data("params")) {
	            	$.extend(_postData,$(this).data("params"));
	            }

	            $.klear.request(
                        {
                            file: self.klearModule("option","file"),
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
                .on('mouseup.dialogOptions',function(e) {
                // Paramos el evento mouseup, para no llegar al tr
                e.preventDefault();
                e.stopPropagation();
            });

            /*
             * Capturar opciones de command (Siempre request externo -- no callback available!
             * TO-DO: Callback JS? when-ever need will be implemented
             */

            $('a.option.command',this.element.klearModule("getPanel"))
            	.off('click.commandAction')
            	.on('click.commandAction',function(e,data) {

                e.preventDefault();
                e.stopPropagation();

                var external = data && data.external || false;
                external = $(this).data("external")? true: external;

                var _container = self.klearModule("getContainer");

                switch (_self.options.moduleName) {
                    case 'list':
                        var _parentHolder = $(this).parents("tr:eq(0)");
                        $(this).on('mouseup',function(e) {
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
                    callerScreen : _self.options.data.screen,
                };

                // Si la pantalla llamante tiene condición (parentId -- en data --
	            // enviarlos a la nueva pantalla
	            if (_self.options.data.parentId) {
	            	_postData.parentId = _self.options.data.parentId;
	            	_postData.parentScreen = _self.options.data.parentScreen;
	            }

	            if (data && typeof data.params != undefined) {
		            $.extend(_postData,data.params);
	            }

                $.klear.request(
                        {
                            file: self.klearModule("option","file"),
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


            $("[title]:not(.fieldInfo-box)",this.element.klearModule("getPanel")).tooltip();

            $(".fieldInfo-box",this.element.klearModule("getPanel")).toggle(function(){

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


            $_dialog.moduleDialog("option","buttons",
                     [
                          {
                            text: $.translate("Close", [__namespace__]),
                            click: function() {
                                $_dialog.moduleDialog("close");
                            }
                        }
                    ]
            );

            if (typeof data.code != 'undefined') {
            	var errorDesc;
            	if (errorDesc = $.klear.fetchErrorByCode(data.code)) {
            		var _oldMessage = data.message;
            		data.message = errorDesc;

            		data.message = data.message.replace(/%message%/,_oldMessage);
            	}

            }


            $_dialog.moduleDialog("option","title",$.translate("Error", [__namespace__]));
            $_dialog.moduleDialog("updateContent",data.message);

        }

    });


})(jQuery);
