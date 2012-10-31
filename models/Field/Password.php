<?php
class KlearMatrix_Model_Field_Password extends KlearMatrix_Model_Field_Abstract
{

    /**
     * @var KlearMatrix_Model_Field_Password_Abstract
     */
    protected $_adapter;

    protected function _init()
    {
        $adapterClassName = "KlearMatrix_Model_Field_Password_" . ucfirst($this->_config->getProperty("adapter"));

        $this->_adapter = new $adapterClassName;
    }

    public function filterValue($value, $original)
    {
        $this->_adapter->setClearValue($value);

        return $this->_adapter->cryptValue();
    }

    /*
     * Prepara el valor de un campo, después del getter
     */
    public function prepareValue($value, $model)
    {
        return "********";
    }
}

//EOF