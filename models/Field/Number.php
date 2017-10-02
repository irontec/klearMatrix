<?php
class KlearMatrix_Model_Field_Number extends KlearMatrix_Model_Field_Abstract
{

    protected $_adapter;

    protected function _init()
    {
        $sourceConfig = $this->_config->getRaw()->source;

        $controlClassName = "KlearMatrix_Model_Field_Number_" . ucfirst($sourceConfig->control);

        $this->_adapter = new $controlClassName($sourceConfig);

        $this->_js = $this->_adapter->getExtraJavascript();
        $this->_css = $this->_adapter->getExtraCss();
    }

    /*
     * Filtra (y adecua) el valor del campo antes del setter
     *
     */
    public function filterValue($value)
    {
        if ($value === '') {
            $value = null;
        }

        return parent::filterValue($value);
    }
}

//EOF