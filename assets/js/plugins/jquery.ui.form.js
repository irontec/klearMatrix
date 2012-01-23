// JavaScript Document 


$.widget("ui.form",{
		
		_init:function(){
			var self = this;
			var form = this.element;
			var inputs = form.find("input , select ,textarea");
			
			$.each(inputs,function(){
				$(this).addClass('ui-state-default ui-corner-all');
				
				if($(this).is(":checkbox"))
				self.checkboxes(this);
				else if($(this).is("input[type='text']")||$(this).is("textarea")||$(this).is("input[type='password']"))
				self.textelements(this);
				else if($(this).is(":radio"))
				self.radio(this);
			
				
				if($(this).hasClass("date"))
				{
					$(this).datepicker();
				}
				
				
			});
		},
		
		textelements:function(element){
			
			$(element).on('focusin',function() {
				$(this).toggleClass('ui-state-focus');
 			}).on('focusout',function() {
 				$(this).toggleClass('ui-state-focus');
 			});
		},
		checkboxes:function(element) {
			$(element).parent("label").after("<span />");
			var parent =  $(element).parent("label").next();
			$(element).addClass("ui-helper-hidden");
			parent.css({width:16,height:16,display:"block"});
			parent.wrap("<span class='ui-state-default ui-corner-all' style='display:inline-block;width:16px;height:16px;margin-right:5px;'/>");
			parent.parent("span").on('click', function(event){
				$(this).toggleClass("ui-state-active");
				parent.toggleClass("ui-icon ui-icon-check");
				$(element).trigger('click');
			});
		},
		
		radio:function(element){
			$(element).parent("label").after("<span />");
			var parent =  $(element).parent("label").next();
			$(element).addClass("ui-helper-hidden");
			parent.addClass("ui-icon ui-icon-radio-off");
			parent.wrap("<span class='ui-state-default ui-corner-all' style='display:inline-block;width:16px;height:16px;margin-right:5px;'/>");
			parent.parent("span").on('click',function(event){
				$(this).toggleClass("ui-state-active");
				parent.toggleClass("ui-icon-radio-off ui-icon-bullet");
				$(element).click();
			});
		}
});

