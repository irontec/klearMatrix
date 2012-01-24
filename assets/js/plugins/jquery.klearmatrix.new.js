;(function load($) {

	this.count = this.count || 0;
	
	if (typeof $.klearmatrix.edit != 'function') {
		if (++this.count == 10) {
			throw "JS Dependency error!";
		}
		setTimeout(function() {load($);},10);
		return;
	}
	
	$.widget("klearmatrix.new", $.klearmatrix.edit, {
		options: {
			data : null,
			moduleName: 'new'
		},
		_super: $.klearmatrix.module.prototype,
		_create : function() {
			this._super._create.apply(this);
		},
		_init: function() {
			
			$.extend(this.options.data,{randIden:Math.round(Math.random(1000,9999)*100000)});

			this.options.data.title = this.options.data.title || this.element.klearModule("option","title");

			var $appliedTemplate = this._loadTemplate("klearmatrixNew");
			
			$(this.element.klearModule("getPanel")).append($appliedTemplate);
			
			this._applyDecorators()
				._registerBaseEvents()
				._registerEvents()
				._initFormElements(); 
			
				
		},
		_registerEvents : function() {
			
			$(this.element.klearModule("getPanel")).on('submit','form.klearMatrix_new',function() {
			
				
				
			});
			return this;
		}		
	});

	$.widget.bridge("klearMatrixNew", $.klearmatrix.new);
	
})(jQuery);
