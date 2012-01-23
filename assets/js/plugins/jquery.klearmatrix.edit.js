;(function load($) {

	this.count = this.count || 0;
	
	if ( (typeof $.klearmatrix.module != 'function') 
		|| (typeof $.fn.h5Validate != 'function') ) {
		if (++this.count == 10) {
			throw "JS Dependency error!";
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
			
			this.options.data.title = this.options.data.title || this.options.title;
			
			var $appliedTemplate = this._loadTemplate("klearmatrixEdit");
			
			$(this.element.klearModule("getPanel")).append($appliedTemplate);
			
			this._applyDecorators()
				._registerBaseEvents()
				._registerEvents()
				._initFormElements(); 
				
		},
		_initFormElements : function() {
			$("form",$(this.element.klearModule("getPanel"))).form();
			return this;
			
		},
		_registerEvents : function() {
			
			$(this.element.klearModule("getPanel")).on('submit','form.klearMatrix_edit',function() {
			
				
				
			});

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
