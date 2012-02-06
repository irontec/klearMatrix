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
								cleanValue : function(_value,ifNull) {
									ifNUll = (typeof ifNULL == 'undefined')? 'no disponible':ifNUll;									
									if(!_value) {
										return ifNUll;
									}
									
									return $('<div/>').text(_value).html();
									
									
								},
								getEditDataForField : function(value,column,isNew) {
									
									var extraConfig = column.config || false

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
									
									var fieldData = {
											_elemIden: column.id + this.data.randIden,
											_elemName: column.id,
											_readonly: column.readonly? true:false,
											_dataConfig : extraConfig,
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
		_registerBaseEvents : function() {
			
			var self = this.element;
			
			$(self.klearModule("getPanel")).on('click','.closeTab',function(e) {
				e.preventDefault();
				e.stopPropagation();
				
				self.klearModule("close");
			});
			
			return this;
		}

	});

	
})(jQuery);
