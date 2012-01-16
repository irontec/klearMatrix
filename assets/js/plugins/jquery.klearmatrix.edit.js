;(function($) {
	
	$.widget("klearmatrix.edit", {
		options: {
			data : null
		},
		
		_create: function(){

			// remember this instance
			$.km.edit.instances.push(this.element);
		},
		_getOtherInstances: function(){
			
			var element = this.element;

			return $.grep($.klearmatrix.edit.instances, function(el){
				return el !== element;
			});
		},
		
		_init: function() {
			
			this._loadMainTemplate();
			
				
		},
		_registerEvents : function() {
			
			

		},
		_loadMainTemplate : function() {
			var $form = $.tmpl(
							"editkMatrix",
							this.options.data);
			
			$(this.element.module("getPanel")).append($form);
			
			
		},
		_setOption: function(key, value){
			
			this.options[key] = value;

			switch(key){
				case "title":
					this.options.mainEnl.html(value);
				break;
			}
		},
		destroy: function(){
			// remove this instance from $.km.mywidget.instances
			var element = this.element,
			position = $.inArray(element, $.klearmatrix.mywidget.instances);

			// if this instance was found, splice it off
			if(position > -1){
				$.klearmatrix.edit.instances.splice(position, 1);
			}

			// call the original destroy method since we overwrote it
			$.Widget.prototype.destroy.call( this );
		}

	});

	
	$.extend($.klearmatrix.edit, {
		instances: []
	});
	
		
})(jQuery);
