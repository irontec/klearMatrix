<?php
abstract class KlearMatrix_Model_Field_AbstractAdapter
{
    protected $_config;
    protected $_js;
    protected $_css;

    abstract public function getConfig();
    abstract public function setConfig(Zend_Config $config);

    public function getExtraJavascript()
    {
        return $this->_js;
    }

    public function getExtraCss()
    {
        return $this->_css;
    }
}