;(function load($) {
    $.widget("klearmatrix.multiselectautocomplete", {
        widgetEventPrefix:"file",
        lastCounter : 0,
        options: {
            cache: {} // cache de nodos dom
        },

        element : null,
        wrapper: null,
        input : null,
        select: null,
        selected: null,
        value: null,
        selectedList: null,
        selectedListSkeleton: null,

        _setOption: function (name, value) {
            $.Widget.prototype._setOption.apply(this, arguments);
        },

        _create: function() {
            var self = this;
        },

        _init: function () {

            var context = this.element.klearModule("getPanel");
            var _self = this;
            if (context.get(0).tagName.toLowerCase() == 'tr') {

                //Estamos en un listado
                //nothing to do here
                return;
            }

            this.options.cache.dummy = context;

            this.options.cache.context = context.parent();
            this.options.cache.element = this.options.cache.context.find("select");
            this.options.cache.searchBox = this.options.cache.context.find("input.term");
            this.options.cache.selectedList = this.options.cache.context.find("ul.selectedList");

            this.options.cache.selectedListSkeleton = $(this.options.cache.selectedList.children().get(0)).clone(true);
            this.options.cache.selectedList.children("li").remove();

            if (this.options.cache.element.length > 0) {

                this._initSelectedValue();
                this._initAutocomplete();
                this._initCustomEvents();

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

        _initCustomEvents: function () {

            var _self = this;
            this.options.cache.selectedList.on("click", "li a.remove", function (e) {

                e.preventDefault();
                e.stopPropagation();

                _self._removeFromSelectedList($(this).parent());
                return;
            });
        },


        _postManualChange: function () {
            var recoveredValue = this.options.cache.element.data("recoveredValue");
            if (recoveredValue && (recoveredValue !== this.options.cache.element.val())) {
                this._initSelectedValue();
            }
        },

        _initSelectedValue: function () {
            var preloadValue = this.options.cache.element.val();
            var recoveredValue = this.options.cache.element.data("recoveredValue");
            
            this.options.cache.originalValue = preloadValue;

            if (preloadValue || recoveredValue) {

                var _self = this;
                var value2load = recoveredValue || preloadValue;

                if (value2load) {

                   var targetUrl = this.options.cache.dummy.attr("href");

                   $.ajax({
                      url: targetUrl + "&reverse=true",
                      dataType: 'json',
                      data: {
                          'value':  value2load
                      },
                      type: 'GET',
                      async: false,
                      error: function (jqXHR, textStatus, errorThrown) {

                        console.log("error", jqXHR);
                      },
                      success: function(data) {

                        var _parentContext = _self;
                        _parentContext._cleanSelectedList();
                        $.each(data.results, function () {
                            var record = this[0];
                            var targetNode = _parentContext.options.cache.element.children('[value='+ record['id'] +']');

                            if (targetNode.length > 0) {
                                targetNode.attr("selected", "selected");
                                targetNode.html(record.label);
                            } else {
                                var newOption = $("<option>").attr("value", record.id )
                                                             .attr("selected", "selected")
                                                             .html(record.value);
                                _parentContext.options.cache.element.append(newOption);
                            }

                            _parentContext._addToSelectedList(record.id, record.label, true);
                        });
                      }
                   });

                }
            }
        },
        _addToSelectedList: function (id, label, isReload) {

            if (this.options.cache.selectedList.find('li[data-value='+ id +']').length == 0) {

                var newSelectedListElement = this.options.cache.selectedListSkeleton.clone();
                newSelectedListElement.attr("data-value", id);
                newSelectedListElement.children("span").html(label);
                this.options.cache.selectedList.append(newSelectedListElement);
            }

            var targetOption = this.options.cache.element.find("option[value="+ id +"]");
            if (targetOption.length == 0) {

                //add option
                var newOption = $("<option />");
                newOption.attr("value", id);
                newOption.attr("selected", "selected");
                newOption.html(label);

                this.options.cache.element.append(newOption);
            } else {
                targetOption.attr("selected", "selected");
            }
            
            if (!isReload) {
                
                var currentValue = this.options.cache.element.val();
                var originalValue = this.options.cache.originalValue;
                
                if (this.checkArrays(originalValue, currentValue)) {
                    this.options.cache.element.removeClass('changed');
                } else {
                    this.options.cache.element.addClass('changed');
                }
                
            }
            
        },

        _removeFromSelectedList: function (node) {
            
            var nodeValue = node.attr("data-value");
            node.remove();
            this.options.cache.element.find("option[value="+nodeValue+"]").removeAttr("selected");
            
            var currentValue = this.options.cache.element.val();
            var originalValue = this.options.cache.originalValue;
            
            if (this.checkArrays(originalValue, currentValue)) {
                this.options.cache.element.removeClass('changed');
            } else {
                this.options.cache.element.addClass('changed');
            }
            
            this.options.parent.options.theForm.trigger("updateChangedState");
            
        },

        _cleanSelectedList: function() {
            this.options.cache.element.find('option').remove();
            this.options.cache.selectedList.find('li').remove();
        },

        _initAutocomplete: function () {

            var _self = this;

            this.select = this.options.cache.element; //.hide(),
            this.select.on('postmanualchange', function() {
                _self._postManualChange();
            });

            this._initUIsAutocomplete(this.options.cache.searchBox, this);
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
                                    label: item[0].label,
                                    value: item[0].value,
                                    id: item[0].id
                                };
                            })
                        );
                    });
                },
                select: function( event, ui ) {

                     _self.options.cache.context.find("input.term").data("idItem", ui.item.id);
                    _self._addToSelectedList(ui.item.id, ui.item.label);

                    /*if(_self.select) {
                        _self.select.trigger('manualchange');
                    }

                    _self._trigger( "selected", event, {
                        item: option
                    });*/
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
                    _self.options.cache.searchBox.val("");
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
        checkArrays: function( arrA, arrB ) {

            if (arrA == undefined) return false;
            if (arrA.length !== arrB.length) return false;

            var cA = arrA.slice().sort().join(","); 
            var cB = arrB.slice().sort().join(",");

            return cA===cB;

        }
    });

    $.widget.bridge("klearmatrix.multiselectautocomplete");

})( jQuery );