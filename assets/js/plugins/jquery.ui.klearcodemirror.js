;(function load($, undefined) {
    if (!$.klear.checkDeps(['CodeMirror'], load)) {
        return;
    }
    
    var plugins = [];
    

    $.widget('ui.klearcodemirror', {
        options: {
        },
        _init : function(){
            var $el = $(this.element);
            //Devuelve un -1 si no está en el array y un 0 si está
            if( $.inArray("clike", plugins) == -1){
                plugins.push("clike");
                $.getScript("../klearMatrix/js/plugins/codemirror/mode/clike/clike.js",function() {
                	checkMatchBrackets($el)
                });
            }
            else checkMatchBrackets($el);
            
        }
    });
    
    function setCodeMirror($el){
        var cm = CodeMirror.fromTextArea($el.get(0), {
            lineNumbers: $el.attr("data-setting-line-numbers") == "true",
            mode: $el.attr("data-setting-mode"),
            theme: $el.attr("data-setting-theme") ? $el.attr("data-setting-theme") : "default",
            tabSize: $el.attr("data-setting-tab-size") ? $el.attr("data-setting-tab-size") : 4,
            readOnly: $el.attr("data-setting-read-only") == "true",
            autofocus: $el.attr("data-setting-autofocus") == "true",
            dragDrop: $el.attr("data-setting-drag-drop") ? $el.attr("data-setting-drag-drop") == "true" : true,
            matchBrackets: $el.attr("data-setting-match-brackets") ? $el.attr("data-setting-match-brackets") == "true" : true,
        });
    
        cm.on("change", function(){
            cm.save();
        });
        
        $el.on('contentUpdate', function() {
            cm.setValue($el.val());
        });
    }
    
    function checkLanguage($el){
    
        if( $el.attr("data-setting-mode") && $.inArray($el.attr("data-setting-mode"), plugins) == -1){
            plugins.push($.inArray($el.attr("data-setting-mode")));
            $.getScript("../klearMatrix/js/plugins/codemirror/mode/" + $el.attr("data-setting-mode") + '/' + $el.attr("data-setting-mode") + '.js',function() {
            	setCodeMirror($el)
            });
        }
        else setCodeMirror($el);
    }
    
    function checkMatchBrackets($el){
    	
    	if( $.inArray("matchbrackets", plugins) == -1 && ($el.attr("data-setting-match-brackets") ? $el.attr("data-setting-match-brackets") == "true" : true)){
            plugins.push("matchbrackets");
            $.getScript("../klearMatrix/js/plugins/codemirror/addon/edit/matchbrackets.js",function() {
            	checkLanguage($el)
            });
        }
        else checkLanguage($el);
    }
    
})(jQuery);

