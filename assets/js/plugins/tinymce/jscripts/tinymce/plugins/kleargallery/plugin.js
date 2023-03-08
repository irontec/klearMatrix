tinymce.PluginManager.add("kleargallery", function(e) {

    var editor = e;
    var dialogWindow = {};
    var _self = this;

    e.addButton("kleargallery", {
        text : "Gallery",
        tooltip: $.translate('Galería de imágenes'),
        icon : false,
        onclick: function() {

            dialogWindow = editor.windowManager.open({
                title: 'Gallery',
                width : 460,
                height : 360,
                html: '<p>Loading...</p>',
                buttons: {
                    text: 'Close',
                    classes: 'widget btn first last abs-layout-item close',
                    onclick: function() {
                        this.parent().parent().close();
                    }
                },
            });

            loadContent();
        },
    });

    function loadContent() {

      $.klear.request(
        {
          file: "GalleryList",
          type: 'dialog',
          screen: 'Gallery_dialog',
          post : {}
      },
      function(data) {

        if(!data.error) {

            loadTinyDialog(data);
            //loadKlearDialog(data);

        } else {

            //self.standardError(data.error);
        }

      },function(data) {

            //self.standardError(data.error);
      });
    }

    function loadTinyDialog(data) {
        var container = $("#" + dialogWindow._id + "-body > p");
        var containerParent = container.parents(".mce-container:eq(0)");

        container.klearModule("getPanel").gallery(data);
        containerParent.removeClass("mce-container");
    }

    function loadKlearDialog (data) {
        var $dialog = $(editor.settings._contentTab).klearModule("showDialog",
            '<br />',
            {
                title: 'Galería',
                width: 460,
                height: 350,
                template : "<div>Loading...</div>",
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

        $dialog.klearModule("getModuleDialog").gallery(data);
    }

}, ["image"]);
