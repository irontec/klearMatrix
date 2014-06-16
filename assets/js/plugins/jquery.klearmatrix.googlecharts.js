;(function load($) {

    if (!$.klear.checkDeps(["$.klearmatrix.module","$.ui.form"],load)) {
    	return;
	}
    $.custom = $.custom || {};

    var __namespace__ = "klearmatrix.googlecharts";
    var data;
    $.widget("klearmatrix.googlecharts", $.klearmatrix.module,  {
        options: {
        	data: null,
            moduleName: 'googlecharts'
        },
        _super: $.klearmatrix.module.prototype,
        _create: function(){
        	this._super._create.apply(this);
        },

        _getOtherInstances: function() {

            var element = this.element;

            return $.grep($.custom[this.options.moduleName].instances, function(el){
                return el !== element;
            });
        },

        destroy: function() {
            // remove this instance from $.custom.mywidget.instances
            var element = this.element,
            position = $.inArray(element, $.custom[this.options.moduleName].instances);

            // if this instance was found, splice it off
            if(position > -1){
                $.custom[this.options.moduleName].instances.splice(position, 1);
            }

            // call the original destroy method since we overwrote it
            $.Widget.prototype.destroy.call( this );
        },

        _setOption : function(key, value) {
            $.Widget.prototype._setOption.apply(this,arguments)
        },

        _init: function() {

        	$.extend(this.options.data,{randIden: Math.round(Math.random(1000,9999)*100000)});

        	//var $appliedTemplate = this._loadTemplate("customCustomDashboard");

        	var $template = $.tmpl(
                    "klearmatrixGooglecharts",
                    this.options.data,
                    $.klearmatrix.template.helper
                    );


        	console.log(this.options.data.title, $template);

        	$(this.element.klearModule("getPanel")).append($template);
        	data = this.options.data;
            this._initPlugin();
        },

        _initPlugin: function() {
        	$.getScript('https://www.google.com/jsapi', function()
        			{
        		 function drawVisualization(chartName, chart) {
        			 	console.log(chart.table);
        		        var wrap = new google.visualization.ChartWrapper({
        		           'chartType': chart.type,
        		           'dataTable': chart.table,
        		           'options': chart.options,
        		           'containerId': chartName+'_div'
        		           });
        		         wrap.draw();
        		 }
        		 console.log(data.chartGroups);
        		 $.each(data.chartGroups, function (gIndex, group){
        			 $('#'+gIndex+'_comment_div').html(group.comment);
        			 $.each(group.charts, function (cIndex, chart) {
        				 $('#'+gIndex+"_"+cIndex+'_comment_div').html(chart.comment);
        				 $('#'+gIndex+"_"+cIndex+'_legend_div').html(chart.legend);
 						//$.each(chart, function( index, value ) {
 							google.load('visualization', '1.0', {'packages':['corechart'], callback: function(){ drawVisualization(cIndex, chart) }});
 						//});
 					});
        		 });
        		});
        }
    });
    $.widget.bridge("googlecharts", $.klearmatrix.googlecharts);

})(jQuery);
