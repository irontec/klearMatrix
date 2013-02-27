(function load($, undefined) {

    if (!$.klear.checkDeps(['WYMeditor'], load)) {

        return;
    }

    //Extend WYMeditor
    WYMeditor.editor.prototype.kleargallery = function(contentTab) {

        var pendingScriptsToLoad = 0;

        var wym = this,
            $box = jQuery(this._box),
            $iframe = jQuery(this._iframe),
            $overlay = null,
            $window = jQuery(window),

            editorMargin = 15;     // Margin from window (without padding)

        //construct the button's html
        var html = '' +
            "<li class='wym_tools_image'>" +
                "<a name='Gallery' href='#'>" +
                    "Gallery" +
                "</a>" +
            "</li>";
        //add the button to the tools box
        $box.find(wym._options.toolsSelector + wym._options.toolsListSelector).append(html);

        //handle click event
        $box.find('li.wym_tools_image a').click(function(e) {

            var $dialog = $(contentTab).klearModule("showDialog",
                '<br />',
                {
                    title: 'Galería',
                    width: 460,
                    height: 350,
                    template : "<span>Loading...</span>",
                    buttons : [
                        {
                            text: "Cerrar",
                            click: function() {

                                $(this).moduleDialog("close");
                            }
                        },
                    ]
                }
            );

            $.klear.request(
                  {
                      file: "GalleryList",
                      type: 'dialog',
                      screen: 'Gallery_dialog',
                      post : {}
                  },
                  function(data) {

                      if(!data.error) {

                          data._wym = wym;
                          var content = $.tmpl(data.data.templateName, data.data, $.klearmatrix.templatehelper);
                          $dialog.klearModule("getModuleDialog").gallery(data);

                      } else {

                          //self.standardError(data.error);
                      }

                  },function(data) {

                        //self.standardError(data.error);
                  }
            );

            e.preventDefault();
            e.stopPropagation();
        });
    };

})(jQuery);