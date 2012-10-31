<?php
class KlearMatrix_Model_Field_Textarea extends KlearMatrix_Model_Field_Abstract
{

    protected $_adapter;

    protected function _init()
    {
        $sourceConfig = $this->_config->getRaw()->source;

        if ($sourceConfig) {

            $controlClassName = "KlearMatrix_Model_Field_Textarea_" . ucfirst($sourceConfig->control);
            $this->_adapter = new $controlClassName($sourceConfig);

            $this->_js = $this->_adapter->getExtraJavascript();
            $this->_css = $this->_adapter->getExtraCss();
        }
    }

    public function getConfig()
    {
        if ($this->_adapter) {
            return $this->_adapter->getConfig();
        }

        return false;
    }
}

//EOF