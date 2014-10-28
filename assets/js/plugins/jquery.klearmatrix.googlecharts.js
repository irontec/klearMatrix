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




        	$(this.element.klearModule("getPanel")).append($template);

        	this._parseDefaultItems();

        	this._initPlugin();
        },

        _initPlugin: function() {

        	var $panel = $(this.element.klearModule("getPanel"));

        	var idPrefix = "chart_"+this.options.data.randIden+"_";

        	var data = this.options.data.values;

        	$.getScript('https://www.google.com/jsapi', function() {


        		function drawVisualization(chartName, chart) {

        			var dateParts = chart.table[1][0].match(/\d+/g);
        			if(dateParts){
        				var date = new Date(dateParts[0], dateParts[1] - 1, dateParts[2], dateParts[3], dateParts[4], dateParts[5]);
            			if (!isNaN(date)){
                			$.each(chart.table, function(index, value){
                				if (index != 0){
                					dateFragments = value[0].split("-");
                					var year = parseInt(dateFragments[0],10);
                					var month = parseInt(dateFragments[1],10)-1;
                					var day = parseInt(dateFragments[2].split(" ")[0],10);
                					var date = new Date(year,month,day);
                					if (!isNaN(date.valueOf())){
                						chart.table[index][0] = date;
                					}
                				}
                			});
            			}
        			}

        			var data = google.visualization.arrayToDataTable(chart.table);

        			if (chart.controls){
            			var dashboard = new google.visualization.Dashboard(
            				document.getElementById(chartName+'_dashboard_div')
            			);
            			var filters = [];
            			$.each(chart.controls.filters, function(filterIIndex, filterI){
            				filters.push(new google.visualization.ControlWrapper({
                				'controlType': filterI.controlType,
                				'containerId': chartName+'_filter_'+filterIIndex+'_div',
                				'options': filterI.options
                			}));

            			});
        			}

    			    var wrap = new google.visualization.ChartWrapper({
    		           'chartType': chart.type,
    		           'dataTable': data,
    		           'options': chart.options,
    		           'containerId': chartName+'_div',
    		           'view': chart.view
			    	});

    			    if (chart.controls){
    			    	dashboard.bind(filters, wrap);
    			    	dashboard.draw(data);
    			    } else {
    			    	wrap.draw(data);
    			    }
        		 }
        		 $.each(data.chartGroups, function (gIndex, group){
        			 $('#'+idPrefix+gIndex+'_comment_div', $panel).html(group.comment);

        			 $.each(group.charts, function (cIndex, chart) {

        				 $('#'+idPrefix+gIndex+"_"+cIndex+'_comment_div', $panel).html(chart.comment);

        				 $('#'+idPrefix+gIndex+"_"+cIndex+'_legend_div', $panel).html(chart.legend);
        				 var maxSize = $('#canvas').width()-145;
        				 maxSize = maxSize.toFixed();
        				 var chartWidth = chart.options.width;
        				 if( parseInt(chartWidth,10) > parseInt(maxSize,10)){
        					 chart.options.width = maxSize;
        				 }
						google.load('visualization', '1.0', {'packages':['corechart','controls'], callback: function(){ drawVisualization(idPrefix+gIndex+"_"+cIndex, chart) }});
 					});
        		 });
        		});
        }
    });
    $.widget.bridge("googlecharts", $.klearmatrix.googlecharts);

})(jQuery);
