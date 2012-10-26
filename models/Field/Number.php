<?php
class KlearMatrix_Model_Field_Number extends KlearMatrix_Model_Field_Abstract
{

    protected $_adapter;

    public function init()
    {
        parent::init();

        $sourceConfig = $this->_config->getRaw()->source;

        $controlClassName = "KlearMatrix_Model_Field_Number_" . ucfirst($sourceConfig->control);

        $this->_adapter = new $controlClassName($sourceConfig);

        $this->_js = $this->_adapter->getExtraJavascript();
        $this->_css = $this->_adapter->getExtraCss();
    }

    public function getConfig()
    {
        return $this->_adapter->getConfig();
    }
}

//EOF