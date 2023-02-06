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

    protected function _cleanHtmlComments($value)
    {
        $value = $this->_filterValue($value);
        return preg_replace('/(<!--.*-->)/Uis', '', $value);
    }

    public function filterValue($value)
    {
        // Por defecto siempre vamos a eliminar comentarios HTML de campos textarea.
        // salvo si cleanupHTMLComments === false en la configuración del campo.
        $cleanupHTMLComments = $this->_config->getRaw()->cleaupHTMLComments;
        $cleanComments = true;
        if (!is_null($cleanupHTMLComments)) {
            $cleanComments = (bool)$cleanupHTMLComments;
        }

        if ($cleanComments === false) {
            return parent::filterValue($value);
        }

        if ($this->_column->isMultilang()) {

            $retValue = array();
            foreach ($value as $lang => $_value) {
                $retValue[$lang] = $this->_cleanHtmlComments($_value);
            }

        } else {
            $retValue = $this->_cleanHtmlComments($value);
        }

        return $retValue;
    }
}
//EOF
