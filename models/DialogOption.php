<?php

/**
* @author jabi
*
*/
class KlearMatrix_Model_DialogOption
{

    protected $_config;
    protected $_dialog;
    protected $_class;
    protected $_title;

    protected $_default = false;

    protected $_noLabel = true;

    public function setDialogName($dialog)
    {
        $this->_dialog = $dialog;
    }

    public function setConfig(Zend_Config $config)
    {

        $this->_config = new Klear_Model_KConfigParser;
        $this->_config->setConfig($config);

        $this->_title = $this->_config->getProperty("title", false);

        $this->_class = $this->_config->getProperty("class", false);
        $this->_label = (bool)$this->_config->getProperty("label", false);
    }


    public function getTitle()
    {
        if (null != $this->_title) {
            return $this->_title;
        }

        return 'unnamed option';

    }

    public function setAsDefault()
    {
        $this->_default = true;
    }

    public function isDefault()
    {
        return true === $this->_default;
    }

    public function toArray()
    {
        $ret = array(
            'icon'=>$this->_class,
            'type'=>'dialog',
            'dialog'=>$this->_dialog,
            'title'=>$this->getTitle(),
            'defaultOption'=>$this->isDefault(),
            'label'=>$this->_label
        );

        if ($this->isDefault()) {
            $ret['defaultOption'] = true;
        }

        return $ret;
    }

}
