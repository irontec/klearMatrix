;(function($) {
	
	$.widget("klearmatrix.delete", $.klearmatrix.module, {
		options: {
			data : null,
			parent : null,
			moduleName: 'delete'
		},
		_super: $.klearmatrix.module.prototype,
		_create : function() {
			this._super._create.apply(this);
		},
		
		
		_doAction : function(moduleDialogCaller) {
			
			
			var $refParent = $(this.options.parent);
			var $self = $(this.element);
			var _ids = $(".deleteable-item",$(moduleDialogCaller)).data("id");
			console.log($(".deleteable-item",$(moduleDialogCaller)));
			$.klear.request(
					{
						file: $refParent.klearModule("option","file"),
						type: 'dialog',
						action: 'delete',
						dialog : $(this).data("dialog"),
						pk : _ids
					},
					function(plugin,data) {
						//$_dialog[plugin]({data : data});
					},
					function() {
									
					}
			);			
		},
		_init: function() {
			
			var $appliedTemplate = this._loadTemplate("klearmatrixDelete");
			var self = this;
			
			$(this.element).moduleDialog("option","buttons",
					 [
					  	{
    						text: "Cancelar",
    						click: function() { $(this).moduleDialog("close"); }
						},
					    {
					        text: "Eliminar",
					        click: function() {
					        	self._doAction.apply(self,[this]);
					        }
					    }
					]
					
					);
			$(this.element).moduleDialog("updateContent",$appliedTemplate);
			
				
		},
	});
	
})(jQuery);