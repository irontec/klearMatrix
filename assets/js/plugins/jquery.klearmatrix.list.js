;(function load($) {

    if (!$.klear.checkDeps(['$.klearmatrix.module','$.ui.form'],load)) {
        return;
    }

    
    var __namespace__ = "klearmatrix.list";
    
    $.widget("klearmatrix.list", $.klearmatrix.module,  {
        options: {
            data : null,
            moduleName: 'list'
        },
        _super: $.klearmatrix.module.prototype,
        _create : function() {
            this._super._create.apply(this);
        },
        _init: function() {

            this.options.data.title = this.options.data.title || this.options.title;

            var $appliedTemplate = this._loadTemplate("klearmatrixList");
            $(this.element.klearModule("getPanel")).append($appliedTemplate);

            this
                ._applyDecorators()
                ._registerBaseEvents()
                ._registerEvents();

        },
        _applyDecorators : function() {
            $container = $(this.element.klearModule("getPanel"));

            $(".generalOptionsToolbar .action, .generalOptionsToolbar a",$container).button();

            if ($("td.multilang",$container).length>0) {
                
                var $mlSelector = $("<span>").addClass("ui-silk ui-silk-comments mlTag").attr("title",$.translate("Field available in multi-language", [__namespace__]));
                
                $("td.multilang",$container).each(function() {
                    $(this).prepend($mlSelector.clone().tooltip());
                });
            }

            return this;
        },
        _registerEvents : function() {

            var self = this.element;
            var _self = this;
            var panel = this.element.klearModule("getPanel");

            // highlight effect on tr
            $('table.kMatrix tr',panel).on('mouseenter mouseleave',function() {
                $("td",$(this)).toggleClass("ui-state-highlight");

                if ($("a.option.default",$(this)).length>0) {
                    $(this).toggleClass("pointer");
                    $("a.option.default",$(this)).toggleClass("ui-state-active");
                }

            })
            .on('mouseup',function(e) {
                // Haciendo toda la tupla clickable para la default option
                e.stopPropagation();
                e.preventDefault();
                $.klear.checkNoFocusEvent(e, $(panel).parent(),$("a.option.default",$(this)));

                $("a.option.default",$(this)).trigger("mouseup");
            });

            $('a._fieldOption', panel).on('mouseenter',function(e) {
                if ($(this).data("relatedtab")) {
                    $(this).data("relatedtab").klearModule("highlightOn");
                }
            }).on('mouseleave',function(e) {
                if ($(this).data("relatedtab")) {
                    $(this).data("relatedtab").klearModule("highlightOff");
                }
            });



            $(".paginator a",panel).on('click',function(e) {
                e.preventDefault();
                e.stopPropagation();

                var targetPage = $(this).data("page");

                var _dispatchOptions = $(self).klearModule("option","dispatchOptions");

                if (!_dispatchOptions.post) _dispatchOptions.post = {};

                $.extend(_dispatchOptions.post,{
                    page : targetPage
                });

                $(self)
                    .klearModule("option","dispatchOptions",_dispatchOptions)
                    .klearModule("reDispatch");

            });

            // Orden de columnas
            $("th:not(.optionHeader)",panel).on("click",function(e) {
                e.preventDefault();
                e.stopPropagation();

                var targetOrder = $(this).data("field");
                var orderType = $("span.asc",$(this)).length>0? 'desc':'asc';

                var _dispatchOptions = $(self).klearModule("option","dispatchOptions");

                if (!_dispatchOptions.post) _dispatchOptions.post = {};


                $.extend(_dispatchOptions.post,{
                    order: targetOrder,
                    orderType: orderType,
                    page: 1
                });

                $(self)
                    .klearModule("option","dispatchOptions",_dispatchOptions)
                    .klearModule("reDispatch");
            }).css("cursor","pointer");

            $("th:not(.optionHeader) span.filter",panel).on("click",function(e) {
                e.stopPropagation();
                e.preventDefault();

            });

            $("span.mlTag",panel).on("click",function(e) {
                e.preventDefault();
                e.stopPropagation();
                var $td = $(this).parent("td");
                var shown = $("div.multilangValue:not(.selected)",$td).is(":visible");

                $("div.multilangValue:not(.selected)",$td).slideToggle();

                if (shown) {
                    $(".langIden",$td).animate({opacity:'0'});
                } else {
                    $(".langIden",$td).animate({opacity:'.5'});
                }

            }).on('mouseup',function(e) {
                // Paramos el evento mouseup, para no llegar al tr
                e.preventDefault();
                e.stopPropagation();
            });

            $(".klearMatrixFiltering span.addTerm",panel).on('click',function(e,noNewValue) {
                e.preventDefault();
                e.stopPropagation();

                var $holder = $(this).parents(".klearMatrixFiltering");
                var $_term = $("input.term",$holder);
                var $_field = $("select[name=searchFiled]",$holder);

                var _dispatchOptions = $(self).klearModule("option","dispatchOptions");
                var fieldName = $_field.val();

                _dispatchOptions.post = _dispatchOptions.post || {};
                _dispatchOptions.post.searchFields = _dispatchOptions.post.searchFields || {};
                _dispatchOptions.post.searchFields[fieldName] = _dispatchOptions.post.searchFields[fieldName] || [];


                if (noNewValue !== true) {
                    if ( (($_term.data('autocomplete')) && (!$_term.data('idItem')) ) ||
                            ($_term.val() == '') ) {

                            $(this).parents(".filterItem:eq(0)").effect("shake",{times: 3},60);
                            return;
                    }

                    $_term.attr("disabled","disabled");
                    $_field.attr("disabled","disabled");
                    var _newVal = ($_term.data('autocomplete'))? $_term.data('idItem') : $_term.val();
                    _dispatchOptions.post.searchFields[fieldName].push(_newVal);
                }

                _dispatchOptions.post.searchAddModifier = $("input[name=addFilters]:checked",panel).length;
                _dispatchOptions.post.page = 1;

                $(self)
                    .klearModule("option","dispatchOptions",_dispatchOptions)
                    .klearModule("reDispatch");


            });

            $(".klearMatrixFiltering input.term",panel).on('keydown',function(e) {
                if (e.keyCode == 13) {
                    // Wait for select event to happed (autocomplete)
                    var $target = $(this);
                    setTimeout(function() {
                        $("span.addTerm",$target.parents(".klearMatrixFiltering")).trigger("click");
                    },10);
                    e.preventDefault();
                    e.stopPropagation();
                }
            });

            $(".klearMatrixFiltering input[name=addFilters]",panel).on('change',function(e) {

                if ($(".klearMatrixFiltering .filteredFields .field",panel).length<=1) {
                    return;
                }
                $("span.addTerm",panel).trigger("click",true);
            });


            $(".klearMatrixFiltering .filteredFields",panel).on('click','.ui-silk-cancel',function(e) {

                var fieldName = $(this).parents("span.field:eq(0)").data("field");
                var idxToRemove = $(this).data("idx");
                var _dispatchOptions = $(self).klearModule("option","dispatchOptions");

                if (!_dispatchOptions.post.searchFields[fieldName]) {
                    return;
                }
                _dispatchOptions.post.searchFields[fieldName].splice(idxToRemove,1);
                _dispatchOptions.post.page = 1;

                $(self)
                    .klearModule("option","dispatchOptions",_dispatchOptions)
                    .klearModule("reDispatch");

            });

            $(".klearMatrixFiltering select[name=searchFiled]",panel).on('change',function(e) {

                var column = $.klearmatrix.template.helper.getColumn(_self.options.data.columns,$(this).val());
                var availableValues = {};
                var searchField = $(".klearMatrixFiltering input.term",panel);

                if ( (column)
                        && (availableValues = $.klearmatrix.template.helper.getValuesFromSelectColumn(column))
                        ){

                    var sourcedata = [];
                    $.each(availableValues,function(i,val) {
                        sourcedata.push({label:val,id:i});
                    })

                    searchField.autocomplete({
                        minLength: 0,
                        source: sourcedata,
                        select: function(event, ui) {
                            searchField.val( ui.item.label );
                            searchField.data('idItem',ui.item.id);
                            return false;
                        }
                    }).data( "autocomplete" )._renderItem = function( ul, item ) {

                        return $( "<li></li>" )
                            .data( "item.autocomplete", item )
                            .append( "<a>" + item.label + "</a>" )
                            .appendTo( ul );
                    };

                } else {
                    if (searchField.data('autocomplete')) {
                        searchField.autocomplete("destroy").data("idItem",null);
                    }
                }

            });

            $(".klearMatrixFilteringForm",panel).form();

            $(".klearMatrixFiltering .title",panel).on('click',function(e,i) {
                var $searchForm = $(this).parents("form:eq(0)");
                if ($searchForm.hasClass("not-loaded")) {
                    $(".filterItem",$searchForm).slideDown(function() {
                        $searchForm.removeClass("not-loaded");
                    });
                } else {
                    $(".filterItem",$searchForm).slideUp(function() {
                        $searchForm.addClass("not-loaded");
                    });

                }

            });
            return this;
        }
    });

    $.widget.bridge("klearMatrixList", $.klearmatrix.list);

})(jQuery);