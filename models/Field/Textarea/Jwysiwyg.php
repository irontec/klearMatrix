<?php 

class KlearMatrix_Model_Field_Textarea_Jwysiwyg extends KlearMatrix_Model_Field_Textarea_Abstract
{
    
    protected $_js = array(
            "/js/plugins/jwysiwyg/jquery.wysiwyg.js",
            "/js/plugins/jwysiwyg/src/controls/default.js",
            "/js/plugins/jquery.ui.klearwysiwyg.js"
            
    );
    
    protected $_css = array(
            "/js/plugins/jwysiwyg/jquery.wysiwyg.css"
            );
    
    public function getExtraJavascript() {
        return $this->_js;
    
    }
    
    public function getExtraCss() {
        return $this->_css;
    
    }
    
    
    public function getConfig() {
        return
        array(
                "plugin"=>'klearwysiwyg',
                "settings" =>
                array(
                        
                        
                )
    
        );
    }
    
    
    public function init() {
    
    
    
    }
    
}