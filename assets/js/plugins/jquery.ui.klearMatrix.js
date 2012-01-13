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

			return $.grep($.ui.klearMatrix.instances, function(el){
				return el !== element;
			});
		},
		
		_init: function() {
			
			this
				._registerEvents()
				._loadMainTemplate();
			
				
		},
		_registerEvents : function() {
			// highlight effect on tr
			$(this.element.kModule("getPanel")).on('mouseenter mouseleave','table.kMatrix tr',function() {
				$("td",$(this)).toggleClass("ui-state-highlight");
			});
			return this;
		},
		_loadMainTemplate : function() {
			var $table = $.tmpl(
							"mainkMatrix",
							this.options.data,
							{ 
								getIndex : function(values,index) {
									console.log(values,index);
									return values[index];
								}
			        
							});
			
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
				$.ui.klearMatrix.instances.splice(position, 1);
			}

			// call the original destroy method since we overwrote it
			$.Widget.prototype.destroy.call( this );
		}

	});

	
	$.extend($.ui.klearMatrix, {
		instances: []
	});
	
		
})(jQuery);
