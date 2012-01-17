;(function($) {
	
	$.widget("klearmatrix.delete", $.klearmatrix.module, {
		options: {
			data : null,
			moduleName: 'edit'
		},
		_super: $.klearmatrix.module.prototype,
		_create : function() {
			this._super._create.apply(this);
		},
		_init: function() {
			
			alert("go!"); 
			
				
		},
	});
	
})(jQuery);