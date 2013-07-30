<?php
class KlearMatrix_Model_Info
{

    protected $_config;
    protected $_fieldInfo = array(
        'type' => 'tooltip',
        'position' => 'left',
        'icon' => 'help',
        'text' => '',
        'label' => ''
    );

    public function setConfig(Zend_Config $info)
    {
        $this->_config = new Klear_Model_ConfigParser();
        $this->_config->setConfig($info);

        $this->_fieldInfo['type'] = $this->_getProperty('type');
        $this->_fieldInfo['position'] = $this->_getProperty('position');
        $this->_fieldInfo['icon'] = $this->_getProperty('icon');
        $this->_fieldInfo['text'] = $this->_getTranslatedProperty('text');
        $this->_fieldInfo['label'] = $this->_getTranslatedProperty('label');
    }

    protected function _getTranslatedProperty($key)
    {
        $property = $this->_getProperty($key);
        return Klear_Model_Gettext::gettextCheck($property);
    }

    protected function _getProperty($key)
    {
        $value = $this->_config->getProperty($key);

        if (!$value) {
            return $this->_fieldInfo[$key];
        }

        return $value;
    }


    /**
     * devuelve la intel preparada para ser JSON-eada.
     * @return multitype:
     */
    public function toArray()
    {
        return $this->_fieldInfo;
    }

}
