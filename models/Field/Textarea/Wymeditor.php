<?php

class KlearMatrix_Model_Field_Textarea_Wymeditor extends KlearMatrix_Model_Field_Textarea_Abstract
{

    protected $_js = array(
        "/js/plugins/wymeditor/jquery.wymeditor.min.js",
        '/js/plugins/wymeditor/plugins/resizable/jquery.wymeditor.resizable.js',
        '/js/plugins/wymeditor/plugins/hovertools/jquery.wymeditor.hovertools.js',
        '/js/plugins/wymeditor/plugins/fullscreen/jquery.wymeditor.fullscreen.js',
        "/js/plugins/jquery.ui.klearwymeditor.js"
    );

    protected $_css = array();

    public function getExtraJavascript()
    {
        return $this->_js;
    }

    public function getExtraCss()
    {
        return $this->_css;
    }

    public function getConfig()
    {
        $ret = array(
            "plugin" => 'klearwymeditor',
            "settings" => array(
                'basePath' => "../klearMatrix/js/plugins/wymeditor/",
                'wymPath' => "../klearMatrix/js/plugins/wymeditor/"
            )
        );

        return $ret;
    }

    public function init()
    {

    }

}

//EOF