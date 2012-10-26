<?php
class KlearMatrix_Model_Field_Textarea extends KlearMatrix_Model_Field_Abstract
{

    protected $_control;

    public function init()
    {
        parent::init();

        $sourceConfig = $this->_config->getRaw()->source;

        if ($sourceConfig) {

            $controlClassName = "KlearMatrix_Model_Field_Textarea_" . ucfirst($sourceConfig->control);
            $this->_control = new $controlClassName($sourceConfig);

            $this->_js = $this->_control->getExtraJavascript();
            $this->_css = $this->_control->getExtraCss();
        }
    }

    public function getConfig()
    {
        if ($this->_control) {

            return $this->_control->getConfig();

        } else {

            return parent::getConfig();
        }
    }
}

//EOF