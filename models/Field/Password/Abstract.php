<?php

abstract class KlearMatrix_Model_Field_Password_Abstract extends KlearMatrix_Model_Field_AbstractAdapter
{

    protected $_clearValue;

    public function setClearValue($value)
    {
        $this->_clearValue = $value;

        return $this;
    }

    public function cryptValue()
    {
        return $this->_clearValue;
    }

    public function setConfig(Zend_Config $config)
    {
        return $this;
    }

    public function getConfig()
    {
        return array();
    }

}

//EOF