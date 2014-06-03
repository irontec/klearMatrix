;(function ($) {

    $.klearmatrix = $.klearmatrix || {};
    $.klearmatrix.template  = $.klearmatrix.template || {};

    var __namespace__ = "klearmatrix.templatehelper";

    $.klearmatrix.template.helper = {

        debug : function() {
            console.log(arguments);
            return '';
        },
        /**
         * Exact comparisons so 0's are correctly displayed
         */
        _checkNull : function (value) {
            return value === null || typeof value == 'undefined' || value === false || value === '';
        },
        cleanValue : function(_value, ifNull, pattern) {

            if (typeof ifNull == 'undefined') {
                ifNull = '';
            }

            if (pattern && !ifNull.match(pattern)) {
                ifNull = '';
            }

            if (this._checkNull(_value)) {
                return ifNull;
            }

            return $('<div/>').text(_value).html();
        },
        _formatSizeUnits : function(bytes) {
            var sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            if (bytes == 0) return '<span class="zero">0 B</span>';
            var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
            return Math.round(bytes / Math.pow(1024, i), 2) + sizes[i];
        },
        getSizeForField : function(value, column) {
            if (!column.properties || column.properties && !column.properties.showSize) {
                return '';
            }

            var _ret = '<div class="size-indicator">';
            if (column.multilang) {
                var first = true;
                for (var i in this.data.langs) {
                    var lang = this.data.langs[i];
                    if (typeof value[lang] == 'string') {
                        if (!first) {
                            _ret += ' / ';
                        }
                        _ret += lang + ': ' + this._formatSizeUnits(unescape(encodeURIComponent(value[lang])).length);
                    }
                    if (first) {
                        first = false;
                    }
                }
            } else {
               _ret += this._formatSizeUnits(unescape(encodeURIComponent(value)).length);
            }
            _ret += '</div>';
            return _ret;
        },
        getEditDataForField : function(value, column, isNew) {

            var extraConfig = column.config || false;
            var properties = column.properties || false;
            var customErrors = column.errors || false;
            var _value = '';

            // valor que devuelve el método si el valor es NULL
            var ifNullValue = '';

            if (true !== isNew) {

                if (typeof value != 'object' && !column.dirty) {

                    var pattern = column.properties && column.properties.pattern || '';
                    _value = this.cleanValue(value,ifNullValue, pattern);

                } else {

                    if (this._checkNull(value)) {
                        _value = ifNullValue;
                    } else {
                        _value = value;
                    }
                }

            } else {
                if (column.properties && column.properties.defaultValue !== null) {
                    _value = column.properties.defaultValue;
                }
            }

            // Tengo prisa...
            if (column.disabledOptions) {
                if (column.disabledOptions['valuesCondition']) {
                    if (column.disabledOptions['valuesCondition'] == 'null') {
                        column.disabledOptions['valuesCondition'] = null;
                    }
                    if (value == column.disabledOptions['valuesCondition']) {
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
                    _elemBaseName: column.id,
                    _readonly: column.readonly? true:false,
                    _dataConfig : extraConfig,
                    _decorators: column.decorators,
                    _properties : properties,
                    _fieldValue : _value,
                    _errors : customErrors
            };

            var node = $("<div />");
            var self = this;
            var _templateHelpers = {
                dataParser: function (attribute, value) {
                    attribute = attribute.charAt(0).toLowerCase() + attribute.substr(1).replace(/[A-Z]/g,function(s) {
                        return "-"+s.toLowerCase();
                    });
                    return  attribute;
                },
                drawSelected : function(attribute, value) {
                    var text = $("<div>" + value +"</div>").text();
                    if (attribute == text) {
                        return 'selected="selected"';
                    }
                    return '';
                },
                formatSizeForFile : function(file) {
                    if (file.size) {
                        return self._formatSizeUnits(parseInt(file.size));
                    }
                    return "";
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
                        _elemBaseName: column.id,
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
        isMultiItemSelectableList : function(generalOptions) {
            for (var i in generalOptions) {
                if (generalOptions[i].multiItem) {
                    return true;
                }
            }
            return false;
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
            if (!values[idx]) {
                return 'error';
            }
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
                    if ((typeof idx != 'undefined') && (column.decorators && column.decorators.autocomplete)) {

                        var dataValues = new Array();
                        for (var indice in idx['relIndex']) {
                            var _structIdx = idx['relIndex'][indice];
                            dataValues.push(idx['relStruct'][_structIdx]['relatedId']);
                        }

                        var resp = $('<span class="autocomplete" />').attr({
                            "data-value": dataValues.join(","),
                            "data-reverse" : "true",
                            "data-fielddecorator" : "autocomplete",
                            "data-field": "multiselect"
                        });

                        for (var decoratorName in column.decorators) {
                            for (var prop in column.decorators[decoratorName]) {
                                resp.attr("data-" + prop, column.decorators[decoratorName][prop]);
                            }
                        }

                        return $("<p>").append(resp).html();

                    } else if ( (typeof idx != 'undefined') && column.config.values[idx] ){
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
        _getProperty : function(column, property) {
            if (!column.properties || !column.properties[property]) {
                return '';
            }

            return column.properties[property];
        },
        getPrefix : function(column) {
            return this._getProperty(column, 'prefix');
        },
        getSufix : function(column) {
            return this._getProperty(column, 'sufix');
        },
        getStringIndexFromColumn : function(row,column) {
            var ret = this.getIndexFromColumn(row, column);
            var $ret = $("<div />").html(ret);
            if ($("div.multilangValue", $ret).length > 0) {
                var $elem = $("div.multilangValue.selected", $ret);
                $("span",$elem).remove();
                return $elem.text();
            }

            return ret;
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

                        if (column.decorators) {
                            var fixedValues = this.getValuesFromSelectColumn(column, values[column.id]);
                            return fixedValues;
                        }

                        var fixedValues = this.getValuesFromSelectColumn(column);
                        var returnValue = [];

                        for(var i in values[column.id]['relStruct']) {

                            var relId = values[column.id]['relStruct'][i]['relatedId'];

                            if (fixedValues[relId]) {
                                returnValue.push(fixedValues[relId]);
                            }
                        }

                        if (returnValue.length == 0) {
                            return '<em>' + $.translate("There are not associated elements") + '</em>';
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

                        var imgUrl = 'http://maps.googleapis.com/maps/api/staticmap?'
                            + 'center=%lat%,%lng%&zoom=%zoom%'
                            + '&size=%width%x%height%&sensor=false';

                        var markerUrl = '&markers=color:red%7C%lat%,%lng%';

                        var printMarker = true;

                        var lat = values[column.id]['lat'];
                        var lng = values[column.id]['lng'];

                        if ( lat == null || lng == null ) {
                            lat = '0.0';
                            lng = '0.0';
                            imgUrl = imgUrl.replace(/%zoom%/g, '1');
                            printMarker = false;
                        } else {
                            imgUrl = imgUrl.replace(/%zoom%/g, values[column.id]['previewZoom']);
                        }

                        imgUrl = imgUrl.replace(/%lat%/g, lat);
                        imgUrl = imgUrl.replace(/%lng%/g, lng);
                        imgUrl = imgUrl.replace(/%width%/g, values[column.id]['previewWidth']);
                        imgUrl = imgUrl.replace(/%height%/g, values[column.id]['previewHeight']);

                        if ( printMarker ) {
                            markerUrl = markerUrl.replace(/%lat%/g, lat);
                            markerUrl = markerUrl.replace(/%lng%/g, lng);
                            imgUrl = imgUrl + markerUrl;
                        }

                        return '<img src="'+imgUrl+'" class="makeItBigger" />';
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

                        var ifNullValue = '';

                        if (column.dirty) {
                            if (this._checkNull(values[column.id])) {
                                return ifNullValue;
                            }
                            return values[column.id];
                        }

                        return this.cleanValue(values[column.id], ifNullValue);
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

        getOptionTemplateName : function() {
            return 'klearmatrixOption';
        },

        _parseDefaultValues : function(settings) {

            var title = settings.title;

            if (typeof settings.title != 'string' || !title.match(/\%item\%|%parent%/)) {
                return title;
            }

            var replaceParentWithItem = settings.replaceParentWithItem;
            var defaultLang = settings.defaultLang;
            var parentIden = settings.parentIden;
            var columns = settings.columns;
            var idx = settings.idx;
            var values = settings.values;
            var defaultValue = '',
                _firstValue = '';

            if ($.isArray(values) && (values.length > 0) && (values[idx])) {

                values = values[idx];

                var count = false;
                var defaultColumns = [];

                for(var i in columns) {
                    if (count === false) {
                        _firstValue = columns[i];
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

                defaultValue = defaultValues.join(' ');

            } else {
                defaultValue = '';
            }

            // Si el método es invocado con replaceParentPerItem, éste viene de un listado
            // Las opciones cogen el title|label de su destino; en este caso, el parent será el item
            // siempre que no esé definido parentIden (el listado sea a su vez hijo de otro)

            defaultValue = this.cleanValue(defaultValue);
            var parentValue = (replaceParentWithItem)?
                                    this.cleanValue(defaultValue) :
                                    this.cleanValue(parentIden);
            return this.buildTitleString(title, defaultValue, parentValue);

        },
        buildTitleString: function(title, defaultValue, parentValue) {
            defaultValue = defaultValue || "";
            parentValue = parentValue || "";
            var res = [];
            var _r = "";
            if (res = title.match(/\[format\|(.*[\%parent\%|\%item\%].*)\]/)) {
                if (res.length == 2) {
                    if (res[1].match(/\%parent%/)) {
                        if (parentValue.trim()!="") {
                            _r = title.replace(res[0], res[1].replace(/\%parent\%/,parentValue));
                        } else {
                            _r = title.replace(res[0], '');
                        }
                    }
                    if (res[1].match(/\%item%/)) {
                        if (defaultValue.trim()!="") {
                            _r = title.replace(res[0], res[1].replace(/\%item\%/,defaultValue));
                        } else {
                            _r = title.replace(res[0], '');
                        }
                    }
                }
            } else {
                _r = title
                   .replace(/\%parent\%/, parentValue)
                   .replace(/\%item\%/, defaultValue);
            }
            return _r;
        },
        getTitle : function(title,idx,replaceParentWithItem) {

            return this._parseDefaultValues({
                    title: title,
                    replaceParentWithItem : replaceParentWithItem,
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
            var _ret = {
                    'attributes' : [],
                    'values' : []
            };
            
            for(var i=0;i<_allowed.length;i++) {
                if (externalData[_prefix+_allowed[i]]) {
                    console.log(externalData);
                    _ret['attributes'].push(_prefix+_allowed[i]);
                    _ret['values'].push(externalData[_prefix+_allowed[i]]);
                }
            }
            return _ret;
        },
        option2HTML : function(option, from, idx, fieldValue)
        {
            var mainTitle = '',
                buttonLabel = false,
                classes = [],
                entity = false;

            if (idx !== false && (option.showOnlyOnNotNull || option.showOnlyOnNull)) {

                if (fieldValue && option.showOnlyOnNull) {
                    return '';
                }

                if (!fieldValue && option.showOnlyOnNotNull) {
                    return '';
                }
            }

            var _node = $("<div />");
            var mustShowLabel = option.label? true:false;

            // Es una entidad concreta (con índice "idx" en data.values
            if (false !== idx && this.data && this.data.values) {
                entity = this.data.values[idx];
            }

            var externalData = false;
            
            if (option.externalOption && entity && fieldValue) {
                
                externalData = this.getExternalData(option);
                externalData['attributes'].push('externalid');
                externalData['values'].push(fieldValue);
                
            } else {
                
                //TODO: Sin probar. Probablemente venga de una opción de un field a un screen external.
                if (option.external && option.file) {
                    externalData['attributes'] = ['externalfile'];
                    externalData['values'] = [option.file];
                }
                
            }
            switch(from) {

                case "List":
                    if (idx === false) {
                        classes.push('_generalOption');
                        mainTitle = this.getTitle(option.title, false, false);

                        if (mustShowLabel && typeof option.label == 'string') {
                            buttonLabel = this.getTitle(option.label, false,false);
                        }

                        if (option.labelOnList) {
                            if (typeof option.labelOnList == 'string') {
                                buttonLabel = this.getTitle(option.labelOnList, false);
                            }
                            mustShowLabel = true;
                        }

                    } else {

                        option.multiItem = false;
                        classes.push('_fieldOption inherit ui-state-nojump');
                        
                        mainTitle = this.getTitle(option.title, idx, option.labelReplaceParentWithItem);

                        if (mustShowLabel && typeof option.label == 'string') {
                            buttonLabel = this.getTitle(option.label, idx, option.labelReplaceParentWithItem);
                        }
                    }

                break;
                case "Edit":

                    mainTitle = this.getTitle(option.title, idx, true);

                    if (mustShowLabel && typeof option.label == 'string') {
                        buttonLabel = this.getTitle(option.label, idx, true);
                    }

                    if (option.labelOnEdit) {
                        if (typeof option.labelOnEdit == 'string') {
                            buttonLabel = this.getTitle(option.labelOnEdit, idx, true);
                        }
                        mustShowLabel = true;
                    }

                    option.multiItem = false;
                    if (fieldValue) {
                        classes.push('_fieldOption inherit ui-state-nojump');
                    } else {
                        classes.push('_generalOption');
                    }
                    break;
                case "Field":
                    // TODO: cargar this con valores necesarios, en casa de querer invocar getTitle
                    mainTitle = option.title;
                    // Forzamos multiItem a false
                    option.multiItem = false;

                    if (mustShowLabel && typeof option.label == 'string') {
                        buttonLabel = option.label;
                    }

                    if (option.from && option.from == "postActionOptions") {

                        if (option.labelOnPostAction) {
                            if (typeof option.labelOnPostAction == 'string') {
                                buttonLabel = option.labelOnPostAction;
                            }
                            mustShowLabel = true;
                        }
                    }

                    classes.push('_fieldOption inherit ui-state-nojump');
                    break;
            }

            if (false === buttonLabel) {
                buttonLabel = mainTitle;
            }

            if (option.defaultOption) {
                classes.push('default');
            }
            var optionData = {
                    classes : classes.join(' '),
                    type : option.type,
                    icon: option.icon,
                    optionIndex : this.getIndex(option, option.type),
                    optionTitle : this.buildTitleString(mainTitle),
                    shortcut: option.shortcut  || false,
                    multiInstance: option.multiInstance || false,
                    external: option.external || false,
                    externalData : externalData,
                    disabledTime : option.disabledTime || false,
                    multiItem: option.multiItem || false,
                    mustShowLabel: mustShowLabel,
                    parentHolderSelector: option.parentHolderSelector || false,
                    buttonLabel: this.buildTitleString(buttonLabel)
            };

            $.tmpl(this.getOptionTemplateName(), optionData).appendTo(_node);
            return _node.html();
        },
        mustShowOptionColum : function(option, value) {
            return true;
        }
    };

})(jQuery);