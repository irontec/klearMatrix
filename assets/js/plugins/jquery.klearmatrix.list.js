;(function load($) {

	this.count = this.count || 0;
	
	if (typeof $.klearmatrix.module != 'function') {
		if (++this.count == 10) {
			throw "JS Dependency error!";
		}
		setTimeout(function() {load($);},10);
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

			$(".generalOptionsToolbar a",$(this.element.klearModule("getPanel"))).button();
			
			return this;
		},
		_registerEvents : function() {
			
			var self = this.element;
			var _self = this;
			
			// highlight effect on tr
			$(this.element.klearModule("getPanel")).on('mouseenter mouseleave','table.kMatrix tr',function() {
				$("td",$(this)).toggleClass("ui-state-highlight");
			});
			
			$(this.element.klearModule("getPanel")).on('mouseenter','a._fieldOption',function(e) {
				if ($(this).data("relatedtab")) {
					$(this).data("relatedtab").klearModule("highlightOn");
				}				
			}).on('mouseleave','a._fieldOption',function(e) {
				if ($(this).data("relatedtab")) {
					$(this).data("relatedtab").klearModule("highlightOff");
				}				
			});
			
			$(this.element.klearModule("getPanel")).on('click','a.option.screen',function(e) {
				
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
				
				_container.one( "tabspostadd", function(event, ui) {

					var $tabLi = $(ui.tab).parent("li");

					// Seteamos como menuLink <- enlace "generador", el enlace que lanza el evento
					$tabLi.klearModule("option","menuLink",_menuLink);
					
					// Actualizamos el file, al del padre (En el constructor se pasa "sucio"
					$tabLi.klearModule("option","file",self.klearModule("option","file"));
					
					// Seteamos el valor para dispatchOptions
					var _dispatchOptions = {
						screen : _menuLink.data("screen"),
						pk : _parentTr.data("id")
					};
					
					_menuLink.data("relatedtab",$tabLi);
					
					$tabLi
						.klearModule("highlightOn")
						.klearModule("option","dispatchOptions",_dispatchOptions)
						.klearModule("reload");
						
					
				});
				
				var tabTitle = ($("td.default",_parentTr).length>0) ? 
									$("td.default",_parentTr).text() : $(this).attr("title");
				_container.tabs( "add", _iden, tabTitle,_newIndex);
				
				
			});
			
			/*
			 */
			$(this.element.klearModule("getPanel")).on('click','a.option.dialog',function(e) {
				
				e.preventDefault();
				e.stopPropagation();
				
				
				var _container = self.klearModule("getContainer");
				var $_parentTr = $(this).parents("tr:eq(0)");
				
				$(self).klearModule("showDialog",
						'<br />',
						{
							title: $(this).attr("title"),
							template : '<div class="ui-widget">{{html text}}</div>',
						});
				
				var $_dialog = $(self).klearModule("getModuleDialog");
				$_dialog.moduleDialog("setAsLoading");				
				$.klear.request(
						{
							file: self.klearModule("option","file"),
							type: 'dialog',
							dialog : $(this).data("dialog"),
							pk : $_parentTr.data("id")
						},
						function(plugin,data) {
							$_dialog[plugin]({data : data, parent: self});
						},
						function() {
										
						}
				);
				
			});
			
			return this;
		}		
	});
	
	$.widget.bridge("klearMatrixList", $.klearmatrix.list);
					
})(jQuery);
