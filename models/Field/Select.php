<?php

/**
 * Clase que devuelve la ruta al forward de _dispatch en base a la configuración a los parámetros de request
* @author jabi
*
*/

class KlearMatrix_Model_Field_Select extends KlearMatrix_Model_Field_Abstract
{
    protected $_adapter;

    protected function _init()
    {
        $sourceConfig = $this->_config->getRaw()->source;
        $adapterClassName = "KlearMatrix_Model_Field_Select_" . ucfirst($sourceConfig->data);

        $this->_adapter = new $adapterClassName($sourceConfig, $this->_column);
        $this->_js = $this->_adapter->getExtraJavascript();
    }

    protected function _filterValue($value)
    {
        if ($value == '__NULL__') {
            return NULL;
        }
        return $value;
    }

    public function getCustomOrderField()
    {
        if (method_exists($this->_adapter, 'getCustomOrderField')) {

            return $this->_adapter->getCustomOrderField();
        }

        return $this->_column->getDbFieldName();
    }

    public function isMassUpdateable()
    {
        return true;
    }

    protected function _getAttributes($fieldConfig)
    {
        $fieldConfig = parent::_getAttributes($fieldConfig);
        return $this->_prepareFieldCustomAttributes($fieldConfig);
    }

    protected function _prepareFieldCustomAttributes($fieldConfig)
    {
        return $this->_prepareAutofilterAttribute($fieldConfig);
    }

    protected function _prepareAutofilterAttribute($fieldConfig)
    {
        $autoFilterKey = 'data-autofilter-select-by-data';
        if (
            !is_array($fieldConfig) ||
            !array_key_exists($autoFilterKey, $fieldConfig) ||
            !is_array($fieldConfig[$autoFilterKey])
        ) {
            return $fieldConfig;
        }

        $autofilter = array();
        foreach ($fieldConfig[$autoFilterKey] as $autofilterElement => $value) {
            if (is_array($value)) {
                $autofilter[] = key($value) . ':' . current($value);
            } else {
                $autofilter[] = $autofilterElement . ':' . $value;
            }
        }

        $fieldConfig[$autoFilterKey] = implode("|", $autofilter);
        return $fieldConfig;
    }
}

//EOF