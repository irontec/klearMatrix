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
    }

    public function filterValue($value, $original)
    {
        if ($value == '__NULL__') {

            return NULL;
        }

        return $value;
    }

    public function getConfig()
    {
        return $this->_adapter->getConfig();
    }

}

//EOF