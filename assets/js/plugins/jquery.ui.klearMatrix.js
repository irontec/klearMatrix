;(function($) {

		
	$.widget("ui.klearMatrix", {
		options: {
			ui: null,
			container: null,
			mainEnl : null,
        	title : null,
            file : null,
            panel : null,
            tabIndex : null,
            baseurl : null,
            menuLink : null,
            foo : '22'
		},
		
		_create: function(){

			// remember this instance
			$.ui.klearMatrix.instances.push(this.element);
		},
		_getOtherInstances: function(){
			
			var element = this.element;

			return $.grep($.ui.kModule.instances, function(el){
				return el !== element;
			});
		},
		
		_init: function() {
				
				this.options.mainEnl = $("a:first",this.element),
				this.options.title = $("a:first",this.element).html(),
				this.options.file = $("a:first",this.element).attr("href").replace(/\#tabs\-/,''),
				this.options.panel = this.options.ui.panel,
				this.options.tabIndex = this.options.ui.index,
				this.options.menuLink = $('#target-' + this.options.file);
				
				this._loadIcon();
		},
		_loadIcon : function() {
		    
				if ($("span.ui-silk",this.options.menuLink.parent()).length > 0) {
				
					var _menuLink = this.options.menuLink;
					var curClasses = $("span.ui-silk",this.options.menuLink.parent()).attr("class").split(' ');
					
					$("span.ui-silk",this.element)
						.addClass(curClasses[(curClasses.length-1)])
						.on('click',function() {
							$(_menuLink).trigger("click");
						});
				}
				
		},
		_setOption: function(key, value){
			
			this.options[key] = value;

			switch(key){
				case "title":
					this.options.mainEnl.html(value);
				break;
			}
		},
		destroy: function(){
			// remove this instance from $.ui.mywidget.instances
			var element = this.element,
			position = $.inArray(element, $.ui.mywidget.instances);

			// if this instance was found, splice it off
			if(position > -1){
				$.ui.kModule.instances.splice(position, 1);
			}

			// call the original destroy method since we overwrote it
			$.Widget.prototype.destroy.call( this );
		},
		_loadTemplates : function(templates) {
			
			var dfr = $.Deferred();
		
			var total = $(templates).length;
			var done = 0;
		
			var successCallback = function() {
				total--;
				done++;
				if (total == 0) {
					dfr.resolve(done);		
				}									
			};
		
			for (var tmplIden in templates) {
				var tmplSrc = templates[tmplIden];
				
				if (undefined !== $.template[tmplIden]) {
					successCallback();
					return;
				}

				$.ajax({
					url: this.options.baseurl + tmplSrc,
					dataType:'text',
					type : 'get',
					success: function(r) {
						$.template(tmplIden, r);
						successCallback();
					},
					error : function(r) {
						dfr.reject($.translate("Error descargando el template [%s]", tmplIden)); 
					}
				}); 
			}
			
			return dfr.promise();							
		},
		
		_loadScripts : function(scripts) {
			var dfr = $.Deferred();
			var total = $(scripts).length;
			var done = 0;

		    
			for(var i=0;i<total;i++) {
				
				  $.ajax({
            			url: this.options.baseurl + scripts[i],
            			dataType:'script',
            			type : 'get',
            			success: function() {
            				total--;
							done++;
							if (total == 0) {
								dfr.resolve(done);		
							}
                        },
                        error : function(r) {
                            dfr.reject("Error descargando el sript ["+scripts[i]+"]"); 
            			}
				  }); 
			  }
			  return dfr.promise();							
		},
		_loadCss : function(css) {
			
			var total = $(css).length;
			var dfr = $.Deferred();
			
			for(var iden in css) {
				$.getStylesheet(this.options.baseurl + css[iden],iden);
				$("#" + iden).on("load",function() {
					total--;
					if (total == 0) {
						dfr.resolve(true);		
					}
				});
			}
			
			dfr.promise(true);							
		},
		_parseDispatchResponse : function(response) {
			 
			if ( (!response.baseurl) || (!response.templates) || (!response.scripts) || (!response.css) || (!response.data) || (!response.module) ) {
				alert("Formato de respuesta incorrecta.<br />Consulte con su administrador.");
				return;							
			}
			
					
			this.options.baseurl = response.baseurl;
			var self = this.element;
			
			$.when(
				this._loadTemplates(response.templates),
				this._loadCss(response.css),
				this._loadScripts(response.scripts)
			).done( function(tmplReturn,scriptsReturn,cssReturn) {
					
					
					if (typeof $.fn[response.module] == 'function' ) {
							$(self)[response.module](response.data);
						}
			            
		            }).fail( function( data ){
		            	console.log("error",data);
		            	$self.trigger("alert","Error registrando el módulo");				                    
		            });	
			
		},
		mostrar : function(msg) {
			var self = this;
			
			
			window.setInterval(function(){ console.log(self.options.foo);},1000);
			
		},
		dispatch : function() {
            $.ajax({
               	url:$.baseurl + 'index/dispatch',
               	dataType:'json',
               	context : this,
               	data : {file:this.options.file},
               	type : 'get',
               	success: this._parseDispatchResponse
            });
		}

	});

	
	$.extend($.ui.kModule, {
		instances: []
	});
		
})(jQuery);
