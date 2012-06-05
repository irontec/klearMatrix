<?php

/**
 * Clase que devuelve la ruta al forward de _dispatch en base a la configuración a los parámetros de request
* @author jabi
*
*/

class KlearMatrix_Model_Field_Select extends KlearMatrix_Model_Field_Abstract
{

    protected $_adapter;

    public function init()
    {
        parent::init();
        $sourceConfig = $this->_config->getRaw()->source;

        $adapterClassName = "KlearMatrix_Model_Field_Select_" . ucfirst($sourceConfig->data);

        $this->_adapter = new $adapterClassName;
        $this->_adapter
                    ->setConfig($sourceConfig)
                    ->setColumn($this->_column)
                    ->init();
    }

    public function filterValue($value,$original)
    {
        if ($value == '__NULL__') {

            return NULL;
        }

        return $value;
    }

    public function getConfig()
    {
        $ret = array_merge(
            $this->_adapter->getExtraConfigArray(),
            array('values' => $this->_adapter->toArray())
        );

        return $ret;
    }

}

//EOF