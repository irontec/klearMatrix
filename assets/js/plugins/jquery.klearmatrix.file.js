(function load($) {

    $.widget("klearmatrix.filepreview", $.klearmatrix.module, {
        widgetEventPrefix:"file",

        options: {
            cache: {},
        },

        _setOption: function (name, value) {

            $.Widget.prototype._setOption.apply(this, arguments);
        },
        _create: function() {

            var self = this;
            this._initPreview();
        },

        _init: function () {

            var context = this.element.klearModule("getPanel");

            this.options.cache.dummy = context;
            this.options.cache.context = context.parent();
        },

        _initPreview: function () {

            var image = $("<img class=\"imgFilePreviewList\" />");
            image.attr("src", this.element.attr("href"));
            this.element.replaceWith(image);

        },

        destroy: function() {

            // remove classes + data
            $.Widget.prototype.destroy.call( this );
            return this;
        },
    });
    $.widget.bridge("klearmatrix.filepreview");

    $.widget("klearmatrix.filelistpreview", $.klearmatrix.filepreview, {

        _initPreview: function () {

            var image = $("<img class=\"imgFilePreviewList\" />");
            image.attr("src", this.element.attr("href"));
            this.element.replaceWith(image);

        },
    });
    $.widget.bridge("klearmatrix.filelistpreview");

})( jQuery );
