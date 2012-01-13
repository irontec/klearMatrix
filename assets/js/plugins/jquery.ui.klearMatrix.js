;(function($) {
		
	$.widget("ui.klearMatrix", {
		options: {
			data : null
		},
		
		_create: function(){

			// remember this instance
			$.ui.klearMatrix.instances.push(this.element);
		},
		_getOtherInstances: function(){
			
			var element = this.element;

			return $.grep($.ui.kModule.instances, function(el){
				return el !== element;
			});
		},
		
		_init: function() {
			this._loadMainTemplate();
			
				
		},
		_loadMainTemplate : function() {
			var $table = $.tmpl("mainkMatrix", this.options.data);
			
			$(this.element.kModule("getPanel")).append($table);
			
			
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
			// remove this instance from $.ui.mywidget.instances
			var element = this.element,
			position = $.inArray(element, $.ui.mywidget.instances);

			// if this instance was found, splice it off
			if(position > -1){
				$.ui.kModule.instances.splice(position, 1);
			}

			// call the original destroy method since we overwrote it
			$.Widget.prototype.destroy.call( this );
		}

	});

	
	$.extend($.ui.klearMatrix, {
		instances: []
	});
		
})(jQuery);
