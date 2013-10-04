;(function() {

    $.klear = $.klear || {};
    $.klear.baseurl = $.klear.baseurl || $("base").attr("href");
    $.klear.loadedTemplates = $.klear.loadedTemplates || {};
    var _kmList = {
        'jsFile_-1824558962' : '2.5.3-crypto-md5.js',
        'jsFile_472193935' : 'jquery.autoresize.js',
        'jsFile_801193221' : 'jquery.h5validate.js',
        'jsFile_1965225950' : 'jquery.jplayer.min.js',
        'jsFile_53406754' : 'jquery.multiselect.filter.js',
        'jsFile_381177282' : 'jquery.multiselect.js',
        'jsFile_-1184596132' : 'jquery.ui.form.js',
        'jsFile_-1960768398' : 'jquery.ui.spinner.js',
        'jsFile_761451189' : 'qq-fileuploader.js',
        'jsFile_395628695' : 'jquery.klearmatrix.module.js',
        'jsFile_-1818335427' : 'jquery.klearmatrix.genericdialog.js',
        'jsFile_1150212397' : 'jquery.klearmatrix.template.helper.js',
        'jsFile_694066308' : 'jquery.klearmatrix.list.js',
        'jsFile_705185194' : 'jquery.klearmatrix.edit.js',
        'jsFile_506478294' : 'jquery.klearmatrix.new.js',
        'jsFile_-1878899213' : 'jquery.klearmatrix.dashboard.js',
        'jsFile_1575925159' : 'jquery.klearmatrix.genericscreen.js'
    };

    for(var iden in _kmList) {
        $.klear.loadedScripts[iden] = true;
    }

    $.ajax({
        url : $.klear.baseurl +'../klearMatrix/template/cache',
          dataType:'json',
          success: function(response) {

              if (!response.templates) {
                  return;
              }
              for(var tmplIden in response.templates) {
                   $.template(tmplIden, response.templates[tmplIden]);
                 $.klear.loadedTemplates[tmplIden] = true;
              }
        }
    });

})();
