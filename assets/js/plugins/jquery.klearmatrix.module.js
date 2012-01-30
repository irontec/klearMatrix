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
								getDataForFieldTemplate : function(value,column) {
									var extraConfig = column.config || false
									
									// htmlentities in JS ;)
									
									var _text = (!value)? '':$('<div/>').text(value).html();
									
									var ret = {
											_elemIden: column.id + this.data.randIden,
											_elemName: column.id,
											_readonly: column.readonly? true:false,
											_dataConfig : extraConfig,
											_fieldValue: _text
									};
									return ret;
								},
								getIndex : function(values,idx) {
									if (!values[idx]) return 'error';

									return values[idx];
									
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
												var _curVal = values[column.id];
												return column.config[_curVal];
											break;
											default:
												return $('<div/>').text(values[column.id]).html();
											break;
										}
									}
								},
								getTemplateForType : function(column) {
									return $.template['clearMatrixFields' + column.type];
								},
								getPaginatorTemplate : function() {
									return $.template['klearmatrixPaginator'];
								},
								getTitle : function(title,idx) {
									
									if (false !== idx) {
										var defaultColumn = this.data.columns[0].id;
									
										for(var i in this.data.columns) {
											if (this.data.columns[i].default) {
												var defaultColumn = this.data.columns[i].id;
												break;
											}
										}
										var defaultColumn = this.data.values[idx][defaultColumn];
									} else {
										var defaultColumn = '';
									}

									return title
											.replace(/\%parent\%/,this.data.parentIden)
											.replace(/\%item\%/,defaultColumn);
									
									
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
