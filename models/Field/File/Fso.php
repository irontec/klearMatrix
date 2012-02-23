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
            "/js/plugins/qq-fileuploader.js"
    );
    
    protected $_css = array(
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
        
        if ($downloadMethod = $this->_config->download) {
            if (isset($downloadMethod->dialog)) {
                $ret['downloadDialog'] = $downloadMethod->dialog;
            }
        }
        
        if ($uploadMethod = $this->_config->upload) {
            if (isset($uploadMethod->command)) {
                $ret['uploadCommnad'] = $uploadMethod->command;
            }
        }
        
        return $ret;        
    }

    public function getFetchMethod($dbName) {
        return 'fetch' . $dbName;
    }
    
    public function getInvolvedFields() {
        $ret = array();
        if ($this->_fileName) {
            $ret['name'] = $this->_fileName;
        }
        
        if ($this->_fileSize) {
            $ret['size'] = $this->_fileSize;
        }
        
        if ($this->_mimeType) {
            $ret['mime'] = $this->_mimeType;
        }
        
        return $ret;
    }
    
    
    public function getExtraJavascript()
	{
	    return $this->_js;
	}
	
	public function getExtraCss() {
	    return $this->_css;
	}
    
}