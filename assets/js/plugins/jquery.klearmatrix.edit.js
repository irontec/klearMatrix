;(function load($) {

	this.count = this.count || 0;
	

	if ( (typeof $.klearmatrix.module != 'function')
		|| (typeof $.ui.form != 'function')
		|| (typeof $.fn.autoResize != 'function')
		|| (typeof $.fn.h5Validate != 'function')
		|| (typeof Crypto != 'object')
		) {
		
		if (++this.count == 30) {
			throw "JS Dependency error! (" +this.count+")"
				+ '\nklearmatrix.module: ' + typeof $.klearmatrix.module  
				+ '\nh5Validate'  + typeof $.fn.h5Validate
				+ '\nAutoresize'  + typeof $.fn.autoResize
				+ '\nCrypto' + typeof Crypto;
		}
		setTimeout(function() {load($);},50);
		return;
	}
	
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
			
			var $appliedTemplate = this._loadTemplate("klearmatrixEdit");
			
			$(this.element.klearModule("getPanel")).append($appliedTemplate);
			
			this._applyDecorators()
				._initFormElements()
				._registerBaseEvents()
				._registerEvents()
				._registerMainActionEvent();
			
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
							} else {
								var $parentModule = $self.klearModule("option","parentScreen");
								$parentModule.klearModule("reDispatch");
							}
	
							self._initSavedValueHashes();
							self.options.theForm.trigger('updateChangedState');
							$dialog.moduleDialog("option","title",'');
							$dialog.moduleDialog("option","buttons",
									 [
									  	{
				    						text: $.translate("Cerrar"),
				    						click: function() {
				    							$(this).moduleDialog("close");
				    							$self.klearModule("close");
				    						}
										},
										{
											text: $.translate("Editar de nuevo"),
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
					selectedText: $.translate("# de # seleccionados"),
					checkAllText: $.translate('Seleccionar todo'),
					uncheckAllText: $.translate('Deseleccionar todo'),
					noneSelectedText: $.translate('Selecciona una opción'),
					selectedText: $.translate('# seleccionados'),
					position: {
					      my: 'center',
					      at: 'center'
					 }
				}).multiselectfilter();
			}
			
			if ($("input.number",this.options.theForm).length > 0) {
				$("input.number",this.options.theForm).each(function() {
					if ($(this).data("plugin")) {
						$(this)[$(this).data("plugin")]();
					}
				});
			}
			
			
			if ($(".qq-uploader",this.options.theForm).length>0) {
				$(".qq-uploader",this.options.theForm).each(function() {
					
					var _hiddenField = $("#" + $(this).attr("rel"));
					
					_hiddenField.on("postmanualchange",function() {
						var _id = $(this).attr("id");
						var $shownFDesc = $('#new_'+_id);
						if ($(this).hasClass("changed")) {
							$shownFDesc
								.html($(this).data("fileDescription"))
								.css("display","block");
							$('#current_'+_id).hide();
							$shownFDesc.addClass("changed ui-state-highlight");
						} else {
							$shownFDesc.removeClass("changed ui-state-highlight");
						}
					});
					
					var requestData = {
							file: _self.klearModule("option","file"),
							pk: $(this).parents("form:eq(0)").data("id")
					};
					
					switch(_hiddenField.data("upload")) {
						case 'command':
							requestData['type'] = _hiddenField.data("upload");
							requestData[requestData['type']] = _hiddenField.data(requestData['type']);
							break;
					};
					
					if (_hiddenField.val() == '') {
						$('#current_'+ _hiddenField.attr("id")).hide();
					}
					var request = $.klear.buildRequest(requestData);
					
					var qqOptions = {
							element: $(this)[0],
							action: request.action,
							params: request.data,
							multiple: false,
							template: '<div class="qq-uploader">' + 
				                '<div class="qq-upload-drop-area"><span></span></div>' +
				                '<div class="qq-upload-button ui-button ui-widget ui-state-default ui-corner-all"><span class="ui-icon ui-icon-folder-open inline"></span>'+$.translate("Subir Fichero")+'</div>' +
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
							$.translate("Existe contenido no guardado.") +
							'<br />' +
							$.translate("¿Desea cerrar la pantalla?")
							,{
							title : $.translate("Cuidado!"),
							buttons : 
								 [
								  	{
			    						text: $.translate("Cancelar"),
			    						click: function() {
			    							$(this).moduleDialog("close");
			    						}
									},
								    {
								        text: $.translate("Omitir Cambios y Cerrar"),
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
