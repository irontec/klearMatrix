;(function load($) {

    if (!$.klear.checkDeps(['$.klearmatrix.module','$.ui.form','$.fn.autoResize','$.fn.h5Validate','Crypto'],load)) {
        return;
    }

    var __namespace__ = "klearmatrix.edit";

    $.widget("klearmatrix.edit", $.klearmatrix.module, {
        options: {
            data : null,
            moduleName: 'edit',
            theForm : false
        },

        _super: $.klearmatrix.module.prototype,

        _create : function() {
            this._super._create.apply(this);
        },

        _formValidationErrors : {
            'customError' : $.translate('undefined error',[__namespace__]),
            'patternMismatch' : $.translate('invalid pattern',[__namespace__]),
            'rangeOverflow' : $.translate('range overflow',[__namespace__]),
            'rangeUnderflow' : $.translate('range underflow',[__namespace__]),
            'stepMismatch' : $.translate('step mismatch',[__namespace__]),
            'tooLong' : $.translate('the value is too long',[__namespace__]),
            'typeMismatch' : $.translate('type mismatched',[__namespace__]),
            'valueMissing' : $.translate('this is a required field',[__namespace__])
        },

        _init: function() {

            $.extend(this.options.data,{randIden:Math.round(Math.random(1000,9999)*100000)});

            this.options.data.title = this.options.data.title || this.element.klearModule("option","title");

            var tplName = (this.options.data.mainTemplate) ? this.options.data.mainTemplate : "klearmatrixEdit";

            var $appliedTemplate = this._loadTemplate(tplName);

            $(this.element.klearModule("getPanel")).append($appliedTemplate);

            this._applyDecorators()
                ._registerReDispatchSavers()
                ._initFormElements()
                ._registerBaseEvents()
                ._registerEvents()
                ._registerFieldsEvents()
                ._registerMainActionEvent();

        },

        _applyDecorators : function() {
            $(".generalOptionsToolbar a",this.element.klearModule("getPanel")).each(function() {
                $(this).button();
            });
            return this;
        },

        _registerReDispatchSavers : function() {
            var self = this;

            this.element.klearModule("option","PreDispatchMethod",function() {
                // Se ejecutará en el contexto de klear.module, el post dispatch será un klearmatrix.edit nuevo
                this.savedValues = {};
                var _selfklear = this;

                $("select.changed,input.changed,textarea.changed",self.options.theForm).each(function() {
                    _selfklear.savedValues[$(this).attr("name")] = $(this).val();
                });

            });

            this.element.klearModule("option","PostDispatchMethod",function() {
                if (!this.savedValues) return;
                $.each(this.savedValues,function(name,value) {
                    $("[name='"+name+"']",self.options.theForm).val(value).trigger("manualchange");
                });
                this.savedValues = {};
            });

            return this;
        },

        _registerMainActionEvent : function() {

            var self = this;

            this.options.theForm.on('submit',function(e) {
                e.preventDefault();
                e.stopPropagation();

                var validForm = $(this).h5Validate("allValid");
                if (!validForm) {

                    return;
                }

                var _launchAction = function() {
                    $(self.element).klearModule("showDialog",
                            '<br />',
                            {
                                title: self.options.data.title,
                                template : '<div class="ui-widget">{{html text}}</div>',
                                buttons : []
                            });

                    $(self.element).klearModule("option","moduleDialog").moduleDialog("setAsLoading");

                    self._doAction.call(self);
                };

                if (self.options.data.actionMessages &&
                    self.options.data.actionMessages.before
                ) {

                    var curMsg = 0;

                    (function showMessage() {

                        // Si se invoca showMessage y no hay más mensajes pendientes, ejecutamos acción
                        if (!self.options.data.actionMessages.before[curMsg]) {
                            _launchAction();
                            return;
                        }
                        var _msg = self.options.data.actionMessages.before[curMsg];

                        curMsg++;

                        $(self.element).klearModule("showDialog",
                                '<br />',
                                {
                                    title: _msg.title,
                                    template : '<div class="ui-widget">{{html text}}</div>',
                                    buttons : []
                                });
                        var $dialog = $(self.element).klearModule("getModuleDialog");
                        var buttons = [];
                        for(var i in _msg.action) {
                            var _ac = _msg.action[i];
                            buttons.push((function(_ac) {
                                return {
                                    text: _ac.label,
                                    click: function() {

                                        if (_ac['return'] === true) {
                                            // Volvemos a showMessage por si hubiera más mensajes de "before"
                                            // o lanzar _launchAction()
                                            showMessage();
                                            $dialog.moduleDialog("close");
                                            return;
                                        } else{
                                            $dialog.moduleDialog("close");
                                            return;
                                        }
                                    }
                                };
                            })(_ac));
                        }

                        $dialog.moduleDialog("option","buttons",buttons);
                        $dialog.moduleDialog("updateContent",_msg.message);

                    })();

                } else {
                    _launchAction();
                }

            });

            return this;
        },

        _doAction : function() {

            (function(self) {
                var $self = $(self.element);
                var $dialog = $self.klearModule("option","moduleDialog");
                var postData = self.options.theForm.serializeArray();

                $.klear.request(
                        {
                            file: $self.klearModule("option","file"),
                            type: 'screen',
                            execute: 'save',
                            pk: self.options.theForm.data("id"),
                            screen: self.options.data.screen,
                            post : postData
                        },
                        function(data) {

                            if (data.error) {
                                self.standardError(data.error);

                            } else {
                                var $parentModule = $self.klearModule("option","parentScreen");
                                if ($parentModule) {
                                    $parentModule.klearModule("reDispatch");
                                }

                                self._initSavedValueHashes();
                                self.options.theForm.trigger('updateChangedState');
                                if ($("input[name=autoclose]",$self.klearModule("getPanel")).is(":checked")) {
                                    $dialog.moduleDialog("close");
                                    $self.klearModule("close");
                                    return;
                                }
                            }

                            $dialog.moduleDialog("option","title",'');
                            $dialog.moduleDialog("option","buttons",
                                     [
                                          {
                                            text: $.translate("Close", [__namespace__]),
                                            click: function() {
                                                $(this).moduleDialog("close");
                                                $self.klearModule("close");
                                            }
                                        },
                                        {
                                            text: $.translate("Edit again", [__namespace__]),
                                            click: function() {
                                                $(this).moduleDialog("close");
                                            }
                                        }
                                    ]
                            );

                            $dialog.moduleDialog("updateContent",data.message);

                            var triggerData = {'data': data, 'postData': postData};
                            $self.trigger('postMainActionHook', triggerData);

                        },
                        // Error from new/index/save
                        function(data) {
                            self.standardError(data);
                        }
                );
            })(this); // Invocamos Closure
        },

        _initSavedValueHashes : function() {

            $("select,input,textarea",this.options.theForm).each(function() {
                var _val = (null == $(this).val())? '':$(this).val();

                if ($(this).val() == '__NULL__') {
                    if ( $(this).data('preload') != '' ) {
                        _val = $(this).data('preload').toString();
                    }
                }

                var _hash = Crypto.MD5(_val);
                $(this)
                    .data("savedValue",_hash)
                    .trigger("manualchange");
            });

            var self = this;
            var _errorTemplate = $('<span class="ui-widget ui-state-error ui-corner-all klearFieldError">'
                                    + '<span class="ui-icon ui-icon-alert"></span><span class="content"></span></span>');

            this.options.theForm
                .h5Validate({
                    focusout: false,
                    focusin: false,
                    change: false,
                    keyup: false,
                    allValidSelectors: 'input.hiddenFile, :input:visible:not(:button):not(:disabled):not(.novalidate), \
                                        select[required]:not(:disabled):not(.novalidate)'
                })
                .on('validated',function(formElement,validation) {

                    var _inputContainer = $(formElement.target).parents("div:eq(0)");

                    if (true === validation.valid) {
                        $(".klearFieldError",_inputContainer).slideUp(function() {
                            $(this).remove();
                        });

                        return;
                    }

                    var errorCollection = [];

                    for (errorType in self._formValidationErrors) {
                        if (validation[errorType] === true) {

                            var _dataIndex = errorType.toLowerCase();
                            if ($(formElement.target).data(_dataIndex)) {
                                errorCollection.push($(formElement.target).data(_dataIndex));
                            } else {
                                errorCollection.push(self._formValidationErrors[errorType]);
                            }
                        }
                    }

                    if (errorCollection.length > 0) {
                        if (!$(".klearFieldError",_inputContainer).is("span")) {
                            _errorTemplate.clone().prependTo(_inputContainer);
                        }
                        $(".klearFieldError .content",_inputContainer).html(errorCollection.join('<br />'));

                    } else {

                        $(".klearFieldError",_inputContainer).slideUp(function() {
                            $(this).remove();
                        });

                    }

                });
        },

        //TODO: Este método está creciendo demasiado. Revisar para que no acabe demasiado inflado
        _initFormElements : function() {
            var self = this;
            var _self = this.element;

            this.options.theForm = $("form",$(this.element.klearModule("getPanel")));
            this.options.theForm.form();

            this._initSavedValueHashes();

            if ($("select.multiselect",this.options.theForm).length > 0) {
                $("select.multiselect",this.options.theForm).multiselect({
                    container: this.element.klearModule('getPanel'),
                    selectedList: 4,
                    selectedText: $.translate("# of # selected", [__namespace__]),
                    checkAllText: $.translate('Select all', [__namespace__]),
                    uncheckAllText: $.translate('Unselect all', [__namespace__]),
                    noneSelectedText: $.translate('Select an option', [__namespace__]),
                    selectedText: $.translate('# selected', [__namespace__]),
                    position: {
                          my: 'center',
                          at: 'center'
                     }
                }).multiselectfilter();
            }

            if ($("input.auto, textarea.auto",this.options.theForm).length > 0) {
                $("input.auto, textarea.auto",this.options.theForm).each(function() {
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
                                target[pluginName](settings);
                            }

                        })($(this), $(this).data("plugin"), pluginSettings);
                    }
                });
            }

            if ($(".jmedia",this.options.theForm).length>0) {
                $(".jmedia",this.options.theForm).each(function() {

                    var requestData = {
                            file: _self.klearModule("option","file"),
                            pk: $(this).parents("form:eq(0)").data("id"),
                            type : 'command',
                            post : 'foo=1',
                            command : $(this).data('command')
                    };


                    var item = $("<div />");
                    $(this).replaceWith(item);
                    var controlId = 'controls' + Math.round(Math.random(1,1000)*1000);
                    var controls = $('<div id="'+controlId+'" class="ui-button ui-widget ui-state-default ui-corner-all controls">' +
                            '<a href="#" class="jp-play" tabindex="1"><span class="ui-icon ui-icon-play inline"></span></a>'+
                            '<a href="#" class="jp-pause" tabindex="2"><span class="ui-icon ui-icon-pause inline"></span></a>' +
                            '<div class="jp-progress ui-widget ui-state-default ui-corner-all" ><div class="ui-widget ui-state-active ui-corner-all jp-seek-bar"><div class="ui-widget ui-widget-header jp-play-bar"></div></div></div>'+
                            '<div class="jp-volume-bar ui-widget ui-state-active ui-corner-all"><div class="jp-volume-bar-value ui-widget ui-state-active ui-corner-all"></div></div>'+
                            '<div class="jp-volumenCtrl">' +
                            '<span class="jp-mute"><span class="ui-icon ui-icon-volume-on inline"></span></span>' +
                            '<span class="jp-unmute"><span class="ui-icon ui-icon-volume-off inline"></span></span></div>' +
                            '<div class="jp-timers"><span class="jp-current-time"></span> / <span class="jp-duration"></span></div>'+
                            '</div>');

                    controls.insertAfter(item);
                    var request = $.klear.buildRequest(requestData);
                    item.jPlayer({
                                    ready : function() {
                                        item.jPlayer("setMedia", {
                                            mp3 : encodeURI(request.action)
                                        }).jPlayer("pause");
                                    },
                                    play: function() {
                                        item.jPlayer("pauseOthers");
                                    },
                                    ended : function() {
                                        item.jPlayer("ready");
                                    },
                                    cssSelectorAncestor : '#' + controlId,
                                    swfPath : '../klearMatrix/bin/',
                                    solution:'html,flash',
                                    supplied: "mp3",
                                    oggSupport: false,
                                    wmode:"window"
                                });
                });
            }

            if ($(".password",this.options.theForm).length>0) {
                var isNew = this.options.theForm.data("type") == "new";

                $(".password", this.options.theForm).each(function() {

                    var $self = $(this);
                    var _parent = $self.parent();
                    var _checkbox = $("input:checkbox[rel="+$self.attr("name")+"]",_parent);

                    if (isNew) {
                        _checkbox.parents("span:eq(0)").remove();
                        return;
                    }

                    $(this)
                        .attr("disabled","disabled")
                        .addClass("ui-state-disabled");

                    _checkbox.on("change",function() {
                        if ($(this).is(":checked")) {
                            $self.removeAttr("disabled").removeClass("ui-state-disabled");
                            $self.select().trigger("focus");

                        } else {
                            $self.attr("disabled","disabled").addClass("ui-state-disabled");
                        }
                    });


                });
            }

            if ($(".checkbox",this.options.theForm).length>0) {

                $(".checkbox", this.options.theForm).each(function() {

                    var $self = $(this);
                    var _parent = $self.parent();
                    var _checkbox = $("input:checkbox[rel=" + $self.attr("name") + "]", _parent);

                    _checkbox.on("change",function() {
                        if ($(this).is(":checked")) {
                            $self.val('1');
                        } else {
                            $self.val('0');
                        }
                    });


                });
            }

            if ($(".qq-uploader",this.options.theForm).length>0) {
                $(".qq-uploader",this.options.theForm).each(function() {

                    var _hiddenField = $("#" + $(this).attr("rel"));
                    if (_hiddenField.length == 0) {
                        return;
                    }

                    var item = $("<div />");
                    item
                        .attr("rel",$(this).attr("rel"))
                        .data("command",$(this).data("command"));

                    $(this).replaceWith(item);

                    _hiddenField.on("postmanualchange",function() {
                        var $shownFDesc = $('#new_'+ $(this).attr("id"));
                        if ($(this).hasClass("changed")) {
                            $shownFDesc
                                .html($(this).data("fileDescription"))
                                .css("display","block");
                            $shownFDesc.addClass("changed ui-state-highlight");
                        } else {
                            $shownFDesc.removeClass("changed ui-state-highlight");
                        }
                    });

                    var requestData = {
                            file: _self.klearModule("option","file"),
                            pk: $(this).parents("form:eq(0)").data("id"),
                            type : 'command',
                            command : item.data('command')
                    };

                    var request = $.klear.buildRequest(requestData);

                    var qqOptions = {
                            element: item[0],
                            action: request.action,
                            params: request.data,
                            multiple: false,
                            messages: {
                                typeError: $.translate("{file} has invalid extension. Only {extensions} are allowed.",[__namespace__]),
                                sizeError: $.translate("{file} is too large, maximum file size is {sizeLimit}.",[__namespace__]),
                                minSizeError: $.translate("{file} is too small, minimum file size is {minSizeLimit}.",[__namespace__]),
                                emptyError: $.translate("{file} is empty, please select files again without it.",[__namespace__]),
                                onLeave: $.translate("The files are being uploaded, if you leave now the upload will be cancelled.",[__namespace__])
                            },
                            template: '<div class="qq-uploader">' +
                                '<div class="qq-upload-drop-area"><span></span></div>' +
                                '<div class="qq-upload-button ui-button ui-widget ui-state-default ui-corner-all"><span class="ui-icon ui-icon-folder-open inline"></span>'+$.translate("Upload File", [__namespace__])+'</div>' +
                                '<ul class="qq-upload-list"></ul>' +
                             '</div>',
                            onComplete : function(id, fileName, result) {

                                $(_self).klearModule("unsetUploadInProgress", id);
                                var $list = $(".qq-upload-list",$(this._element));

                                if (result.error) {
                                    $list.empty();
                                    $(_self).klearModule("showDialogError", result.message, {title : $.translate("ERROR",[__namespace__])});
                                    return;
                                }

                                var fName = $(".qq-upload-file",$list).html();
                                var fSize = $(".qq-upload-size",$list).html();
                                var _id = _hiddenField.attr("id");
                                _hiddenField
                                    .val(result.code)
                                    .data("fileDescription",fName + ' ('+fSize+')')
                                    .trigger("manualchange");
                                $list.html('');
                            },
                            onSubmit: function (id, fileName) {

                                $(_self).klearModule("setUploadInProgress", id);
                                return true;
                            },

                            onCancel: function(id, fileName){

                                $(_self).klearModule("unsetUploadInProgress", id);
                            },
                            onError: function(id, fileName, reason) {

                                $(_self).klearModule("unsetUploadInProgress", id);
                            },
                            showMessage : function(message) {

                                if (typeof(message) == 'string') {
                                    $(".qq-upload-list",$(this.element)).html('');
                                    $(_self).klearModule("showDialogError", message, {title : $.translate("ERROR",[__namespace__])});
                                }
                            }
                    };

                    if (_hiddenField.data("extensions")) {
                        qqOptions.allowedExtensions = _hiddenField.data("extensions").split(',');
                    }

                    (function lazyQQLoad() {
                        if (!qq || !qq.FileUploader) {
                            this.count++;
                            if (this.count > 10) {
                                return;
                            }
                            setTimeout(lazyQQLoad,50);
                            return;
                        }
                        var uploader = new qq.FileUploader(qqOptions);
                    })();
                });
            }

            $("input, textarea", this.options.theForm)
                .autoResize({
                    onStartCheck: function() {
                        // El plugin se "come" el evento :S
                        $(this).trigger("manualchange");
                    }
                })
                .end()
                .filter(":not(:disabled)").filter(":not(:hidden)").eq(0).trigger("focusin").select().focus();


            //Mark required fields
            var _required = $('<span title="' + $.translate("Campo obligatorio",[__namespace__]) + '" class="ui-icon inline ui-icon-heart"></span>');
            $("input, textarea, select", this.options.theForm).filter("[required]").filter("[required]").before(_required.clone());

            //Validate required select fields by regExp
            $("select[required]",this.options.theForm).not("[pattern]").attr("pattern", "[^__NULL__].{0,}");

            $("div.expandable", this.options.theForm).each(function() {
                $(this).hide();
                var expand = '<button class="ui-state-default ui-corner-all right pointer"><span class="ui-icon ui-icon-circle-triangle-s"></span></button>';
                var contract = '<button class="ui-state-default ui-corner-all right pointer"><span class="ui-icon ui-icon-circle-triangle-n"></span></button>';

                $("<div class='container ui-widget-content ui-corner-all'/>")
                    .html($('label:first',$(this)).clone())
                    .insertAfter($(this))
                    .find('label:first')
                    .addClass('pointer')
                    .append(expand)
                    .after('<br />')
                    .on('click',function(){
                        $(this).parent('div').slideToggle('slow',function(){
                            $(this).prev('div.expandable').slideToggle('slow');
                    });
                });

                $('label:first',$(this)).append(contract).after('<br />').on('click',function(){
                    $(this).parent('div').slideToggle('slow',function(){
                        $(this).next('div').slideToggle('slow');
                    });
                });

                $(this).find('button').on('click',function(e){
                    e.preventDefault();
                });
                $(this).next('div').find('button').on('click',function(e){
                    e.preventDefault();
                });
            });
            return this;
        },

        //TODO: Este método está creciendo demasiado. Revisar para que no acabe demasiado inflado
        _registerEvents : function() {

            var self = this;

            this.options.theForm.on('updateChangedState',function() {
                if ($(".changed",$(this)).length > 0) {

                    self.element.klearModule("setAsChanged", function() {
                        self.element.klearModule('showDialog',
                            $.translate("There is unsaved content.", [__namespace__]) +
                            '<br />' +
                            $.translate("Close the screen?", [__namespace__])
                            ,{
                            title : $.translate("Attention!", [__namespace__]),
                            buttons :
                                 [
                                      {
                                        text: $.translate("Cancel", [__namespace__]),
                                        click: function() {
                                            $(this).moduleDialog("close");
                                        }
                                    },
                                    {
                                        text: $.translate("Ignore changes and close", [__namespace__]),
                                        click: function() {
                                            self.element.klearModule("setAsUnChanged");
                                            self.element.klearModule("close");
                                        }
                                    }
                                ]
                        });

                        return true;
                    });

                } else {
                    self.element.klearModule("setAsUnChanged");
                }
            });

            $(".generalOptionsToolbar a.action",this.element.klearModule("getPanel")).on('click',function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.options.theForm.trigger("submit");
            });

            $(".visualFilter",this.element.klearModule("getPanel")).on('manualchange.visualFilter',function(e,manual) {

                //Si es manual y es un campo oculto no hacemos los filtros
                //porque este campo oculto puede tener a su vez otros filtros
                //y mostrar campos que no debería
                //Ejemplo: A oculta B y C, pero B muestra C. Primero se comprueba A ocultando B y C.
                //Después se comprueba B mostrando C, pero no debería, ya que B está oculto de antes.

                if (manual && $(this).parents("div:eq(0)").is(':hidden')) {
                    return;
                }

                if ($(this).is("input:hidden")) {

                    var curOption = $(this);

                } else {

                    var curOption = $("option[value="+$(this).val()+"]",$(this));
                }

                if (!curOption.data("show") && !curOption.data("hide")) {
                    return;
                }

                if (curOption.data("show")) {
                    $.each(curOption.data("show").split(","),function(i,val) {
                        var fName = $.trim(val);
                        if (fName == '') return;
                        var field = $("label[rel='"+fName+"']:eq(0)",self.options.theForm).parents("div:eq(0)");

                        if (manual) {

                            field.show();

                        } else {

                            //Cuando mostramos un campo, lanzamos el visualFilter si tiene
                            //por si está relacionado con otro campo que debemos ocultar o mostrar
                            field.slideDown('normal', function(){
                                $(".visualFilter", field).trigger("manualchange.visualFilter",true);
                            }).addClass("ui-state-highlight");

                            setTimeout(function() {
                                field.removeClass('ui-state-highlight');
                            },1300);
                        }
                    });
                }
                if (curOption.data("hide")) {
                    $.each(curOption.data("hide").split(","),function(i,val) {

                        var fName = $.trim(val);
                        if (fName == '') return;

                        var field = $("label[rel='"+fName+"']:eq(0)",self.options.theForm).parents("div:eq(0)");

                        if (manual) {

                            field.hide();

                        } else {

                            //Aquí no hace falta lanzar el visualFilter porque aunque se oculta, el valor no cambia
                            field.slideUp();
                        }
                    });
                }

            }).trigger("manualchange.visualFilter",true);

            $("select, input, textarea", this.options.theForm).on('manualchange', function(e) {
                if ($(this).data("target-for-change")) {
                    var _target = $(this).data("target-for-change");
                } else {
                    var _target = $(this);
                }

                var _val = $(this).val() ? $(this).val() : '';

                if ($(this).data("savedValue") != Crypto.MD5(_val)) {
                    _target.addClass("changed ui-state-highlight");
                    $(this).addClass("changed");
                } else {
                    _target.removeClass("changed ui-state-highlight");
                    $(this).removeClass("changed");
                }

                self.options.theForm.trigger("updateChangedState");
                $(this).trigger("postmanualchange");
            });

            $("select",this.options.theForm).on("change", function() {
                $(this).trigger("manualchange");
            });

            $("select, input, textarea", this.options.theForm).on("keydown", function(e) {
                if(e.shiftKey && e.ctrlKey && e.which == 13) {
                    e.preventDefault();
                    self.options.theForm.trigger('submit');
                }
            });


            var _copied = $('<span title="' + $.translate("Campo auto-copiado",[__namespace__]) + '" class="ui-silk inline ui-silk-page-white-copy copied"></span>');

            $("dl.multiLanguage dd")
                .on('isCopied',function() {
                    if ($(this).hasClass("copied")) {
                        return;
                    }
                    if ($("[data-multilang]",$(this)).val() == '') {
                        return;
                    }


                    $(this)
                        .addClass("copied")
                        .append(_copied.clone());
                })
                .on('isNotCopied',function() {
                    $(this)
                        .removeClass("copied")
                        .find("span.copied").remove();
                });

            $('dl.multiLanguage input, textarea', this.options.theForm).on('keyup', function() {

                var _dl = $(this).parents("dl.multiLanguage:eq(0)");

                if (!$(this).parent("dd").hasClass("selected")) {
                    var _selValue = _dl.find("dd.selected [data-multilang]").val();

                    if ($(this).val() != _selValue) {
                        $(this).parent("dd").trigger("isNotCopied");
                    } else {
                        $(this).parent("dd").trigger("isCopied");
                    }
                    return;
                }


                var _val = $(this).val();

                _dl.find("dd:not(.selected) [data-multilang]").each(function() {
                    if ($(this).val() == '' || ($(this).parent("dd").hasClass("copied"))) {
                        $(this).val(_val).trigger("change").trigger("manualChange");
                        $(this).parent("dd").trigger("isCopied");
                    }
                });

            }).trigger("keyup");

            return this;
        }

    });

    $.widget.bridge("klearMatrixEdit", $.klearmatrix.edit);

})(jQuery);
