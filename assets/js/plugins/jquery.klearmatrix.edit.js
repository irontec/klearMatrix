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
			
			$.extend(this.options.data,{randIden:Math.round(Math.random(1000,9999)*100000)});
			
			this
				._loadTemplate("editkMatrix")
				._loadOptionIcons();
			
				
		},
		_registerEvents : function() {
			
			

		},
		_loadOptionIcons : function() {
			
			$(".klearMatrix_options button",this.element.klearModule("getPanel")).each(function() {
				$(this).button({
					icons: {
		                primary: $(this).data("icon")
		            },
		            text: $(this).data("text")
				})
			});
			
		}
	});

	$.widget.bridge("klearMatrixEdit", $.klearmatrix.edit);
	
})(jQuery);
