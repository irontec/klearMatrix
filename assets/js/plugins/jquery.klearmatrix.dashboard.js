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
                ._registerEvents();

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
        }
    });

    $.widget.bridge("dashboard", $.klearmatrix.dashboard);

})(jQuery);