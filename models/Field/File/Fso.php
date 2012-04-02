<?php


/*
 * TODO: Abstracta de File
 */
class KlearMatrix_Model_Field_File_Fso
{
    
    protected $_config;
    
    protected $_fileName;
    protected $_fileSize;
    protected $_mimeType;
    
    protected $_js = array(
            
            "/js/plugins/jquery.jplayer.min.js",
            "/js/plugins/qq-fileuploader.js"
    );
    
    protected $_css = array(
            "/css/jquery.jplayer.css",
            "/css/qq-fileuploader.css"
    );
    
    public function setConfig($config) {
        $this->_config = $config;
        return $this;
    }
    
    
    public function init() {
    
        
    }
    
    protected function _getAllowedExtensions() {
        $exts = array();
        if (!isset($this->_config->extensions)) return array();
        foreach($this->_config->extensions as $ext) {
            $exts[] = $ext;            
        }
        return implode(',',$exts);        
    }

    protected function _getSizeLimit() {
        if (isset($this->_config->size_limit)) {
            return $this->_config->size_limit;
        } else {
            return null;
        } 
    }
    
    public function getConfig() {
        $ret = array();
        $ret['allowed_extensions'] = $this->_getAllowedExtensions();
        $ret['size_limit'] = $this->_getSizeLimit();
        
        if ($fileOptions = $this->_config->options) {
            $ret['options'] = array();
            foreach ($fileOptions as $option => $opObject) {
                
                $ret['options'][$option] = $opObject;
            }
            
        }
        return $ret;        
    }

    public function getFetchMethod($dbName) {
        return 'fetch' . $dbName;
    }
    
      
    public function getExtraJavascript()
	{
	    return $this->_js;
	}
	
	public function getExtraCss() {
	    return $this->_css;
	}
    
}