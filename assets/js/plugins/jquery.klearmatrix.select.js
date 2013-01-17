(function load($) {

    $.widget("klearmatrix.selectautocomplete", $.klearmatrix.module, {
        widgetEventPrefix:"file",

        options: {
            cache: {},
        },

        _setOption: function (name, value) {

            $.Widget.prototype._setOption.apply(this, arguments);
        },
        _create: function() {

            var self = this;
            //this._initPreview();
        },

        _init: function () {

            console.log("selectautocomplete");
            var context = this.element.klearModule("getPanel");

            this.options.cache.dummy = context;
            this.options.cache.context = context.parent();
        },

        destroy: function() {

            // remove classes + data
            $.Widget.prototype.destroy.call( this );
            return this;
        },
    });

    console.log("chackpoint selectautocomplete");
    $.widget.bridge("klearmatrix.selectautocomplete");

})( jQuery );
