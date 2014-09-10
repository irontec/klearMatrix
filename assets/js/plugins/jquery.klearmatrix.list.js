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
        _intervals: {},
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
                ._registerFieldsEvents()
                ._registerEvents();
        },
        destroy: function() {

            this._clearInterval();
            this._super.destroy.apply(this);
        },
        _clearInterval: function () {

            for (idx in this._intervals) {
                if (idx == $(this.element.klearModule("getPanel")).attr("id")) {
                    clearInterval(this._intervals[idx]);
                }  
            }
        },
        _applyDecorators : function() {

            var self = this.element;
            var _self = this;

            $container = $(this.element.klearModule("getPanel"));

            $(".generalOptionsToolbar .action, .generalOptionsToolbar a",$container).button();

            if ($("td.multilang",$container).length>0) {

                var $mlSelector = $("<span>").addClass("ui-silk ui-silk-comments mlTag").attr("title",$.translate("Field available in multi-language"));

                $("td.multilang",$container).each(function() {
                    $(this).prepend($mlSelector.clone().tooltip());
                });
            }

            $("caption span.extraCaptionInfo input",$container)
                .spinner()
                .on('keydown',function(e) {

                    if (e.keyCode == 13) {
                        $(this).trigger('fireit');
                    }
                })
                .on('fireit',function() {
                    var _count = parseInt($(this).val());
                    var _dispatchOptions = $(self).klearModule("option","dispatchOptions");
                    if (!_dispatchOptions.post) _dispatchOptions.post = {};

                    $.extend(_dispatchOptions.post,{
                        count : _count
                    });

                    $(self)
                        .klearModule("option","dispatchOptions",_dispatchOptions)
                        .klearModule("reDispatch");

                });


            return this;
        },
        _registerEvents : function() {
            var self = this.element;
            var _self = this;
            var panel = this.element.klearModule("getPanel");

            var tr = $('table.kMatrix tr', panel);

            $("td", tr).on('mouseenter mouseleave',function() {

                $("td:not(.options)", $(this).parent('tr')).toggleClass("ui-state-highlight");

                if (!$(this).is(".options") && $("a.option.default", $(this).parent('tr')).length>0) {
                    $(this).parent('tr').toggleClass("pointer");
                    $("a.option.default", $(this).parent('tr')).toggleClass("ui-state-active");
                }

            }).on('mouseup',function(e) {
                // Haciendo toda la tupla clickable para la default option
                e.stopPropagation();
                e.preventDefault();
                
                if ($(this).is(".options")) {
                    return;
                }
                
                if ($(this).hasClass("multiItem")) {
                    var $chkBox = $("input:checkbox",$(this));
                    $chkBox.prop('checked', !($chkBox.is(':checked')));
                    return;
                }
                // Modificador SHIFT + MultiItem => toggle checkbox
                if (e.shiftKey && $("td.multiItem",$(this).parent("tr")).length > 0) {
                    $("td.multiItem",$(this).parent("tr")).trigger("mouseup");
                    // Pequeño hack para deseleccionar el texto.
                    if ( document.selection ) {
                        document.selection.empty();
                    } else if ( window.getSelection ) {
                        window.getSelection().removeAllRanges();
                    }
                    return;
                }
                
                $.klear.checkNoFocusEvent(e, $(panel).parent(),$("a.option.default", $(this).parent('tr')));

                var $optionAnchor = $("a.option.default", $(this).parent('tr'));
                var event = ($optionAnchor.is(".screen"))? "mouseup":"click";
                $optionAnchor.trigger(event);

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

                var _dispatchOptions = $(self).klearModule("option", "dispatchOptions");

                if (!_dispatchOptions.post) _dispatchOptions.post = {};

                $.extend(_dispatchOptions.post,{
                    page : targetPage
                });

                $(self)
                    .klearModule("option","dispatchOptions",_dispatchOptions)
                    .klearModule("reDispatch");

            });
            
            // REFRESCAR EL LISTADO
            var myTime = $('.generalOptionsToolbar .refresh',panel).data("auto-refresh");          
            var iden = $(panel).attr("id");
            this._clearInterval();

            if(myTime){
                $('.generalOptionsToolbar .refresh',panel).hide();
                this._intervals[iden] = setInterval(function() {contadorRefresh()}, 1000);
            }

            function myInterval() {
                self._intervals(contInterval[iden]);
                $(self).klearModule("reDispatch");
            }

            function contadorRefresh() {

                var numTime = parseInt($('.generalOptionsToolbar .refresh:eq(0) .count',panel).html());
                var newTime = numTime - 1;

                if (numTime == 16) {
                    $('.generalOptionsToolbar .refresh',panel).fadeIn();
                }

                if (numTime == 1) {
                    clearInterval(self._intervals[iden]);
                    $(self).klearModule("reDispatch");
                    return;
                }

                $('.generalOptionsToolbar .refresh .count',panel).html(newTime);
            }
            
            // DETENER EL REFRESCO DEL LISTADO
            $('.generalOptionsToolbar .refresh',panel).on('click',function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // DETENER LA CUENTA REGRESIVA DE TIEMPO Y DEL CONTADOR
                clearInterval(self._intervals[iden]);
                
                $('.generalOptionsToolbar .refresh',panel).fadeOut();
                $('.generalOptionsToolbar .refresh .count',panel).text(myTime);

                self._intervals[iden] = setInterval(function() {contadorRefresh()}, 1000);
            });

            // Orden de columnas
            $("th:not(.notSortable)",panel).on("click",function(e) {
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
            
            $("th.multiItem", panel).on("mouseup",function(e) {
                e.stopPropagation();
                e.preventDefault();
                var $chkBox = $("input:checkbox",$(this));
                var targetValue = !($chkBox.is(':checked'));
                $(".multiItem input:checkbox", panel).prop('checked', targetValue);
                return;
            }).css("cursor","pointer");
            
            $(".multiItem input:checkbox", panel).on("click",function(e) {
                e.preventDefault();
            });
            
            $("th:not(.notSortable) span.filter",panel).on("click",function(e) {
                e.stopPropagation();
                e.preventDefault();
            });

            $("th.multiItem", panel).on('refreshButtons',function(e) {
                var numberOfChecks = $("td.multiItem input:checked", panel).length;
                var disabled = (numberOfChecks == 0);
                $("a._generalOption[data-multiitem]", panel).each(function() {
                    $(this).button("option", "disabled", disabled);
                    if (numberOfChecks > 0) {
                        $("sup",$(this)).html('('+numberOfChecks+')');
                    } else {
                        $("sup",$(this)).html('');
                    }
                });
                var headChecked = (numberOfChecks == $("td.multiItem input:checkbox", panel).length);
                $("th.multiItem input:checkbox", panel).prop('checked', headChecked);
            });
            
            $(".multiItem", panel).on('mouseup',function() {
                $("th.multiItem", panel).trigger('refreshButtons'); 
            });
            
            $("th.multiItem", panel).trigger('refreshButtons');
            
            
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


            var $applyFilters = $("input[name=applyFilters]",panel);

            $(".klearMatrixFiltering span.addTerm",panel).on('click',function(e,noNewValue) {
                e.preventDefault();
                e.stopPropagation();

                var $holder = $(this).parents(".klearMatrixFiltering");
                var $_term = $("input.term",$holder);
                var $_field = $("select[name=searchField]",$holder);

                var _dispatchOptions = $(self).klearModule("option","dispatchOptions");

                var fieldName = $_field.val();

                _dispatchOptions.post = _dispatchOptions.post || {};
                _dispatchOptions.post.searchFields = _dispatchOptions.post.searchFields || {};
                _dispatchOptions.post.searchOps = _dispatchOptions.post.searchOps || {};

                _dispatchOptions.post.searchFields[fieldName] = _dispatchOptions.post.searchFields[fieldName] || [];
                _dispatchOptions.post.searchOps[fieldName] = _dispatchOptions.post.searchOps[fieldName] || [];

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

                    var _searchOp = 'eq';
                    if ($("select[name=searchOption]",$holder).parent("span").is(":visible")) {
                        _searchOp = $("select[name=searchOption]",$holder).val();
                    }

                    _dispatchOptions.post.searchOps[fieldName].push(_searchOp);

                }

                _dispatchOptions.post.searchAddModifier = $("input[name=addFilters]:checked",panel).length;

                if ( $applyFilters.length > 0 ) {
                    _dispatchOptions.post.applySearchFilters = ( $applyFilters.is(':checked') ) ? 1 : 0;
                } else {
                    _dispatchOptions.post.applySearchFilters = 1;
                }
                _dispatchOptions.post.page = 1;

                $(self)
                    .klearModule("option","dispatchOptions",_dispatchOptions)
                    .klearModule("reDispatch");


            });

            $(".klearMatrixFiltering",panel).on('keydown','input.term',function(e) {

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

            $applyFilters.on('change',function(e) {
                e.stopPropagation();
                e.preventDefault();
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
                _dispatchOptions.post.searchOps[fieldName].splice(idxToRemove,1);
                _dispatchOptions.post.page = 1;

                $(self)
                    .klearModule("option","dispatchOptions",_dispatchOptions)
                    .klearModule("reDispatch");

            });

            $("button.preconfigureFilters", panel).button().on('click', function (e) {

                e.stopPropagation();
                e.preventDefault();
                var _dispatchOptions = $(self).klearModule("option","dispatchOptions");

                _dispatchOptions.post = _dispatchOptions.post || {};
                _dispatchOptions.post.searchFields = _dispatchOptions.post.searchFields || {};
                _dispatchOptions.post.searchOps = _dispatchOptions.post.searchOps || {};

                var fieldName = $(this).data("field");
                var _newVal = $(this).data("value");
                var _searchOp = 'eq';
                if ($(this).data("ops")) {
                    _searchOp = $(this).data("ops");
                }

                _dispatchOptions.post.searchFields[fieldName] = _dispatchOptions.post.searchFields[fieldName] || [];
                _dispatchOptions.post.searchOps[fieldName] = _dispatchOptions.post.searchOps[fieldName] || [];

                _dispatchOptions.post.searchFields[fieldName].push(_newVal);
                _dispatchOptions.post.searchOps[fieldName].push(_searchOp);

                _dispatchOptions.post.searchAddModifier = $("input[name=addFilters]:checked",panel).length;
                _dispatchOptions.post.page = 1;

                $(self)
                    .klearModule("option","dispatchOptions",_dispatchOptions)
                    .klearModule("reDispatch");

            });

            //Ocultamos botones preconfigurados que estén en activo en este momento
            $("p.filteredFields span.field",panel).each(function() {

                var $spanValues = $("span.content", $(this));
                var fieldName = $(this).data("field");

                $("button.preconfigureFilters", panel).filter(function() {

                    if (!$(this).data('field') ||
                            $(this).data('field') != fieldName ||
                                !$(this).data('value')) {
                        return false;
                    }

                    var candidateValue = $(this).data('value');

                    return $spanValues.filter(function() {
                        return (candidateValue == $(this).data("value"));
                    }).length > 0;

                }).button("disable");

            });

            $(".klearMatrixFilteringForm",panel).form();

            var currentPlugin = false;
            var originalSearchField = $(".klearMatrixFiltering input.term",panel).clone();

            $(".klearMatrixFiltering select[name=searchField]",panel).on('manualchange.searchValues',function(e, manual) {

                var column = $.klearmatrix.template.helper.getColumn(_self.options.data.columns, $(this).val());

                var availableValues = {};
                var $container = $(".klearMatrixFiltering",panel);
                var searchField = $("input.term",$container);
                var searchOption = $("span.searchOption",$container);

                if (false !== currentPlugin) {

                    searchField[currentPlugin]("destroy");
                    currentPlugin = false;
                }

                var _newField = originalSearchField.clone();
                searchField = searchField.replaceWith(_newField);
                searchField = _newField;

                column.search = column.search || {};

                //TODO: Determinar cuando mostrar el searchOption (=/</>) desde el controlador
                if (column.config && column.config['plugin'] && column.config['plugin'].match(/date|time/g)) {
                    column.search.options = true;
                }

                if (column.search.options) {
                    searchOption.show();
                } else {
                    searchOption.hide();
                }

                $container.find("span.infoShow").remove();
                $container.find("div.infoDiv").remove();

                if (column.search.info) {
                    $("<span />")
                        .attr("class","infoShow ui-silk ui-silk-help inline")
                        .prependTo($(".filterItem",$container))
                        .on('click', function(e){
                            if ($('div.infoDiv').is(':visible')) {
                                $('div.infoDiv').slideUp('fast');
                            } else {
                                $('div.infoDiv').slideDown('fast');
                            }
                        });

                    $("<div>"+column.search.info+"</div>")
                        .attr("class","infoDiv hidden")
                        .prependTo($(".filterItem",$container));
                }

                switch(true) {
                    // un select!
                    case  (column.type == 'select'):
                    case  (column.type == 'multiselect'):
                        var _availableValues = $.klearmatrix.template.helper.getValuesFromSelectColumn(column);

                        var sourcedata = [];
                        $.each(_availableValues,function(i,val) {
                            sourcedata.push({label:val,id:i});
                        });

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
                        currentPlugin = 'autocomplete';

                        break;

                    // TODO, hacer que esta configuración venga de serie en column.search
                    case (column.config && typeof column.config['plugin'] == 'string'):
                        var _pluginName = column.config['plugin'];
                        currentPlugin = _pluginName;
                        var _settings = column.config['settings'] || {};
                           searchField[_pluginName](_settings);
                           break;

                    case (column.search.plugin && typeof column.search.plugin == 'string'):
                        var _pluginName = column.config['plugin'];
                        currentPlugin = column.search.plugin;
                        var _settings = column.search.settings || {};
                           searchField[column.search.plugin](_settings);
                        break;
                    default:

                    break;
                }

                if (manual !== true) {
                    searchField.focus();
                }

            }).trigger('manualchange.searchValues', true);

            var $filteredFields = $(".klearMatrixFiltering .filteredFields",panel);
            $(".klearMatrixFiltering .title",panel).on('click',function(e,i) {
                var $searchForm = $(this).parents("form:eq(0)");
                var target = ".filterItem";

                if ( $applyFilters.is(':checked') == false
                    && $filteredFields.find('.field').length > 0 ) {

                    target = ".filteredFields";

                }

                if ($searchForm.hasClass("not-loaded")) {
                    $(target,$searchForm).slideDown(function() {
                        $searchForm.removeClass("not-loaded");
                    });
                } else {
                    $(target,$searchForm).slideUp(function() {
                        $searchForm.addClass("not-loaded");
                    });
                }
            });

            if ( $applyFilters.is(':checked') == false
                    && $filteredFields.find('.field').length > 0 ) {

                $filteredFields.css('opacity','0.5');
                $filteredFields.unbind('click');
                $('.preconfiguredFilters, .filterItem', panel).hide();

                $(".klearMatrixFilteringForm:eq(0)",panel).addClass('not-loaded');

            }

            //Exportar a CSV el listado
            $("a.option.csv", panel).on('click', function(event) {
                event.preventDefault();
                event.stopPropagation();

                var _dispatchOptions = $(self).klearModule("option","dispatchOptions");

                if (!_dispatchOptions.post) {

                    _dispatchOptions.post = {};
                }

                var _tmpOptions = {};
                $.extend(_tmpOptions, _dispatchOptions.post);
                _tmpOptions['format'] = 'csv';


                var reqOpts = {
                        file: $(self).klearModule("option","file"),
                        type: 'screen',
                        screen: _self.options.data.screen,
                        post: _tmpOptions,
                        external: true
                    };


                if (_dispatchOptions.pk!=undefined) {
                    reqOpts.pk = _dispatchOptions.pk;
                    reqOpts.screen = _dispatchOptions.screen;
                }

                $.klear.request(reqOpts);
            });

            //Autocompletes
            var autocompleteNodes = {};
            var autocompleteEntities = {};

            $("span.autocomplete", panel).each(function () {

                var identifier = $(self).klearModule("option","file") + "_" + $(this).data("command");
                if (! autocompleteEntities[identifier] ) {

                    var _post = $(this).data();
                    _post.value = new Array;

                    var requestData = {
                            file: $(self).klearModule("option","file"),
                            type : 'command',
                            post: _post
                    };

                    if (! $(this).data('command')) {
                        $.console.log("ATENCIÓN: El atributo command no está configurado. Se utiliza el valor por defecto: " + $(this).data('fielddecorator') + "_command"); 
                        requestData['command'] = $(this).data('fielddecorator') + "_command";
                    }

                    autocompleteEntities[identifier] = requestData;
                    autocompleteNodes[identifier] = new Array;
                }

                autocompleteEntities[identifier].post.value.push($(this).attr("data-value"));
                autocompleteNodes[identifier].push(this);
            });

            $.each(autocompleteEntities, function (idx) {

                var request = $.klear.buildRequest(this);
                var nodes = autocompleteNodes;

                var _url = request.action;
                _url += '&' + $.param(request.data);
                $.getJSON( _url , function( data, status, xhr ) {
                    $.each(nodes[idx], function () {

                        var currentNode = $(this);
                        var currentNodeData = data.results["'"+currentNode.attr("data-value")+"'"];
                        if (!currentNodeData) {
                            return;
                        }

                        var results = new Array();
                        if (Object.prototype.toString.call( currentNodeData ) === '[object Array]') {
                            for (var key in currentNodeData) {
                                results.push(currentNodeData[key].label);
                            }
                        } else {
                            results.push(currentNodeData.label);
                        }
                        $(this).replaceWith(results.join(", "));
                    });
                });
            });

            return this;
        }
    });

    $.widget.bridge("klearMatrixList", $.klearmatrix.list);

})(jQuery);
