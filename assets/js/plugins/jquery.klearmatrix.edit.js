;(function load($) {

	this.count = this.count || 0;
	
	if ( (typeof $.klearmatrix.module != 'function') 
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
		setTimeout(function() {load($);},10);
		return;
	}
	
	$.widget("klearmatrix.edit", $.klearmatrix.module, {
		options: {
			data : null,
			moduleName: 'edit'
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
				._registerBaseEvents()
				._initFormElements()
				._registerEvents()
				._registerMainActionEvent();
			
		},
		_registerMainActionEvent : function() {
			var self = this;
			
			this.$theForm.on('submit',function(e) {
				e.preventDefault();
				e.stopPropagation();
				
				$(self.element).klearModule("showDialog",
						'<br />',
						{
							title: $.translate("guardando..."),
							template : '<div class="ui-widget">{{html text}}</div>',
							buttons : []
						});
				
				$(self.element).klearModule("getModuleDialog").moduleDialog("setAsLoading");	
				
				self._doAction.call(self);
			});
			
			return this;
		},	
		_doAction : function() {
			
			var self = this;
			var $self = $(this.element);
			
			var $dialog = $self.klearModule("getModuleDialog") 
			
			$.klear.request(
					{
						file: $self.klearModule("option","file"),
						type: 'screen',
						execute: 'save',
						pk: self.$theForm.data("id"),
						screen: self.options.data.screen,
						post : self.$theForm.serialize()
					},
					function(data) {
						
						if (data.error) {
							//TO-DO: FOK OFF
						} else {
							var $parentModule = $self.klearModule("option","parentScreen");
							$parentModule.klearModule("reDispatch");
						}

						self._initSavedValueHashes();
						self.$theForm.trigger('updateChangedState');
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
			
		},
		
		$theForm : null,
		
		_initSavedValueHashes : function() {
			
			$("select,input,textarea",this.$theForm).each(function() {
				var _hash = Crypto.MD5($(this).val()); 
				$(this)
					.data("savedValue",_hash)
					.trigger("manualchange");
			});			
		},
		_initFormElements : function() {
			
			this.$theForm = $("form",$(this.element.klearModule("getPanel")));
			this.$theForm.form();
			
			if ($("select.multiselect",this.$theForm).length > 0) {
				$("select.multiselect",this.$theForm).multiselect({
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
				});
			}
			
			this._initSavedValueHashes();
			$("input, select, textarea",this.$theForm)
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
			
			this.$theForm.on('updateChangedState',function() {
				if ($(".changed",$(this)).length > 0) {
					
					self.element.klearModule("setAsChanged", function() {
						self.element.klearModule('showDialog',
							$.translate("Existe contenido no guardado.") + '<br />' + $.translate("¿Desea cerrar la pantalla?")
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
				
			$(".generalOptionsToolbar a.action").on('click',function(e) {
				e.preventDefault();
				e.stopPropagation();
				self.$theForm.trigger("submit");
			});
			
			
			$("select,input,textarea",this.$theForm).on('manualchange',function() {
				var _val = $(this).val()? $(this).val():'';
				if ($(this).data("savedValue") != Crypto.MD5(_val)) {
					$(this).addClass("changed ui-state-highlight");
				} else {
					$(this).removeClass("changed ui-state-highlight");					
				}
				self.$theForm.trigger("updateChangedState");
			});
			
			$("select",this.$theForm).on("change",function() {
				$(this).trigger("manualchange");
			});
			
			$("[title]",this.$theForm).tooltip();
			
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
	
	console.log("KM EDIT : ",typeof $.klearmatrix.edit);

})(jQuery);
