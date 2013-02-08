<?php
class KlearMatrix_Model_Info
{

    protected $_config;
    protected $_fieldInfo = array();

    public function setConfig(Zend_Config $info)
    {
        $this->_config = new Klear_Model_ConfigParser();
        $this->_config->setConfig($info);

        $this->_fieldInfo = array();
        $this->_fieldInfo['type'] = $this->_config->getProperty('type')? $this->_config->getProperty('type'):'tooltip';
        $this->_fieldInfo['text'] = Klear_Model_Gettext::gettextCheck($this->_config->getProperty('text'));
        $this->_fieldInfo['position'] = $this->_config->getProperty('position')? $this->_config->getProperty('position'):'left';
        $this->_fieldInfo['icon'] = $this->_config->getProperty('icon')? $this->_config->getProperty('icon'):'help';
        $this->_fieldInfo['label'] = Klear_Model_Gettext::gettextCheck($this->_config->getProperty('label')? $this->_config->getProperty('label'):'');

    }

    /**
     * devuelve la intel preparada para ser JSON-eada.
     * Por favor, poner el nombre que deseeis;
     * @return multitype:
     */
    public function getJsonArray()
    {
        return $this->_fieldInfo;
    }


}
