;(function load($) {

	this.count = this.count || 0;
	
	if ( (!$.klearmatrix) || (typeof $.klearmatrix.module != 'function') ) {
		if (++this.count == 20) {
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
		_getTdClearText : function($item) {
			if (!$item.is(".multilang")) {
				return $item.contents().first().text()
			}
			
			if ($(".multilangValue",$item).length>0) {
				if ($(".selected",$item).length == 1) {
					return $(".selected",$item).contents().first().text()				
				} else {
					return $(".multilangValue:eq(0)",$item).contents().first().text()
				}
			} else {
				return false;
			}
			
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
			
			$('a.option.screen',this.element.klearModule("getPanel")).on('mouseup',function(e) {
				
				e.preventDefault();
				e.stopPropagation();
				
				var _container = self.klearModule("getContainer");
				
				var _iden = "#tabs-" + self.klearModule("option","file")
							+ '_' + $(this).data("screen");
				
				if ($(this).data("multiinstance")) {
					_iden += '_' + Math.round(Math.random(1000,9999)*100000);
				} else {
					_iden += '_' + $(this).parents("tr:eq(0)").data("id");
				}
				
				if ($(_iden).length > 0) {
					_container.tabs('select', _iden);
					return;
				}
				
				var _newIndex = self.klearModule("option","tabIndex")+1;
				var _menuLink = $(this);
				var _parentTr = $(this).parents("tr:eq(0)");
				
				if ($(this).hasClass("_fieldOption")) {
					_menuLink.addClass("ui-state-highlight");
				}
				
				var tabTitle = ($("td.default",_parentTr).length>0) ? 
						_self._getTdClearText($("td.default",_parentTr)) : $(this).attr("title");
				
				_container.one( "tabspostadd", function(event, ui) {

					var $tabLi = $(ui.tab).parent("li");
					
					// Seteamos como menuLink <- enlace "generador", el enlace que lanza el evento
					$tabLi.klearModule("option","menuLink",_menuLink);
					$tabLi.klearModule("option","parentScreen",self);
					$tabLi.klearModule("option","title",tabTitle);
					
					// Actualizamos el file, al del padre (En el constructor se pasa "sucio")
					$tabLi.klearModule("option","file",self.klearModule("option","file"));
					
					// Seteamos el valor para dispatchOptions
					var _dispatchOptions = {
						screen : _menuLink.data("screen"),
						pk : _parentTr.data("id"),
						post : {
							callerScreen : _self.options.data.screen
						}
					};
					
					
					// hioghlight on hover
					_menuLink.data("relatedtab",$tabLi);
					
					$tabLi.klearModule("option","dispatchOptions",_dispatchOptions)
						.klearModule("reload");
						
					
				});
				
				// Klear open in background
				$.klear.checkNoFocusEvent(e, $(self.klearModule("getPanel")).parent(), $(this));
				
				_container.tabs( "add", _iden, tabTitle,_newIndex);
				
			}).on('click',function(e) {
				// Paramos el evento click, que salta junto con mouseup al hacer click con botón izquierdo
				e.preventDefault();
				e.stopPropagation();
			});
			
			
			
			/*
			 * Capturar opciones de diálogo.
			 */
			$('a.option.dialog',this.element.klearModule("getPanel")).on('click',function(e,data) {
				
				e.preventDefault();
				e.stopPropagation();
				
				
				var _container = self.klearModule("getContainer");
				var $_parentTr = $(this).parents("tr:eq(0)");
				var $caller = $(this);
				$(self).klearModule("showDialog",
						'<br />',
						{
							title: $(this).attr("title"),
							template : '<div class="ui-widget">{{html text}}</div>'
						});
				
				var $_dialog = $(self).klearModule("getModuleDialog");
				$_dialog.moduleDialog("setAsLoading");
				
				var _postData = (typeof data != undefined)? data:false;
				$.klear.request(
						{
							file: self.klearModule("option","file"),
							type: 'dialog',
							dialog : $(this).data("dialog"),
							pk : $_parentTr.data("id"),
							post: _postData
						},
						function(response) {
				
							$_dialog[response.plugin]({data : response.data, parent: self, caller: $caller});
						},
						function() {
							console.log(arguments);
										
						}
				);
				
			}).on('mouseup',function(e) {
				// Paramos el evento mouseup, para no llegar al tr
				e.preventDefault();
				e.stopPropagation();
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
