<?php
class KlearMatrix_Model_Field_Textarea_Codemirror extends KlearMatrix_Model_Field_Textarea_Abstract
{
    
    public function init()
    {
    
        $this->_js = array(
                "/js/plugins/codemirror/lib/codemirror.js",
                "/js/plugins/jquery.ui.klearcodemirror.js",
        );
        
        $this->_css = array(
                "/js/plugins/codemirror/lib/codemirror.css",
        );

        if($this->_config->getProperty('settings')){
            $this->_settings = $this->_config->getProperty('settings')->toArray();
            if( isset($this->_settings['theme'])) 
                $this->_css[] = "/js/plugins/codemirror/theme/" . $this->_settings['theme'] . ".css";
        }
    }
    
    protected function _setPlugin()
    {
        $this->_plugin = 'klearcodemirror';
    }
}