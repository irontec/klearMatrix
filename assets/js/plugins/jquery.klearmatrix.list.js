;(function load($) {

	this.count = this.count || 0;
	
	if ( (!$.klearmatrix) || (typeof $.klearmatrix.module != 'function') ) {
		if (++this.count == 40) {
			throw "JS Dependency error!";
		}
		setTimeout(function() {load($);},20);
		return;
	}
	
	$.widget("klearmatrix.list", $.klearmatrix.module,  {
		options: {
			data : null,
			moduleName: 'list'
		},
		_super: $.klearmatrix.module.prototype,
		_create : function() {
			this._super._create.apply(this);
		},
		_init: function() {
			
			this.options.data.title = this.options.data.title || this.options.title; 
			var $appliedTemplate = this._loadTemplate("klearmatrixList");
			$(this.element.klearModule("getPanel")).append($appliedTemplate);
			
			this
				._applyDecorators()
				._registerBaseEvents()
				._registerEvents();
				
		},
		_applyDecorators : function() {
			$container = $(this.element.klearModule("getPanel"));
			
			$(".generalOptionsToolbar a",$container).button();
			
			if ($("td.multilang",$container).length>0) {
				
				var $mlSelector = $("<span>").addClass("ui-silk ui-silk-comments mlTag").attr("title",$.translate("Campo disponible en multi-lenguaje"));
				
				$("td.multilang",$container).each(function() {
					$(this).prepend($mlSelector.clone().tooltip());
				});
			}
			
			return this;
		},
		_registerEvents : function() {
			
			var self = this.element;
			var _self = this;
			
			// highlight effect on tr
			$('table.kMatrix tr',this.element.klearModule("getPanel")).on('mouseenter mouseleave',function() {
				$("td",$(this)).toggleClass("ui-state-highlight");
				
				if ($("a.option.default",$(this)).length>0) {
					$(this).toggleClass("pointer");
					$("a.option.default",$(this)).toggleClass("ui-state-active");
				}
				
			})
			.on('mouseup',function(e) {
				// Haciendo toda la tupla clickable para la default option
				e.stopPropagation();
				e.preventDefault();
				$.klear.checkNoFocusEvent(e, $(self.klearModule("getPanel")).parent(),$("a.option.default",$(this)));
				$("a.option.default",$(this)).trigger("mouseup");
			});
			
			$('a._fieldOption', this.element.klearModule("getPanel")).on('mouseenter',function(e) {
				if ($(this).data("relatedtab")) {
					$(this).data("relatedtab").klearModule("highlightOn");
				}				
			}).on('mouseleave',function(e) {
				if ($(this).data("relatedtab")) {
					$(this).data("relatedtab").klearModule("highlightOff");
				}				
			});
			
			
			
			$(".paginator a",this.element.klearModule("getPanel")).on('click',function(e) {
				e.preventDefault();
				e.stopPropagation();
				
				var targetPage = $(this).data("page");
				
				var _dispatchOptions = $(self).klearModule("option","dispatchOptions");
				
				if (!_dispatchOptions.post) _dispatchOptions.post = {};
				
				$.extend(_dispatchOptions.post,{
					page : targetPage 
				});

				$(self)
					.klearModule("option","dispatchOptions",_dispatchOptions)
					.klearModule("reDispatch");
				
			});
			
			// Orden de columnas
			$("th:not(.optionHeader)",this.element.klearModule("getPanel")).on("click",function(e) {
				e.preventDefault();
				e.stopPropagation();
				
				var targetOrder = $(this).data("field");
				var orderType = $("span.asc",$(this)).length>0? 'desc':'asc';
				
				var _dispatchOptions = $(self).klearModule("option","dispatchOptions");

				if (!_dispatchOptions.post) _dispatchOptions.post = {};

				
				$.extend(_dispatchOptions.post,{
					order: targetOrder,
					orderType: orderType,
					page: 1
				});
			
				$(self)
					.klearModule("option","dispatchOptions",_dispatchOptions)
					.klearModule("reDispatch");
			}).css("cursor","pointer");

			$("th:not(.optionHeader) span.filter",this.element.klearModule("getPanel")).on("click",function(e) {
				e.stopPropagation();
				e.preventDefault();
				
			});
			
			$("span.mlTag",this.element.klearModule("getPanel")).on("click",function(e) {
				e.preventDefault();
				e.stopPropagation();
				var $td = $(this).parent("td");
				var shown = $("div.multilangValue:not(.selected)",$td).is(":visible");
				
				$("div.multilangValue:not(.selected)",$td).slideToggle();
				
				if (shown) {
					$(".langIden",$td).animate({opacity:'0'});
				} else {
					$(".langIden",$td).animate({opacity:'.5'});
				}
				
			}).on('mouseup',function(e) {
				// Paramos el evento mouseup, para no llegar al tr
				e.preventDefault();
				e.stopPropagation();
			});
			
			return this;
		}		
	});
	
	$.widget.bridge("klearMatrixList", $.klearmatrix.list);
					
})(jQuery);
