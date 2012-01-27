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
			
			$self
				.moduleDialog("setAsLoading")
				.moduleDialog("option","buttons",[]);
			
			$.klear.request(
					{
						file: $refParent.klearModule("option","file"),
						type: 'dialog',
						execute: 'delete',
						dialog : $(this).data("dialog"),
						pk : _ids
					},
					function(data) {
						
						$self.moduleDialog("updateContent",data.message);
						
						if (data.error) {
							//TO-DO: FOK OFF
						} else {
							if (!$.isArray(data.pk)) data.pk = [data.pk];
							
							$.each(data.pk,function(idx,_pk) {
								$("tr[data-id='"+_pk+"']",$refParent.klearModule("getPanel")).slideUp(function() {
									$(this).remove();
								});
							});
						}

						$self.moduleDialog("option","buttons",
								 [
								  	{
			    						text: "Cerrar",
			    						click: function() { $(this).moduleDialog("close"); }
									}
								]
						);
														
						
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