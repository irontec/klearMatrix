;(function load($) {

    if (!$.klear.checkDeps(['$.klearmatrix.module','$.ui.form'],load)) {
        return;
    }

    var __namespace__ = "klearmatrix.genericscreen";

    $.widget("klearmatrix.genericscreen", $.klearmatrix.module,  {
        options: {
            data : null,
            moduleName: 'genericscreen'
        },
        _super: $.klearmatrix.module.prototype,
        _create : function() {
            this._super._create.apply(this);
        },
        _init: function() {

            if (this.options.data.templateName) {
            	var $appliedTemplate = this._loadTemplate(this.options.data.templateName);
            	$(this.element.klearModule("getPanel")).append($appliedTemplate);
            
            	this._registerBaseEvents();
             
            }
        }    
    });

    $.widget.bridge("klearMatrixGenericScreen", $.klearmatrix.genericscreen);

})(jQuery);