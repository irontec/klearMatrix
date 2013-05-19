(function ($) {

    $.klearmatrix = $.klearmatrix || {};
    $.klearmatrix.template  = $.klearmatrix.template || {};

    var __namespace__ = "klearmatrix.templatehelper";

    $.klearmatrix.template.helper = {

        debug : function() {
            console.log(arguments);
            return '';
        },

        cleanValue : function(_value, ifNull, pattern) {

            if (typeof ifNull == 'undefined') {

                ifNull = '';
            }

            if (pattern && !ifNull.match(pattern)) {

                ifNull = '';
            }

            /**
             * Exact comparisons so 0's are correctly displayed
             */
            if(typeof _value == 'undefined' || _value === false || _value === '') {

                return ifNull;
            }

            return $('<div/>').text(_value).html();
        },

        getEditDataForField : function(value, column, isNew) {

            var extraConfig = column.config || false;
            var properties = column.properties || false;
            var customErrors = column.errors || false;
            var _value = '';

            if (true !== isNew) {

                if (typeof value != 'object' && !column.dirty) {

                    var pattern = column.properties && column.properties.pattern || '';
                    _value = this.cleanValue(value,'', pattern);

                } else {

                    _value = value;
                }

            } else {
                if (column.properties && column.properties.defaultValue) {

                    _value = column.properties.defaultValue;
                }
            }

            // Tengo prisa...
            if (column.disabledOptions) {

                if (column.disabledOptions['valuesCondition']) {
                    if (column.disabledOptions['valuesCondition'] == 'null') {
                        column.disabledOptions['valuesCondition'] = null;
                    }

                    if (_value == column.disabledOptions['valuesCondition']) {
                        return column.disabledOptions['label'];
                    }
                }
            }

            if ( (this.data) && (this.data.parentItem == column.id) ) {
                column.readonly = true;
                _value = this.data.parentId;
            }

            var randIden = (this.data && this.data.randIden)? this.data.randIden:Math.round(Math.random(10000)*10000);

            var fieldData = {
                    _elemIden: column.id + randIden,
                    _elemName: column.id,
                    _readonly: column.readonly? true:false,
                    _dataConfig : extraConfig,
                    _decorators: column.decorators,
                    _properties : properties,
                    _fieldValue : _value,
                    _errors : customErrors
            };

            var node = $("<div />");

            var _templateHelpers = {
                dataParser: function (attribute, value) {

                    attribute = attribute.charAt(0).toLowerCase() + attribute.substr(1).replace(/[A-Z]/g,function(s) {
                        return "-"+s.toLowerCase();
                    });
                    return  attribute;
                }
            };

            if (column.multilang) {
                
                var _mlList = $("<dl />");
                _mlList.addClass("multiLanguage");
 
                for (var i in this.data.langs) {

                    var lang = this.data.langs[i];
                    var _curValue = isNew? '': this.cleanValue(fieldData._fieldValue[lang] );

                    var _curFieldData = {
                        _elemIden: column.id + lang + randIden,
                        _elemName : column.id + lang,
                        _readonly: column.readonly? true:false,
                        _dataConfig : extraConfig,
                        _properties : properties,
                        _fieldValue: _curValue,
                        _errors : customErrors,
                        _multilang : true,
                        _locale: this.data.langDefinitions[lang]['locale']
                    };

                    var _node = $("<div />");
                    
                    $.tmpl(this.getTemplateNameForType(column.type), _curFieldData, _templateHelpers).appendTo(_node);

                    var _data = {
                        _iden: _curFieldData._elemIden,
                        _lang : lang,
                        _field : _node.html(),
                        _class : (lang == $.klear.language)? 'selected':''
                    };

                    
                    if (lang == $.klear.language) {
                    	$.tmpl('klearmatrixMultiLangField', _data).prependTo(_mlList);
                    } else {
                    	$.tmpl('klearmatrixMultiLangField', _data).appendTo(_mlList);
                    }
                }
                
                
                _mlList.appendTo(node);

            } else {

                $.tmpl(this.getTemplateNameForType(column.type), fieldData, _templateHelpers).appendTo(node);
            }

            return node.html();

        },

        getColumnName : function(columns, columnId) {

            for(var idx in columns) {
                if (columns[idx].id == columnId) {
                    return columns[idx].name;
                }
            }
            return false;

        },

        getColumn : function(columns, columnId) {

            for (var idx in columns) {
                if (columns[idx].id == columnId) {
                    return columns[idx];
                }
            }

            return false;
        },

        getIndex : function(values,idx) {
            if (!values[idx]) return 'error';
            return values[idx];
        },

        parseOptionCustomization : function(customOptions,config) {

            var response =  '';
            if (!customOptions) {

                return response;
            }

            if (typeof(customOptions[config[config.type]]) == "undefined") {

                return response;
            }

            response = $("<" + customOptions[config[config.type]].wrapper  + "/>");
            response.addClass(customOptions[config[config.type]]['class']);
            response.html(customOptions[config[config.type]].result);

            return response.wrap("<div></div>").parent().html();
        },
        
        parseParentOptionCustomizationOpen : function(customOptions,config) {
            var response =  '';
            if (!customOptions) {

                return response;
            }

            if (typeof(customOptions[config[config.type]]) == "undefined") {

                return response;
            }

            response = ("<" + customOptions[config[config.type]].parentWrapper  + " class='" + customOptions[config[config.type]]['parentClass'] +"'>");
            return response;
        },
        
        parseParentOptionCustomizationClose : function(customOptions,config) {

            var response =  '';
            if (!customOptions) {

                return response;
            }

            if (typeof(customOptions[config[config.type]]) == "undefined") {

                return response;
            }

            response = ("</" + customOptions[config[config.type]].parentWrapper  + " >");
            return response;
        },

        getMultiLangValue : function(value,langs,defaultLang) {
            var retItem = $("<div />");
            for (var i in langs) {
                var mlData = {
                        _lang : langs[i],
                        _value : this.cleanValue(value[langs[i]]),
                        _default : (langs[i] == defaultLang)
                };
                _compiled = $.tmpl('klearmatrixMultiLangList',mlData);
                retItem.append(_compiled);
            }

            return retItem.html();
        },

        getValuesFromSelectColumn : function(column, idx) {

            switch (column.type){
                case 'select':

                    var ret = {};
                    for (var index in column.config.values) {
                        ret[column.config.values[index].key] = column.config.values[index].item;
                    }

                    if ( (typeof idx != undefined) && column.decorators && column.decorators.autocomplete) {

                        var resp = $('<span class="autocomplete" />').attr({
                                        "data-value": idx,
                                        "data-reverse" : "true",
                                        "data-fielddecorator" : "autocomplete",
                                        "data-field": "select"
                                   });

                        for (var decoratorName in column.decorators) {

                            for (var prop in column.decorators[decoratorName]) {

                                resp.attr("data-" + prop, column.decorators[decoratorName][prop]);
                            }
                        }

                        return $("<p>").append(resp).html();

                    } else if ( (typeof idx != 'undefined') && typeof ret[idx] != 'undefined') {

                        return ret[idx];

                    } else {

                        return ret;
                    }

                break;
                case 'multiselect':
                    if (column.config.values['__className']) {
                        delete column.config.values['__className'];
                    }
                    var ret = {};
                    for (var index in column.config.values) {
                        ret[column.config.values[index].key] = column.config.values[index].item;

                        if ( (typeof idx != 'undefined') && column.config.values[index].key == idx) {
                        	return column.config.values[index].item;
                        }
                    }
                    // Multiselect bugs ahead!!
                    if ( (typeof idx != 'undefined') && column.config.values[idx] ){
                        return ret[idx];
                    } else {
                        return ret;
                    }

                break;
            }
            if (typeof idx != 'undefined') {
                return this.cleanValue(idx);
            } else {
                return false;
            }
        },

        getIndexFromColumn : function(values, column) {

            if (typeof values[column.id] == 'undefined') {

                return '';

            } else {

                switch(column.type){

                    case 'select':

                        var _curVal = values[column.id];

                        if (_curVal && column.decorators) {

                           return this.getValuesFromSelectColumn(column, _curVal);

                        } else if (this.getValuesFromSelectColumn(column)[_curVal]) {

                            return this.getValuesFromSelectColumn(column)[_curVal];
                        }
                        
                        if (_curVal == null &&  this.getValuesFromSelectColumn(column)['__null__']) {
                        	return this.getValuesFromSelectColumn(column)['__null__'];                        	
                        }

                        return '';
                        break;

                    case 'multiselect':

                        var fixedValues = this.getValuesFromSelectColumn(column);
                        var returnValue = [];

                        for(var i in values[column.id]['relStruct']) {

                            var relId = values[column.id]['relStruct'][i]['relatedId'];

                            if (fixedValues[relId]) {

                                returnValue.push(fixedValues[relId]);
                            }
                        }

                        if (returnValue.length == 0) {

                            return '<em>' + $.translate('There are not associated elements', [__namespace__]) + '</em>';
                        }

                        return returnValue.join(', ');
                        break;

                    case 'file':
                        var extra = '';
                        if (column.config && column.config.options) {
                            for(var _optIdx in column.config.options) {

                                if (column.config.options[_optIdx]['listController']) {

                                    var option = column.config.options[_optIdx];
                                    extra += '<a data-filename="'+values[column.id]['name']+'" href="#" ';
                                    extra += ' class="option ';
                                    if (option['class']) {
                                        extra += option['class'] + ' ' ;
                                    }
                                    if (option['type']) {
                                        extra += option['type'];
                                    }
                                    extra += '" ';

                                    if (option['type']) {
                                        extra += ' data-'+option['type']+'="'+option['target']+'" ';
                                    }

                                    if (option.props) {
                                        for (var propName in option.props) {
                                            var prop = option.props[propName];
                                            extra += 'data-'+propName+'="'+prop+'" ';
                                        }
                                    }
                                    if (option.external) {
                                        extra += 'data-external="true"';
                                    }
                                    extra += '>';
                                    if (option.icon) {
                                        extra += '<span class="ui-silk inline '+option.icon+'"></span>';
                                    }
                                    extra += '</a>';
                                }

                            }

                        }

                        if (column.config.options.hiddenName) {
                            return extra;
                        }

                        return extra + this.cleanValue(values[column.id]['name']);
                        break;

                    case 'checkbox':
                        var icon = 'closethick';

                        if (values[column.id] == 1) {
                            icon = 'check';
                        }

                        return '<span class="ui-icon ui-icon-' + icon + '" />';
                        break;

                    case 'video':

                        return this.cleanValue(values[column.id]['title']);
                        break;

                    case 'map':


                        return this.cleanValue(values[column.id]['address']);
                        break;

                    default:

                        if(column.properties && column.properties.maxLength) {

                            if (typeof(values[column.id]) == "string") {

                                if (values[column.id].length > column.properties.maxLength) {

                                    values[column.id] = values[column.id].substring(0,column.properties.maxLength);
                                    values[column.id] += '...';
                                }

                            } else {

                                for (idx in values[column.id]) {

                                    if (values[column.id][idx].length > column.properties.maxLength) {

                                        values[column.id][idx] = values[column.id][idx].substring(0,column.properties.maxLength);
                                        values[column.id][idx] += '...';
                                    }
                                }
                            }
                        }

                        if (column.multilang) {

                            return this.getMultiLangValue(values[column.id],this.data.langs,$.klear.language);
                        }

                        if (column.dirty) {

                            return values[column.id];
                        }

                        return this.cleanValue(values[column.id]);
                        break;
                }
            }
        },

        getTemplateNameForType : function(type) {
            return 'klearMatrixFields' + type;
        },

        getTemplateForType : function(type) {
            return $.template[this.getTemplateNameForType(type)];
        },

        getPaginatorTemplate : function() {
            return $.template['klearmatrixPaginator'];
        },

        _parseDefaultValues : function(settings) {

            var title = settings.title;

            if (typeof settings.title != 'string' || !title.match(/\%item\%|%parent%/)) {
                return title;
            }

            var replaceParentPerItem = settings.replaceParentPerItem;
            var defaultLang = settings.defaultLang;
            var parentIden = settings.parentIden;
            var columns = settings.columns;
            var idx = settings.idx;
            var values = settings.values;

            if ($.isArray(values) && (values.length > 0) && (values[idx])) {

                values = values[idx];

                var count = false;
                var defaultColumns = [];

                for(var i in columns) {
                    if (count === false) {
                        var _firstValue = columns[i];
                        count = true;
                    }

                    if (columns[i]['default'] ) {
                        defaultColumns.push(columns[i]);
                    }
                }

                if (defaultColumns.length == 0) {
                    defaultColumns.push(_firstValue);
                }

                var defaultValues = [];
                for(var i in defaultColumns) {
                    var defaultColumn = defaultColumns[i];

                    if (defaultColumn.multilang) {
                        defaultValues.push(values[defaultColumn.id][defaultLang]);
                    } else {
                        defaultValues.push(values[defaultColumn.id]);
                    }
                }

                var defaultValue = defaultValues.join(' ');

            } else {
                var defaultValue = '';
            }

            // Si el método es invocado con replaceParentPerItem, éste viene de un listado
            // Las opciones cogen el title|label de su destino; en este caso, el parent será el item
            var parentValue = (replaceParentPerItem)?
                                    this.cleanValue(defaultValue) :
                                    this.cleanValue(parentIden);

            var _r = title
                    .replace(/\%parent\%/,parentValue)
                    .replace(/\%item\%/,this.cleanValue(defaultValue));

            return _r;

        },
        getTitle : function(title,idx,replaceParentPerItem) {
            return this._parseDefaultValues({
                    title: title,
                    replaceParentPerItem : replaceParentPerItem,
                    defaultLang : this.data.defaultLang,
                    parentIden: this.data.parentIden,
                    columns: this.data.columns,
                    values: this.data.values,
                    idx: idx
            });
        },
        getExternalData : function(externalData) {
        	var _allowed = ['file','noiden','searchby','removescreen','title'];
        	var _prefix = 'external';
        	var _ret = '';
        	for(var i=0;i<_allowed.length;i++) {
        		if (externalData[_prefix+_allowed[i]]) {
        			_ret += ' data-' + _prefix+_allowed[i]+ '=' +externalData[_prefix+_allowed[i]]+''; 
        		}
        	}
        	return _ret;
        },

        mustShowOptionColum : function(option, value) {

            return true;
        }
    };

})(jQuery);