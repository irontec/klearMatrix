;(function load($) {

	this.count = this.count || 0;
	
	if ( (!$.klearmatrix) || (typeof $.klearmatrix.module != 'function') ) {
		if (++this.count == 20) {
			throw "JS Dependency error!";
		}
		setTimeout(function() {load($);},20);
		return;
	}
	
	$.widget("klearmatrix.genericdialog", $.klearmatrix.module,  {
		options: {
			data : null,
			moduleName: 'genericdialog'
		},
		_super: $.klearmatrix.module.prototype,
		_create : function() {
			this._super._create.apply(this);
		},
		_getDialogContent : function() {
			
			return this.options.data.message;
		},
		_getButtons : function() {
			var _buttons = [];
			
			for(var label in this.options.data.buttons) {
				
				_buttons.push((function(label,self) {
			
					var button = {
						text: label,
						click : function() {
							if (self.options.data.buttons[label].recall) {
								self.options.caller.trigger("click",self.options.data.buttons[label].params);
							}
							$(this).moduleDialog("close");
						}
					}
					return button;
				})(label,this));
				
			}
			
			return _buttons;
	
		},
		_init: function() {
			
			var self = this;
			
			$(this.element).moduleDialog("option","buttons",this._getButtons());
			$(this.element).moduleDialog("updateContent",this._getDialogContent());
			
		}
				
	});
	
	$.widget.bridge("klearMatrixGenericDialog", $.klearmatrix.genericdialog);
					
})(jQuery);
