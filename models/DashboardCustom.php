<?php
class KlearMatrix_Model_DashboardCustom implements KlearMatrix_Model_Interfaces_Dashboard
{
    protected $_config;
    protected $_item;

    public function setConfig(Zend_Config $config)
    {
        $this->_config = $config;
    }

    public function setItem(KlearMatrix_Model_ResponseItem $item)
    {
        $this->_item = $item;
    }

    public function getName()
    {
        return $this->_item->getTitle();
    }
    public function getClass()
    {
        return $this->_item->getRawConfigAttribute("class");
    }

    public function getFile()
    {
        return $this->_config->file;
    }
    public function getSubtitle()
    {
        return false;
    }

}