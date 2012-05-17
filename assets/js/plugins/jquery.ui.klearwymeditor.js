(function($, undefined) {
	
	$.widget('ui.klearwymeditor', {
		options: {
			logoHtml: ''
		},
		_init:function(){
			
			var $el = $(this.element);
			var options = this.options;
			
			var _self = this;
			
			options.postInit = function(wym) {
	            
	            $(wym._doc).bind('keyup', function(){
	            	wym._element.val(wym.xhtml());
	            	wym._element.addClass('changed');
	            	_self.element.trigger('manualchange');
	            	wym._box.addClass('ui-state-highlight');
	            });
	            try {
	            	wym.hovertools();
		            wym.resizable();
		            wym.fullscreen();	
	            } catch(e) {}
	            
	        };
			
			window.setTimeout(function(){
				$wym  = $el.wymeditor(options);	
			},900);
		}
			
	});
})(jQuery);	
