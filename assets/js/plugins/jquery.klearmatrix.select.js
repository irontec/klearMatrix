(function load($) {

    $.widget("klearmatrix.selectautocomplete", $.klearmatrix.module, {
        widgetEventPrefix:"file",

        cache : {}, //cache para autocomplete

        options: {
            cache: {}, // cache de nodos dom
        },

        element : null,
        wrapper: null,
        input : null,
        select: null,
        selected: null,
        value: null,

        _setOption: function (name, value) {

            $.Widget.prototype._setOption.apply(this, arguments);
        },

        _create: function() {

            var self = this;
        },

        _init: function () {

            var context = this.element.klearModule("getPanel");

            this.options.cache.dummy = context;

            this.options.cache.context = context.parent();
            this.options.cache.element = this.options.cache.context.find("select");

            this._initSelectedValue();
            this._initAutocomplete();
        },

        _initSelectedValue: function () {

            if (this.options.cache.element.data("preload")) {

                var _self = this;
                var preloadValue = this.options.cache.element.data("preload");

                if (this.options.cache.element.find("option[value"+ preloadValue +"]").length == 0) {

                    var targetUrl = this.options.cache.dummy.attr("href").replace("value=__NULL__", "value=" + preloadValue);


                   $.ajax({
                      url: targetUrl + "&reverse=true",
                      dataType: 'json',
                      type: 'GET',
                      async: false,
                      success: function(data){

                        var option = $("<option>").attr("value", data[0].id )
                                                  .html(data[0].value);

                        option.appendTo(_self.options.cache.element);
                        option.attr("selected",true);
                      }
                   });

                }
            }

        },

        _initAutocomplete: function () {

            var _self = this;

            this.select = this.options.cache.element.hide(),
            this.selected = this.select.children( ":selected" ),

            this.value = this.selected.val() ? this.selected.text() : "",
            this.wrapper = $("<span>").addClass( "ui-combobox" ).insertAfter( this.select );

            this.wrapper.append('<span class="ui-icon inline ui-icon-script"></span>');

            this.input = $( "<input>" )
                .appendTo( this.wrapper )
                .val( this.value )
                .attr( "title", "" )
                .addClass( "ui-state-default ui-combobox-input ui-corner-all" )
                .autocomplete({
                    delay: 0,
                    minLength: 0,
                    source: function( request, response ) {

                        var term = request.term;

                        if ( term in _self.cache ) {
                            response( _self.cache[ term ] );
                            return;
                        }

                        $.getJSON( _self.options.cache.dummy.attr("href") , request, function( data, status, xhr ) {

                            _self.cache[ term ] = data;
                            response( data );
                        });
                    },

                    select: function( event, ui ) {

                        var option = _self.options.cache.element.children("[value="+ ui.item.id +"]");

                        if (! option.get(0)) {

                            option = $("<option />")
                                        .attr("value", ui.item.id )
                                        .html(ui.item.value);

                            option.appendTo(_self.options.cache.element);
                        }

                        option.get(0).selected = true;

                        _self._trigger( "selected", event, {
                            item: option
                        });
                    },

                    change: function( event, ui ) {
                        if ( !ui.item )
                            return _self._removeIfInvalid( this );
                    }
                })
                .addClass( "ui-widget ui-widget-content ui-corner-left" );

            this.input.data( "autocomplete" )._renderItem = function( ul, item ) {
                return $( "<li>" )
                    .data( "item.autocomplete", item )
                    .append( "<a>" + item.label + "</a>" )
                    .appendTo( ul );
            };

            this.input
                .tooltip({
                    position: {
                        of: this.button
                    },
                    tooltipClass: "ui-state-highlight"
                });
        },

        _removeIfInvalid : function (element) {

                var _self = this;

                var value = $( element ).val(),
                    matcher = new RegExp( "^" + $.ui.autocomplete.escapeRegex( value ) + "$", "i" ),
                    valid = false;

                this.select.children( "option" ).each(function() {
                    if ( $( this ).text().match( matcher ) ) {
                        this.selected = valid = true;
                        return false;
                    }
                });

                if ( !valid ) {
                    // remove invalid value, as it didn't match anything
                    $( element )
                        .val( "" )
                        .attr( "title", value + " didn't match any item" )
                        .tooltip( "open" );

                    this.select.val( "" );
                    setTimeout(function() {
                        _self.input.tooltip( "close" ).attr( "title", "" );
                    }, 2500 );

                    this.input.data( "autocomplete" ).term = "";
                    return false;
                }
        },

        destroy: function() {

            // remove classes + data
            this.wrapper.remove();
            this.options.cache.element.show();

            $.Widget.prototype.destroy.call( this );
            return this;
        },
    });

    $.widget.bridge("klearmatrix.selectautocomplete");

})( jQuery );
