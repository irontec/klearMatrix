(function load($) {
    $.widget("klearmatrix.selectautocomplete", {
        widgetEventPrefix:"file",
        lastCounter : 0,
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
            if (context.get(0).tagName.toLowerCase() == 'tr') {

                //Estamos en un listado
                //nothing to do here
                return;
            }

            this.options.cache.dummy = context;

            this.options.cache.context = context.parent();
            this.options.cache.element = this.options.cache.context.find("select");

            if (this.options.cache.element.length > 0) {

                this._initSelectedValue();
                this._initAutocomplete();

            } else {

                this._initFilteringForm();
            }
        },

        _initFilteringForm: function () {

            var _self = this;
            this.options.cache.context = this.options.cache.context.parent();
            this.options.cache.dummy = this.options.cache.dummy;

            this.options.cache.dummy.parent().on('manualchange',function(e) {

                if (_self.options.cache.dummy.filter(":selected").length > 0) {

                    e.stopImmediatePropagation();

                    var searchOption = _self.options.cache.context.find("span.searchOption");
                    var searchField = _self.options.cache.context.find("input.term");
                    var selectedValue = searchField.val();
                    var column = _self.options.parent.options.data.columns  ;

                    for (var idx in column) {

                        if (column[idx].config && column[idx].config.plugin) {

                            if(searchField[column[idx].config.plugin]) {

                                searchField[column[idx].config.plugin]("destroy");
                            }
                        }
                    }

                    searchOption.hide();
                     _self._initUIsAutocomplete(_self.options.cache.context.find("input.term").val(""), _self);
                }
            });
        },

        _postManualChange: function () {
            var recoveredValue = this.options.cache.element.data("recoveredValue");
            if (recoveredValue && (recoveredValue !== this.options.cache.element.val())) {
                this._initSelectedValue();
            }
        },

        _initSelectedValue: function () {
            var preloadValue = this.options.cache.element.data("preload");
            var recoveredValue = this.options.cache.element.data("recoveredValue");

            if (preloadValue || recoveredValue) {

                var _self = this;
                var value2load = recoveredValue || preloadValue;

                if (this.options.cache.element.find("option[value"+ value2load +"]").length == 0) {

                   var targetUrl = this.options.cache.dummy.attr("href") + "&value=" + value2load;

                   $.ajax({
                      url: targetUrl + "&reverse=true",
                      dataType: 'json',
                      type: 'GET',
                      async: false,
                      success: function(data) {

                        _self.options.cache.element.children("[selected]").removeAttr("selected");

                        for (first in data.results) break;
                        var element = data.results[first];
                        var option = $("<option>").attr("value", element.id )
                                                  .attr("selected", "selected")
                                                  .html(element.value);

                        option.appendTo(_self.options.cache.element);
                        _self.options.cache.element.val(element.id);

                        if (_self.input && _self.input.data("autocomplete")) {
                            _self.input.val(element.value);
                            _self.input.data("autocomplete")._trigger("change");
                        }

                        if (recoveredValue) {
                            _self.options.cache.element.data("recoveredValue", null);
                        }

                        _self.options.cache.element.trigger("change");
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
                .addClass("ui-state-default ui-combobox-input ui-corner-all")
                .addClass("ui-widget ui-widget-content ui-corner-left");

            //Se le envía el target para el highlight y se bindea "postmanualchange"
            this.select.data('target-for-change',this.select.next("span").children("input:eq(0)"));
            this.select.on('postmanualchange', function() {
                _self._postManualChange();
            });

           this._initUIsAutocomplete(this.input, this);
        },

        _initUIsAutocomplete: function (targetNode, context) {
            var self = this;
            var _self = context;

            targetNode.autocomplete({
                delay: 0,
                minLength: 0,
                source: function( request, response ) {

                    var term = request.term;
                    $.getJSON( _self.options.cache.dummy.attr("href") , request, function( data, status, xhr ) {
                        _self.lastCounter = data.totalItems;
                        response(
                            $.map( data.results, function( item ) {
                                return {
                                    label: item.label,
                                    value: item.value,
                                    id: item.id
                                };
                            })
                        );
                    });
                },

                select: function( event, ui ) {

                    _self.options.cache.context.find("input.term").data("idItem", ui.item.id);
                    var option = _self.options.cache.element.children("[value="+ ui.item.id +"]");

                    if (! option.get(0)) {

                        option = $("<option />")
                                    .attr("value", ui.item.id )
                                    .html(ui.item.value);

                        option.appendTo(_self.options.cache.element);
                    }

                    option.get(0).selected = true;

                    if(_self.select) {
                        _self.select.trigger('manualchange');
                    }

                    _self._trigger( "selected", event, {
                        item: option
                    });
                },
                open : function() {
                    if ($(".autocompleteCounter",$(this).parents("span:eq(0)")).length == 0) {
                        $(this).parents("span:eq(0)").append('<span class="autocompleteCounter"></span>');
                    }
                    var $counter = $(".autocompleteCounter",$(this).parents("span:eq(0)"));
                    $counter.html(_self.lastCounter).show();

                },
                close : function() {
                    $(".autocompleteCounter",$(this).parents("span:eq(0)")).fadeOut('fast');
                },
                change: function( event, ui ) {

                    if ( !ui.item ) {
                        return _self._removeIfInvalid( this );
                    }
                }

            }).on("focusin", function () {

                $(this).select();
            });

            targetNode.data("autocomplete")._renderItem = function( ul, item ) {

                return $( "<li>" ).data( "item.autocomplete", item )
                                  .append( "<a>" + item.label + "</a>" )
                                  .appendTo( ul );
            };

            targetNode.tooltip({
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
