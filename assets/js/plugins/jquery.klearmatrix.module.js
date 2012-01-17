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
		_setOption : function() {
			$.Widget.prototype._setOption.apply(this,arguments)
		},
		_loadTemplate : function(tmplName) {
			var $tmplObj = $.tmpl(
							tmplName,
							this.options.data,
							{ 
								getDataForFieldTemplate : function(value,column) {
									return {
											elemIden: column.iden + this.data.randIden,
											fieldValue:value
									};
								},
								getIndex : function(values,idx) {
									if (!values[idx]) return 'error';

									return values[idx];
									
								},
								getIndexFromColumn : function(values,column) {
									
									if ('undefined' === typeof values[column.id]) {
										
										switch(column.type) {
											case "_option":
												
												if (this.data.fieldOptions) {
													var ret = [];

													for(var i=0;i<this.data.fieldOptions.length;i++) {
														
														var _op = this.data.fieldOptions[i];
														
														var _type = _op.type || 'screen';
														ret.push('<a class="_fieldOption '+_type+'" href="" data-'+_type+'="'+_op[_type]+'" title="'+_op.title+'">');
														ret.push('<span class="ui-silk inline '+_op.class+'"></span>');
														if (_op.label) {
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
									
										return values[column.id];
									}
								},
								getTemplateForType : function(column) {
									return $.template['clearMatrixFields' + column.type];
								}
			        
							});
			
			$(this.element.klearModule("getPanel")).append($tmplObj);
			return this;
			
		}

	});

	
})(jQuery);
