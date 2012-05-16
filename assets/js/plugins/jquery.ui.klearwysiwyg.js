(function($, undefined) {
	
	$.widget('ui.klearwysiwyg', {
		options: {
			
		},
		_init:function(){
			var $el = $(this.element);
			$el.wysiwyg({autoGrow:true, controls:"bold,italic,|,undo,redo"});	
		}
			
	});
  
})(jQuery);	
