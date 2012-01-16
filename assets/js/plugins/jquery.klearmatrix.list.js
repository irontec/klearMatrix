;(function($) {

	$.widget("klearmatrix.list", $.klearmatrix.module,  {
		options: {
			data : null,
			moduleName: 'list'
		},
		_super: $.klearmatrix.module.prototype,
		_create : function() {
			console.log("created 0");
			this._super._create.apply(this);
			console.log("created");
		},
		_init: function() {
			
			this
				._registerEvents()
				._loadMainTemplate();
			
				
		},
		_registerEvents : function() {
			
			var self = this.element;
			
			// highlight effect on tr
			$(this.element.module("getPanel")).on('mouseenter mouseleave','table.kMatrix tr',function() {
				$("td",$(this)).toggleClass("ui-state-highlight");
			});
			
			$(this.element.module("getPanel")).on('mouseenter','a._fieldOption',function(e) {
				if ($(this).data("relatedtab")) {
					$(this).data("relatedtab").module("highlightOn");
				}				
			}).on('mouseleave','a._fieldOption',function(e) {
				if ($(this).data("relatedtab")) {
					$(this).data("relatedtab").module("highlightOff");
				}				
			});
			
			$(this.element.module("getPanel")).on('click','a._fieldOption',function(e) {
				
				e.preventDefault();
				e.stopPropagation();
				
				var _container = self.module("getContainer");
				
				var _iden = "#tabs-" + self.module("option","file")
							+ '_' + $(this).data("screen")
							+ '_' + $(this).parents("tr:eq(0)").data("id");
				
				
				if ($(_iden).length > 0) {
					_container.tabs('select', _iden);
					return;
				}
				
				var _newIndex = self.module("option","tabIndex")+1;
				var _menuLink = $(this);
				var _parentTr = $(this).parents("tr:eq(0)");
				
				_menuLink.addClass("ui-state-highlight");
				
				_container.one( "tabspostadd", function(event, ui) {
					
					var $tabLi = $(ui.tab).parent("li");
					// Seteamos como menuLink <- enlace "generador", el enlace que lanza el evento
					$tabLi.module("option","menuLink",_menuLink);
					
					// Actualizamos el file, al del padre (En el constructor se pasa "sucio"
					$tabLi.module("option","file",self.module("option","file"));
					
					// Seteamos el valor para dispatchOptions
					var _dispatchOptions = {
						screen : _menuLink.data("screen"),
						pk : _parentTr.data("id")
					};
					
					_menuLink.data("relatedtab",$tabLi);
					
					$tabLi.module("option","dispatchOptions",_dispatchOptions);
					$tabLi.module("reload");
					
				});
				
				var tabTitle = ($("td.default",_parentTr).length>0) ? 
									$("td.default",_parentTr).text() : $(this).attr("title");
				_container.tabs( "add", _iden, tabTitle,_newIndex);
				
				
			});
			
			
			return this;
		},
		_loadMainTemplate : function() {
			var $table = $.tmpl(
							"mainkMatrix",
							this.options.data,
							{ 
								getIndex : function(values,index) {
									
									if ('undefined' === typeof values[index]) {
										
										switch(index) {
											case "_fieldOptions":
												
												
												if (this.data.fieldOptions) {
													var ret = [];

													for(var i=0;i<this.data.fieldOptions.length;i++) {
														
														var _op = this.data.fieldOptions[i];
														
														ret.push('<a class="_fieldOption" href="" data-screen="'+_op.screen+'" title="'+_op.title+'">');
														ret.push('<span class="ui-silk inline '+_op.class+'"></span>');
														if (!_op.noLabel) {
															ret.push(_op.title);
														}
														ret.push('</a>');
													}
													return ret.join('');
												}
												
											break;
										
											default:
												return "no disponible";
										}
										
									} else {
									
										return values[index];
									}
								}
			        
							});
			
			$(this.element.module("getPanel")).append($table);
			
			
		}
		
	});

					
})(jQuery);
