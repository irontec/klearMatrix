;(function load($) {

	this.count = this.count || 0;
	
	if ( (typeof $.klearmatrix.module != 'function') 
		|| (typeof $.fn.h5Validate != 'function') 
		|| (typeof Crypto != 'object')
		) {
		if (++this.count == 30) {
			throw "JS Dependency error!"
				+ 'klearmatrix.module: ' + typeof $.klearmatrix.module  
				+ 'h5Validate'  + typeof $.fn.h5Validate
				+ 'Crypto' + typeof Crypto;
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
				._registerEvents();
				
				
		},
		
		$theForm : null,
		
		_initSavedValueHashes : function() {
			
			$("select,input,textarea",this.$theForm).each(function() {
				var _hash = Crypto.MD5($(this).val()); 
				$(this).data("savedValue",_hash);
			});			
		},
		_initFormElements : function() {
			
			this.$theForm = $("form",$(this.element.klearModule("getPanel")));
			this.$theForm.form();
			this._initSavedValueHashes();			
			return this;
			
		},
		_registerEvents : function() {
			
			var self = this;
			
			this.$theForm.on('submit',function() {
			
				
				
			}).on('updateChangedState',function() {
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
				
			
			$("select,input,textarea",this.$theForm).on('change',function() {

				if ($(this).data("savedValue") != Crypto.MD5($(this).val())) {
					$(this).addClass("changed ui-state-highlight");
				} else {
					$(this).removeClass("changed ui-state-highlight");					
				}
				self.$theForm.trigger("updateChangedState");
				
			});
			
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
