;(function($) {
	
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
			
			this._loadMainTemplate();
			
				
		},
		_registerEvents : function() {
			
			

		},
		_loadMainTemplate : function() {
			var data = $.extend(this.options.data,{randIden:Math.random(1000,9999)});
			
			var $form = $.tmpl(
							"editkMatrix",
							data);
			
			$(this.element.module("getPanel")).append($form);
			
			
		},
		_setOption: function(key, value){
			
			this.options[key] = value;

			switch(key){
				case "title":
					this.options.mainEnl.html(value);
				break;
			}
		}

	});

	
		
})(jQuery);
