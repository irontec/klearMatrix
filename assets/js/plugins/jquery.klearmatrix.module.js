;(function($) {
	
	$.widget("klearmatrix.module", {
		options: {
			moduleName: 'module'
		},
		_create: function(){
			
			if (!this.instances) {
				$.extend($.klearmatrix[this.options.moduleName], {
					instances: []
				});
			}
			
			$.klearmatrix[this.options.moduleName].instances.push(this.element);
			
		},
		_getOtherInstances: function(){
			
			var element = this.element;

			return $.grep($.klearmatrix[this.options.moduleName].instances, function(el){
				return el !== element;
			});
		},
		destroy: function(){
			// remove this instance from $.klearmatrix.mywidget.instances
			var element = this.element,
			position = $.inArray(element, $.klearmatrix[this.options.moduleName].instances);
	

			// if this instance was found, splice it off
			if(position > -1){
				$.klearmatrix[this.options.moduleName].instances.splice(position, 1);
			}

			// call the original destroy method since we overwrote it
			$.Widget.prototype.destroy.call( this );
		},
		_setOption : function() {
			$.Widget.prototype._setOption.apply(this,arguments)
		}

	});

	
})(jQuery);
