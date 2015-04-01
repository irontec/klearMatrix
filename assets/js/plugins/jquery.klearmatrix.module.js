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
                return this.options.data[value];
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

            // Recarga el nombre de la pestaña con el título calculado.
            //if (this.options.data && this.options.data.title) {
            //    this.element.klearModule("updateTitle",this.options.data.title);
            //}
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

            if ($(element).data("multiitem")) {
                return $("td.multiItem input:checked");
            }

            if ($(element).data("parentHolderSelector")) {

                var _candidateParent = $(element).parents($(element).data("parentHolderSelector"));

                if (_candidateParent.length > 0) {
                    return _candidateParent;
                }
            }

            var modulecheck;
            // Si es un módulo con parent
            if (this.options.moduleParent) {
                modulecheck = this.options.moduleParent;
            } else {
                modulecheck = this.options.moduleName;
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
                    throw 'no parentHolder found for option';
                break;
            }
        },
        /*
         * Eventos comunes en todos los controladores para campos
         */
        _registerFieldsEvents : function() {

            var self = this;
            var _self = this.element;

            if ($(".fieldDecorator",_self.klearModule("getPanel")).length > 0) {

                $(".fieldDecorator",_self.klearModule("getPanel")).each(function() {

                    var _post = $(this).data();
                    $.extend(_post, {value : $("#" + $(this).attr("rel")).val()});

                    var parentSelector = (self.options.moduleName == 'list')? "tr:eq(0)":"form:eq(0)";

                    var _targetCommand = '';
                    if (! $(this).data('command')) {
                        _targetCommand = $(this).data('fielddecorator') + "_command";
                        $.console.log("ATENCIÓN: El atributo command no está configurado. Se utiliza el valor por defecto: " + $(this).data('fielddecorator') + "_command");
                    } else {
                        _targetCommand = $(this).data('command');
                    }

                    var requestData = {
                        file: _self.klearModule("option","file"),
                        pk: $(this).parents(parentSelector).data("id"),
                        type : 'command',
                        post: _post,
                        command : _targetCommand
                    };

                    var request = $.klear.buildRequest(requestData);

                    var _url = request.action; //encodeURI()
                     _url += '&' + $.param(request.data);

                     $(this).attr("href", _url).html($(this).data("fielddecoratort"));

                    if ($.fn["klearmatrix."+ $(this).data('field') + $(this).data('fielddecorator')]) {

                        var methodName = $(this).data('field') + $(this).data('fielddecorator');
                        $(this)[methodName]({"parent": self});
                    }
                });
            }

            if ($(".filePreview",_self.klearModule("getPanel")).length>0) {
                 $(".filePreview",_self.klearModule("getPanel")).each(function() {

                     var _post;

                     if ($(this).data("filename")) {
                         _post = {filename:$(this).data("filename")};
                     } else {
                         _post = {filename:$(this).parent("span:eq(0)").data("filename")};
                     }
                     var _validData = ['width','height','crop'];
                     var $self = $(this);
                     var imageAttribs = '';
                     $.each(_validData,function(i,value) {
                         if ($self.data(value)) {
                             _post[value] = $self.data(value);
                         }

                         if (value == 'width' || value == 'height') {
                             imageAttribs += value + '="'+_post[value]+'px" ';
                         }
                     });
                     var parentSelector = (self.options.moduleName == 'list')? "tr:eq(0)":"form:eq(0)";

                     var requestData = {
                             file: _self.klearModule("option","file"),
                             pk: $(this).parents(parentSelector).data("id"),
                             type : 'command',
                             post: _post,
                             command : $(this).data('command')
                     };

                     var item = $("<img class=\"imgFilePreviewList\" "+imageAttribs+" />");

                     var request = $.klear.buildRequest(requestData);
                     var _url = request.action; //encodeURI()
                     _url += '&' + $.param(request.data);
                     item.attr("src", _url);
                     $(this).replaceWith(item);
                });
            }

            if ($(".fieldInfo-tooltip",_self.klearModule("getPanel")).length>0) {
                $(".fieldInfo-tooltip",_self.klearModule("getPanel")).tooltip({
                    'content': function(){return $el.attr('data-title');}
                });
            }

            if ($(".tooltip",_self.klearModule("getPanel")).length>0) {
                $(".tooltip",_self.klearModule("getPanel")).tooltip({
                    'content': function(){return $(this).attr('data-title');}
                });
            }

            if ($("input.auto, textarea.auto",_self.klearModule("getPanel")).length > 0) {
                $("input.auto, textarea.auto",_self.klearModule("getPanel")).each(function() {
                    if ($(this).data("plugin")) {

                        var pluginSettings = {};

                        $.each($(this).data(),function(idx, value) {
                            if (idx.match(/setting-*/)) {

                                idx = idx.replace('setting', '');
                                idx = idx.charAt(0).toLowerCase() + idx.substr(1); //lcfirst
                                if (!pluginSettings) {
                                    pluginSettings = {};
                                }

                                pluginSettings[idx] = value;
                            }
                        });

                        (function lazyPluginLoad(target, pluginName, settings) {
                            if (!$.fn[pluginName]) {
                                this.count++;
                                if (this.count > 20) {
                                    return;
                                }
                                setTimeout(function() {
                                    lazyPluginLoad(target, pluginName, settings);
                                },50);
                            }

                            if (target[pluginName]) {
                                settings._contentTab = _self;
                                if (target.data("basename")) {
                                    target[pluginName](settings, self.options.data.columns[target.data("basename")].config);
                                } else {
                                    target[pluginName](settings);
                                }
                            }

                        })($(this), $(this).data("plugin"), pluginSettings);
                    }
                });
            }
            return this;
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

            this._resolveAutoOption();
            this._doGhostList();

            var $viewPort = $(this.element.klearModule("getPanel"));
            // Se trata de un dialogo!
            if (this.options.parent) {
                $viewPort = $($(this.options.parent).klearModule("getPanel"));
            }

            if ($('select:not(.notcombo, [data-decorator]).multiselect', $viewPort).length > 0) {
                $('select:not(.notcombo, [data-decorator]).multiselect', $viewPort).multiselect({
                    container: $viewPort,
                    selectedList: 4,
                    selectedText: $.translate("# of # selected"),
                    checkAllText: $.translate("Select all"),
                    uncheckAllText: $.translate("Unselect all"),
                    noneSelectedText: $.translate("Select an option"),
                    selectedText: $.translate("# selected"),
                    position: {
                          my: 'center',
                          at: 'center'
                     }
                }).multiselectfilter();
            }

            $('select:not(.multiselect, .notcombo, [data-decorator])', $viewPort)
                .selectBoxIt({theme: "jqueryui",autoWidth: false, viewport: $viewPort})
                .add($('input[type=hidden].readOnlySelectField', $viewPort))
                .on("change",function() {

                    var isReadOnlyHiddenInput = (this.nodeName.toLowerCase() == "input");

                    // Necesario para que los select "invisibles" cojan la anchura correcta (el el filtrado por ejemplo)
                    if (!isReadOnlyHiddenInput && !$(this).data("resizeTrick") && $(this).data("target-for-change")) {

                        var cssClasses2Keep = $(this).data("target-for-change").attr("class");
                        $(this).selectBoxIt({autoWidth: true})
                        .data("resizeTrick",true)
                        .data("selectBoxIt").refresh(function () {
                            this.dropdown.addClass(cssClasses2Keep);
                        });
                    }

                    if ($(this).data('autofilter-select-by-data')) {
                        var _configDataArray = $(this).data('autofilter-select-by-data').split("|");

                        var _configData, _fieldToBeFiltered, _filterData, _targetValue, selectBox, $holder, $field;
                        for (var cdIdx in _configDataArray) {

                            _configData = _configDataArray[cdIdx];

                            _fieldToBeFiltered = _configData.split(":")[0];
                            _filterData = _configData.split(":")[1];
                            _targetValue = $(this).val();

                            $field = $("select[name="+_fieldToBeFiltered+"]",$(this).parents("form:eq(0)"));
                            var $targetComboId = $field.attr("id");

                            if ($targetComboId) {
                                $targetComboId = "hiddenComboValues" + $targetComboId;
                            }

                        	$field.data("olds", $field.val());
                            $holder = $("#" + $targetComboId);
                            
                            if ($holder.length < 1) {
                                $holder = $("<select class='hidden' id='"+ $targetComboId +"' />");
                                $(this).parents("form:eq(0)").append($holder);
                            } else {
                                $holder.children('option').appendTo($field);
                                $field.val($field.data("olds"));
                                
                                var originalValue = $field.data("preload");
                                var originalValueOption = $field.find("option[value="+ originalValue +"]");
                                if (originalValue && originalValueOption) {
                                	originalValueOption.prop("selected", true);
                                } else {
                                	$field.find("option:eq(0)").prop("selected", true);
                                }
                            }
                            $parent = $field.parents(".container:eq(0)");
                            $parent.css("opacity",'0.5');
                            selectBox = $field.data("selectBoxIt");

                            var filterCriterion = "[value=__NULL__],[data-"+_filterData+"="+_targetValue+"]";
                            var filteredOptionElements = $("option",$field).not(filterCriterion);
                            filteredOptionElements.appendTo($holder);
                            if (typeof selectBox !== "undefined") {
                                selectBox.refresh();
                                selectBox.dropdown.trigger("click");
                                selectBox.close();                                
                            } else if (typeof $field.multiselect !== "undefined"){
                            	$field.multiselect("refresh");
                            }

                            $parent.css({'opacity':1});
                            $(this).trigger("manualchange");
                        }
                    } else {
                        $(this).trigger("manualchange");
                    }

                })
                .on("postmanualchange", function () {
                    if ($(this).filter("select").data("target-for-change")) {
                        var cssClasses2Keep = $(this).data("target-for-change").attr("class");
                        $(this).selectBoxIt().data("selectBoxIt").refresh(function () {
                            this.dropdown.addClass(cssClasses2Keep);
                        });
                    }
                    $(this).trigger("blur.selectBoxIt");
                })
                .on('open', function() {

                    // Fix sólo para dialogos!
                    if (!_self.options.parent) {
                        return;
                    }
                    var $_parents = $(this).parents("div").toArray();
                    while (parent = $_parents.shift()) {
                        if ($(parent).hasClass("ui-tabs-panel")) {
                            break;
                        }
                        $(parent)
                            .data("prevOverflow", $(parent).css("overflow"))
                            .css("overflow","visible");
                    }

                }).on('close', function() {

                    // Fix sólo para dialogos!
                    if (!_self.options.parent) {
                        return;
                    }

                    var $_parents = $(this).parents("div").toArray();
                    while (parent = $_parents.shift()) {
                        if (!$(parent).data("prevOverflow")) {
                            break;
                        }
                        $(parent).css("overflow",$(parent).data("prevOverflow"));
                    }
                })
                .each(function() {

                    $(this).data("target-for-change",$(this).next("span").children("span:eq(0)"));
                    if ($(this).is("[data-autofilter-select-by-data]")) {
                        $(this).trigger("change");
                    }
                });

            $('a.option.screen', this.element.klearModule("getPanel"))
            .off('mouseup.screenOption')
            .on('mouseup.screenOption', function(e) {

                e.preventDefault();
                e.stopPropagation();

                var _menuLink = $(this);

                if (_self.options.parent) {
                    self = _self.options.parent;
                }

                var _container = self.klearModule("getContainer");
                var _file = self.klearModule("option", "file");

                if (_menuLink.data("externalfile")) {
                    _file= _menuLink.data("externalfile");
                }

                var _screen = _menuLink.data("screen");
                var _iden = "#tabs-" + _file;

                if (!_menuLink.data("externalremovescreen")) {
                    _iden += '_' + _screen;
                }

                var _parentHolder = _self._resolveParentHolder(this);

                if (!_menuLink.data("externalnoiden")) {

                    if (_menuLink.data("multiinstance")) {
                        _iden += '_' + Math.round(Math.random(1000, 9999)*100000);
                    } else {
                        if (_menuLink.data("externalid")) {
                            _iden += '_' + _menuLink.data("externalid");
                        } else {
                            _iden += '_' + _parentHolder.data("id");
                        }
                    }
                }

                var _curPk = _parentHolder.data("id");

                if (_menuLink.data("externalname")) {
                    var _field = _menuLink.parent().find("[name='"+_menuLink.data("externalname")+"']");
                    if (_field.length >0) {
                        _curPk = _field.val();
                    }
                }
                if (_menuLink.data("externalid")) {
                    _curPk = _menuLink.data("externalid");
                }

                // Seteamos el valor para dispatchOptions
                var _dispatchOptions = {
                    post : {
                        callerScreen : _self.options.data.screen
                    }
                };

                if (!_menuLink.data("externalremovescreen")) {
                    _dispatchOptions['screen'] = _menuLink.data("screen");
                }

                if (!_menuLink.data("externalnoiden")) {
                    _dispatchOptions ['pk'] = _curPk;
                }

                var _searchOps = false;

                // de momento "damos por hecho" que serán campos select, y llevan implícito un 'eq';
                if (_menuLink.data("externalsearchby")) {
                    _searchOps = {searchFields : {}, searchOps : {}};
                    var _searchField = _menuLink.data("externalsearchby");
                    _searchOps['searchFields'][_searchField] = [_curPk];
                    _searchOps['searchOps'][_searchField] = ['eq'];
                }

                $.extend(_dispatchOptions['post'], _searchOps);

                var tabTitle;
                if (_menuLink.data("externaltitle")) {
                    tabTitle = _menuLink.data("externaltitle");
                } else {
                    tabTitle = _menuLink.tooltip("close").attr("title");
                }

                if ($(this).hasClass("_fieldOption")) {
                    _menuLink.addClass("ui-state-highlight");
                }

                // Si el tab ya está abierto
                if ($(_iden).length > 0) {

                    $selTabLi = _container
                                    .tabs('select', _iden)
                                    .find(".ui-tabs-selected:eq(0)");

                    if (_searchOps !== false) {
                        $selTabLi
                            .klearModule("option", "dispatchOptions", _dispatchOptions)
                            .klearModule("reDispatch");
                    }

                    $selTabLi.klearModule("updateTitle", tabTitle);

                    return;
                }

                var _newIndex = self.klearModule("option", "tabIndex")+1;

                _container.one( "tabspostadd", function(event, ui) {

                    var $tabLi = $(ui.tab).parent("li");

                    // Seteamos como menuLink <- enlace "generador", el enlace que lanza el evento
                    $tabLi.klearModule("option", "menuLink", _menuLink);
                    $tabLi.klearModule("option", "parentScreen", self);
                    $tabLi.klearModule("updateTitle", tabTitle);

                    // Actualizamos el file, al del padre (En el constructor se pasa "sucio")
                    $tabLi.klearModule("option", "file", _file);

                    // Si la pantalla llamante tiene condición (parentId -- en data --
                    // enviarlos a la nueva pantalla
                    if (_self.options.data.parentId) {
                        _dispatchOptions.post.parentId = _self.options.data.parentId;
                        _dispatchOptions.post.parentScreen = _self.options.data.parentScreen;
                    }

                    // highlight on hover
                    _menuLink.data("relatedtab", $tabLi);

                    $tabLi
                        .klearModule("option", "dispatchOptions", _dispatchOptions)
                        .klearModule("reload");
                });

                // Klear open in background
                $.klear.checkNoFocusEvent(e, $(self.klearModule("getPanel")).parent(), $(this));
                _container.tabs( "add", _iden, tabTitle, _newIndex);

            }).off('click.screenOption')
                .on('click.screenOption', function(e) {
                // Paramos el evento click, que salta junto con mouseup al hacer click con botón izquierdo
                e.preventDefault();
                e.stopPropagation();
            }).filter("[data-shortcut]").each(function() {
                var $option = $(this);
                var keyCode = $(this).data("shortcut").toUpperCase().charCodeAt(0);
                self.klearModule("registerShortcut",keyCode,function() {
                    $option.trigger("mouseup.screenOption");
                });
            });

            /*
             * Capturar opciones de diálogo.
             */

            $('a.option.dialog', this.element.klearModule("getPanel"))
            .off('click.dialogOption')
            .on('click.dialogOption', function(e, data) {

                e.preventDefault();
                e.stopPropagation();

                var external = data && data.external || false;

                if (_self.options.parent) {
                    self = _self.options.parent;
                }

                var _parentHolder = _self._resolveParentHolder(this);

                var curPK = _parentHolder.data("id");

                if ($(_parentHolder).length > 1) {
                    curPK = [];
                    $(_parentHolder).each(function() {
                       curPK.push($(this).data("id"));
                    });
                }

                var $caller = $(this);

                $(self).klearModule("showDialog",
                        '<br />',
                        {
                            title: $(this).attr("title") || '',
                            template : '<div class="ui-widget">{{html text}}</div>',
                            width: 'auto'
                        });

                var $_dialog = $(self).klearModule("getModuleDialog");
                $(self).moduleDialog("setAsLoading");
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
                        pk : curPK,
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
            .off('mouseup.dialogOption')
            .on('mouseup.dialogOption', function(e) {
                // Paramos el evento mouseup, para no llegar al tr
                e.preventDefault();
                e.stopPropagation();
            })
            .filter("[data-shortcut]").each(function() {
                var $option = $(this);
                var keyCode = $(this).data("shortcut").toUpperCase().charCodeAt(0);
                self.klearModule("registerShortcut",keyCode,function() {
                    $option.trigger("click.dialogOption");
                });
            });

            /*
             * Capturar opciones de command (Siempre request externo -- no callback available!
             * TO-DO: Callback JS? when-ever need will be implemented
             */

            $('a.option.command', this.element.klearModule("getPanel"))
            .off('mouseup.commandOption')
            .on('mouseup.commandOption',function(e) {
                e.preventDefault();
                e.stopPropagation();
            })
            .off('click.commandOption')
            .on('click.commandOption', function(e, data) {

                e.preventDefault();
                e.stopPropagation();

                var selfCommand = $(this);

                var external = data && data.external || false;
                external = selfCommand.data("external")? true: external;

                if (_self.options.parent) {
                    self = _self.options.parent;
                }

                var _parentHolder = _self._resolveParentHolder(this);

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

                var _file = $(self).klearModule("option", "file");

                var disabledTime = selfCommand.data('disabledtime') || null;
                if (disabledTime) {

                    selfCommand.button("option", "disabled", "disabled");
                    window.setTimeout(function(){
                        selfCommand.button("option", "disabled", false);
                    }, disabledTime);
                }

                $.klear.request(
                    {
                        file: _file,
                        type: 'command',
                        command : selfCommand.data("command"),
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

            }).filter("[data-shortcut]").each(function() {
                var $option = $(this);
                var keyCode = $(this).data("shortcut").toUpperCase().charCodeAt(0);
                self.klearModule("registerShortcut",keyCode,function() {
                    $option.trigger("click.commandOption");
                });
            });
              

            $("a[title]:not(.fieldInfo-box),span[title]", this.element.klearModule("getPanel")).tooltip();

            $(".fieldInfo-box", this.element.klearModule("getPanel")).toggle(function(){

                var $self = $(this);

                var $box = $self.parent().find('.fieldInfo-boxinfo');
                if ($box.length<=0) {
                    $box = $('<div />', {
                        'class' : 'fieldInfo-boxinfo ui-state-highlight ui-corner-all',
                        html: '<p>' + $self.data('title') + '</p>'
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
                        text: $.translate("Close"),
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

                $message.append($showTrace).append($trace);

                $trace.hide();
                $showTrace.on('click', function(e) {
                    e.preventDefault();
                    $trace.toggle();
                    $_dialog.moduleDialog('option', 'width', 800);
                });
            }

            $_dialog.moduleDialog("option", "title", $.translate("Error")  + _extraCode);
            $_dialog.moduleDialog("updateContent", $message);
        },

        _resolveAutoOption : function() {
            var $container = $(this.element).klearModule("getContainer");
            $("span.autoOption",$container).each(function() {
                var dataValues = $(this).data();
                var entity = null;
                var fieldValue = null;

                $(this).replaceWith($.klearmatrix.template.helper.option2HTML(dataValues, 'Field', entity, fieldValue));
            });
        },
        _doGhostList : function() {
            var $container = $(this.element).klearModule("getContainer");

            $("ul.ghostList", $container).each(function() {

                $optionHolder = $(this).next(".ghostListOptions");
                $optionHolder.hide();
                $futureOptions = $(".opClone",$(this));

                $("a",$optionHolder).each(function() {
                    var link = false;
                    if ($(this).hasClass("screen")) {
                        link = $(this).data("screen");
                    } else if ($(this).hasClass("dialog")) {
                        link = $(this).data("dialog");
                    } else if ($(this).hasClass("command")) {
                        link = $(this).data("command");
                    } else {
                        return;
                    }

                    var optString = $("<div />").append($(this)).html();
                    $futureOptions.filter("[data-link='"+link+"']").replaceWith(optString);
                });
            });

            $(".ghostListCounter input",$container)
            .off('doSum')
            .on('doSum',function() {
                var $parent = $(this).parent();
                $("span.counter", $parent).html($parent.next("ul.ghostList").find("li:visible").length);
            })
            .off('keyup.ghostfilter')
            .on('keyup.ghostfilter',function() {
                var $items =  $(this).parent().next("ul.ghostList").find("li");
                $items.show();
                if ($(this).val() == "") {
                    $(this).trigger("doSum");
                    return;
                }
                $items.not(":contains("+$(this).val()+")").hide();
                $(this).trigger("doSum");

            })
            .trigger('keyup.ghostfilter');
            $(".ghostTableCounter input",$container)
            .off('doSum')
            .on('doSum',function() {

                var $parent = $(this).parent();
                $("span.counter", $parent).html($parent.next("div.ghostTableContainer").find("tr.hideable:visible").length);
            })
            .off('keyup.ghostfilter')
            .on('keyup.ghostfilter',function() {
                var $items =  $(this).parent().next("div.ghostTableContainer").find("tr.hideable");
                $items.show();
                if ($(this).val() == "") {
                    $(this).trigger("doSum");
                    return;
                }
                $items.not(":contains("+$(this).val()+")").hide();
                $(this).trigger("doSum");

            })
            .trigger('keyup.ghostfilter');
        }
    });

})(jQuery);
