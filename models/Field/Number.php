<?php
class KlearMatrix_Model_Field_Number extends KlearMatrix_Model_Field_Abstract
{

    protected $_control;

    public function init()
    {
        parent::init();

        $sourceConfig = $this->_config->getRaw()->source;

        $controlClassName = "KlearMatrix_Model_Field_Number_" . ucfirst($sourceConfig->control);

        $this->_control = new $controlClassName($sourceConfig);
        $this->_js = $this->_control->getExtraJavascript();
        $this->_css = $this->_control->getExtraCss();
    }

    public function getConfig()
    {
        return $this->_control->getConfig();
    }
}

//EOF