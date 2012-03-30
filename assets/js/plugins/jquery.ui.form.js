// JavaScript Document 


$.widget("ui.form",{
		
		_init:function(){
			var self = this;
			var form = this.element;
			var inputs = form.find("input , select ,textarea");
			
			$.each(inputs,function(){
				$(this).addClass('ui-widget ui-state-default ui-corner-all');
				
				
				if($(this).is(":checkbox"))
				self.checkboxes(this);
				else if($(this).is("input[type='text']")||$(this).is("textarea")||$(this).is("input[type='password']"))
				self.textelements(this);
				else if($(this).is(":radio"))
				self.radio(this);
			
				if ($(this).is(":disabled")) {
					$(this).addClass("fieldDisabled");
				}
				
				if($(this).hasClass("date"))
				{
					$(this).datepicker();
				}
				
				
			});
		},
		
		textelements:function(element){
			
			$(element).on('focusin',function() {
				$(this).addClass('ui-state-focus');
 			}).on('focusout',function() {
 				$(this).removeClass('ui-state-focus');
 			});
		},
		checkboxes:function(element) {
			var parent = $("<span />");
			var $input = $(element);
			$input.after(parent);
			$input.addClass("ui-helper-hidden");
			parent.css({width:15,height:15,display:"block"});
			parent.wrap("<span class='ui-state-default ui-corner-all' style='display:inline-block;width:15px;height:15px;margin:0 3px;'/>");
			
			if ($input.is(":checked")) {
				parent.parent("span").addClass("ui-state-active");
				parent.addClass("ui-icon ui-icon-check");
			}
			
			parent.parent().on('click', function(event){
				
				parent.toggleClass("ui-icon ui-icon-check");
				
				if (parent.hasClass("ui-icon")) {
					$input.prop("checked",true);
				} else {
					$input.prop("checked",false);
				}
				$input.trigger('change');
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

