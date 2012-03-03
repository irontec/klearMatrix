;(function($) {
	
	$.widget("klearmatrix.module", {
		options: {
			moduleName: 'module'
		},
		_create: function(){
			
			if (!this.instances) {
				$.extend($.klearmatrix[this.options.moduleName], {
					instances: []
				});
			}
			$.klearmatrix[this.options.moduleName].instances.push(this.element);
			
		},
		_getOtherInstances: function(){
			
			var element = this.element;

			return $.grep($.klearmatrix[this.options.moduleName].instances, function(el){
				return el !== element;
			});
		},
		destroy: function(){
			// remove this instance from $.klearmatrix.mywidget.instances
			var element = this.element,
			position = $.inArray(element, $.klearmatrix[this.options.moduleName].instances);
	

			// if this instance was found, splice it off
			if(position > -1){
				$.klearmatrix[this.options.moduleName].instances.splice(position, 1);
			}

			// call the original destroy method since we overwrote it
			$.Widget.prototype.destroy.call( this );
		},
		_setOption : function(key, value) {
			$.Widget.prototype._setOption.apply(this,arguments)
		},
		_loadTemplate : function(tmplName) {
			
			
			var $tmplObj = $.tmpl(
							tmplName,
							this.options.data,
							{
								debug : function() {
									console.log(arguments);
									return '';
								},
								cleanValue : function(_value,ifNull) {
									ifNUll = (typeof ifNULL == 'undefined')? 'no disponible':ifNUll;									
									if(!_value) {
										return ifNUll;
									}
									
									return $('<div/>').text(_value).html();
								},
								getEditDataForField : function(value,column,isNew) {
									
									var extraConfig = column.config || false;
									var properties = column.properties || false;
									if (true === isNew) {
										var _value = '';
									} else {
										if (typeof value != 'object') {

											var _value = this.cleanValue(value,'');

										} else {
											// Casos de multiselect y multiLang 
											var _value = value;
										}
									}
									

									if (this.data.parentItem == column.id) {
										column.readonly = true;
										_value = this.data.parentId;
									}
									
									var fieldData = {
											_elemIden: column.id + this.data.randIden,
											_elemName: column.id,
											_readonly: column.readonly? true:false,
											_dataConfig : extraConfig,
											_properties : properties,
											_fieldValue : _value
									};

									
									var node = $("<div />");
									
									if (column.multilang) {
										var mlData = [];
										
										for (var i in this.data.langs) {
											
											var lang = this.data.langs[i];
											var _curValue = isNew? '':this.cleanValue(fieldData._fieldValue[lang] ,'');
											
											var _curFieldData = {
												_elemIden: column.id + lang + this.data.randIden,
												_elemName : column.id + lang,
												_readonly: column.readonly? true:false,
												_dataConfig : extraConfig,
												_properties : properties,
												_fieldValue: _curValue
											};
												
											var _node = $("<div />");
											
											$.tmpl(this.getTemplateNameForType(column.type),_curFieldData).appendTo(_node);
											mlData.push({
												_iden: _curFieldData._elemIden,
												_lang : lang,
												_field : _node.html()
											});
											
											
										}
										
										$.tmpl('klearmatrixMultiLangField',mlData).appendTo(node);
										
									} else {
										
										$.tmpl(this.getTemplateNameForType(column.type),fieldData).appendTo(node);
									}
									
									return node.html();
									
								},
								getColumnName : function(columns, columnId) {
								
									for(var idx in columns) {
										if (columns[idx].id == columnId) {
											return columns[idx].name;
										}
									}
									return false;
									
								},
								getIndex : function(values,idx) {
									if (!values[idx]) return 'error';
									return values[idx];
								},
								getMultiLangValue : function(value,langs,defaultLang) {
									var retItem = $("<div />");
									for (var i in langs) {
										var mlData = {
												_lang : langs[i],
												_value : this.cleanValue(value[langs[i]]),
												_default : (langs[i] == defaultLang)
										};
										_compiled = $.tmpl('klearmatrixMultiLangList',mlData);
										retItem.append(_compiled);

									}
									
									return retItem.html();								
									
								},
								getIndexFromColumn : function(values,column) {
										
									if (!values[column.id]) {
										switch(column.type) {
											default:
												return "no disponible";
										}
										
									} else {
										switch(column.type){
											case 'select':
												var _curVal = this.cleanValue(values[column.id]);
												if (column.config[_curVal]) {
													return column.config[_curVal];
												} else {
													return '';
												}
											break;
											case 'multiselect':
												
												var returnValue = [];
												for(var i in values[column.id]['relStruct']) {
													var relId = values[column.id]['relStruct'][i]['relatedId'];
													if (column.config[relId]) {
														returnValue.push(column.config[relId]);
													}
												}
												if (returnValue.length == 0) {
													return '<em>' + $.translate('no hay elementos asociados') + '</em>';
												} else {
													return returnValue.join(', ');
												}
												
											break;
											case 'file':
												return this.cleanValue(values[column.id]['name'])
											break;
											default:
												if (column.multilang) {
												
													return this.getMultiLangValue(values[column.id],this.data.langs,this.data.defaultLang);
												
												} else {
												
													return this.cleanValue(values[column.id]);
												}
											break;
										}
									}
								},
								getTemplateNameForType : function(type) {
									return 'klearMatrixFields' + type;
								},
								getTemplateForType : function(type) {
									return $.template[this.getTemplateNameForType(type)];
								},
								getPaginatorTemplate : function() {
									return $.template['klearmatrixPaginator'];
								},
								getTitle : function(title,idx) {
									
									if (false !== idx) {
										var defaultColumn = this.data.columns[0];
									
										for(var i in this.data.columns) {
											if (this.data.columns[i].default) {
												var defaultColumn = this.data.columns[i];
												break;
											}
										}
										

										var defaultValue = this.data.values[idx][defaultColumn.id];

										if (defaultColumn.multilang) {
											defaultValue = defaultValue[this.data.defaultLang];
											
										}
										
									} else {
										var defaultColumn = '';
									}

									return title
											.replace(/\%parent\%/,this.cleanValue(this.data.parentIden))
											.replace(/\%item\%/,this.cleanValue(defaultValue));
									
									
								}
			        
							});
			return $tmplObj;
			
		},
		_getClearText : function($item) {
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
		_registerBaseEvents : function() {
			
			var self = this.element;
			var _self = this;
			
			$(self.klearModule("getPanel")).on('click','.closeTab',function(e) {
				e.preventDefault();
				e.stopPropagation();
				
				self.klearModule("close");
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
				switch (_self.options.moduleName) {
					case 'list':
						var _parentHolder = $(this).parents("tr:eq(0)");
					break;
					case 'new':
					case 'edit':
						var _parentHolder = $(this).parents("form:eq(0)");
						
					break;
				}
				
				if ($(this).hasClass("_fieldOption")) {
					_menuLink.addClass("ui-state-highlight");
				}
				
				var tabTitle = ($(".default",_parentHolder).length>0) ? 
						_self._getClearText($(".default",_parentHolder)) : $(this).attr("title");
				
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
						pk 	   : _parentHolder.data("id"),
						post : {
							callerScreen : _self.options.data.screen,
						}
					};

					// Si la pantalla llamante tiene condición (parentId -- en data --
					// enviarlos a la nueva pantalla
					if (_self.options.data.parentId) {
						_dispatchOptions.post.parentId = _self.options.data.parentId;
						_dispatchOptions.post.parentScreen = _self.options.data.parentScreen;
					}
					
					
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
				
				var external = data && data.external || false
				
				var _container = self.klearModule("getContainer");
				
				switch (_self.options.moduleName) {
					case 'list':
						var _parentHolder = $(this).parents("tr:eq(0)");
					break;
					case 'new':
					case 'edit':
						var _parentHolder = $(this).parents("form:eq(0)");
					break;
				}
			
				var $caller = $(this);
				$(self).klearModule("showDialog",
						'<br />',
						{
							title: $(this).attr("title"),
							template : '<div class="ui-widget">{{html text}}</div>'
						});
				
				var $_dialog = $(self).klearModule("getModuleDialog");
				$_dialog.moduleDialog("setAsLoading");
				$_dialog.data("dialogName", $(this).data("dialog"));
				
				var _postData = (data && typeof data.params != undefined)? data.params:false;
				$.klear.request(
						{
							file: self.klearModule("option","file"),
							type: 'dialog',
							dialog : $_dialog.data("dialogName"),
							pk : _parentHolder.data("id"),
							post: _postData,
							external: external
						},
						function(response) {
							if (external) {
								$_dialog.moduleDialog("close");	
							} else {
								$_dialog[response.plugin]({data : response.data, parent: self, caller: $caller});
							}
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
			
			
			return this;
		}

	});

	
})(jQuery);
