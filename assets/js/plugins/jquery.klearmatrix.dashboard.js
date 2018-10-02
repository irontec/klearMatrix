;(function load($) {

    if (!$.klear.checkDeps(['$.klearmatrix.module','$.ui.form'],load)) {
        return;
    }

    var __namespace__ = "klearmatrix.dashboard";
    
    $.widget("klearmatrix.dashboard", $.klearmatrix.module,  {
        options: {
            data : null,
            moduleName: 'dashboard'
        },
        _super: $.klearmatrix.module.prototype,
        _create : function() {
            this._super._create.apply(this);
        },
        _init: function() {

            //this.options.data.title = this.options.data.title || this.options.title;

            var $appliedTemplate = this._loadTemplate("klearmatrixDashboard");

            $(this.element.klearModule("getPanel")).append($appliedTemplate);

            this
                ._applyDecorators()
                ._registerBaseEvents()
                ._registerEvents()
                ._css3Columns();

            $(document).trigger("kDashboardLoaded");


        },
        _applyDecorators : function() {

            var self = this.element;
            var _self = this;

            var $container = $(this.element.klearModule("getPanel"));

            $(".generalOptionsToolbar .action, .generalOptionsToolbar a",$container).button();

            return this;
        },
        _registerEvents : function() {

            var self = this.element;
            var _self = this;
            var $container = $(this.element.klearModule("getPanel"));

            return this;
        },
       _css3Columns: function () {

            var self = this.element;
            var _self = this;
            var $container = $(this.element.klearModule("getPanel"));

            var wrapper = $container.find('div.klearMatrixDashboard > div');
            var boxes = wrapper.children('fieldset');
            var box = boxes.filter(':eq(0)');

            var boxWidth = box.width();

            var css3VendorPrefixes = [
                '-moz',
                '-webkit',
                '-o',
                '-ms',
                '-khtml'
            ];

            for (prefix in css3VendorPrefixes) {

                var boxWidth = prefix + '-column-width';

                wrapper.css({ boxWidth : '300px'});
            }

            wrapper.css({
                'column-width' : '300px',
                'display': 'flex',
                'flex-direction': 'row',
                'justify-content': 'flex-start',
                'align-items': 'flex-start',
                'flex-wrap': 'nowrap'
            });

            for (prefix in css3VendorPrefixes) {

                var boxColumnBreak = prefix + "-column-break-inside";
                boxes.css({ boxColumnBreak : 'avoid'});
            }

            boxes.css({ 'column-break-inside' : 'avoid'});

            if (boxWidth != box.width()) {

                boxes.removeClass("legacy");
                boxes.css({'width': '85%', 'flex-basis': '275px'});
            }

            return this;
        }
    });

    $.widget.bridge("dashboard", $.klearmatrix.dashboard);

})(jQuery);