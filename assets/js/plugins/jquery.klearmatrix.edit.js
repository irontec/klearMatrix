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
                ._registerMainActionEvent();

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
                	$("[name="+name+"]",self.options.theForm).val(value).trigger("manualchange");
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

                $(self.element).klearModule("showDialog",
                        '<br />',
                        {
                            title: self.options.data.title,
                            template : '<div class="ui-widget">{{html text}}</div>',
                            buttons : []
                        });

                $(self.element).klearModule("option","moduleDialog").moduleDialog("setAsLoading");

                self._doAction.call(self);
            });

            return this;
        },
        _doAction : function() {

            (function(self) {
                var $self = $(self.element);
                var $dialog = $self.klearModule("option","moduleDialog");

                $.klear.request(
                        {
                            file: $self.klearModule("option","file"),
                            type: 'screen',
                            execute: 'save',
                            pk: self.options.theForm.data("id"),
                            screen: self.options.data.screen,
                            post : self.options.theForm.serialize()
                        },
                        function(data) {

                            if (data.error) {
                                //TO-DO: FOK OFF
                                // Mostrar errores desde arriba
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
                var _hash = Crypto.MD5(_val);
                $(this)
                    .data("savedValue",_hash)
                    .trigger("manualchange");
            });

            this.options.theForm
                            .h5Validate()
                            .on('validated',function(formElement,validation) {

                            });

        },
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

                        var plgSettings = {};

                        $.each($(this).data(),function(idx, value) {
                            if (idx.match(/setting-*/)) {

                                idx = idx.replace('setting', '');
                                idx = idx.charAt(0).toLowerCase() + idx.substr(1); //lcfirst

                                plgSettings[idx] = value;
                            }
                        });

                        $(this)[$(this).data("plugin")](plgSettings);
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
                                    cssSelectorAncestor : '#' + controlId,
                                    swfPath : '../klearMatrix/bin/',
                                    solution:'html,flash',
                                    supplied: "mp3",
                                    oggSupport: false,
                                    wmode:"window"
                                });

                });

            }


            if ($(".qq-uploader",this.options.theForm).length>0) {
                $(".qq-uploader",this.options.theForm).each(function() {

                    var item = $("<div />");
                    item
                        .attr("rel",$(this).attr("rel"))
                        .data("command",$(this).data("command"));

                    $(this).replaceWith(item);

                    var _hiddenField = $("#" + item.attr("rel"));

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
                            template: '<div class="qq-uploader">' +
                                '<div class="qq-upload-drop-area"><span></span></div>' +
                                '<div class="qq-upload-button ui-button ui-widget ui-state-default ui-corner-all"><span class="ui-icon ui-icon-folder-open inline"></span>'+$.translate("Upload File", [__namespace__])+'</div>' +
                                '<ul class="qq-upload-list"></ul>' +
                             '</div>',
                            onComplete : function(id, fileName, result) {
                                var $list = $(".qq-upload-list",$(this.element));
                                var fName = $(".qq-upload-file",$list).html();
                                var fSize = $(".qq-upload-size",$list).html();
                                var _id = _hiddenField.attr("id");
                                _hiddenField
                                    .val(result.code)
                                    .data("fileDescription",fName + ' ('+fSize+')')
                                    .trigger("manualchange")
                                $list.html('');
                            },

                            onError : function() {
                                console.log("error",arguments);

                            }
                    };

                    if (_hiddenField.data("extensions")) {
                        qqOptions.allowedExtensions = _hiddenField.data("extensions").split(',');
                    }

                    var uploader = new qq.FileUploader(qqOptions);

                });
            }


            $("input, select, textarea",this.options.theForm)
                .autoResize({
                    onStartCheck: function() {
                        // El plugin se "come" el evento :S
                        $(this).trigger("manualchange");
                    }
                })
                .find(":not(:disabled):eq(0)").trigger("focusin").select();
            return this;

        },
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

            $("select.visualFilter").on('manualchange.visualFilter',function(e,manual) {
                var curOption = $("option[value="+$(this).val()+"]",$(this));
                $.each(curOption.data("hide").split(","),function(i,val) {
                    var fName = $.trim(val);
                    if (fName == '') return;
                    var field = $("[name='"+fName+"']:eq(0)",self.options.theForm).parents("p:eq(0)");
                    if (manual) field.hide();
                    else field.slideUp();
                });

                $.each(curOption.data("show").split(","),function(i,val) {
                    var fName = $.trim(val);
                    if (fName == '') return;
                    var field = $("[name='"+fName+"']:eq(0)",self.options.theForm).parents("p:eq(0)");
                    if (manual) field.show();
                    else field.slideDown();
                });

            }).trigger("manualchange.visualFilter",true);

            $("select,input,textarea",this.options.theForm).on('manualchange',function() {
                var _val = $(this).val()? $(this).val():'';
                if ($(this).data("savedValue") != Crypto.MD5(_val)) {
                    $(this).addClass("changed ui-state-highlight");
                } else {
                    $(this).removeClass("changed ui-state-highlight");
                }
                self.options.theForm.trigger("updateChangedState");
                $(this).trigger("postmanualchange");
            });

            $("select",this.options.theForm).on("change",function() {
                $(this).trigger("manualchange");
            });

            $("[title]",this.options.theForm).tooltip();

            return this;

        },
        _applyDecorators : function() {
            $(".generalOptionsToolbar a",this.element.klearModule("getPanel")).each(function() {
                $(this).button();
            });
            return this;
        }
    });

    $.widget.bridge("klearMatrixEdit", $.klearmatrix.edit);

})(jQuery);
