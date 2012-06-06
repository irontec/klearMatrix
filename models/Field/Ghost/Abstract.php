<?php
abstract class KlearMatrix_Model_Field_Ghost_Abstract
{
    protected $_config;

    public function setConfig(Zend_Config $config)
    {
        $kconfig = new Klear_Model_KConfigParser;
        $kconfig->setConfig($config);

        $this->_config = $kconfig;
        return $this;
    }
}

//EOF