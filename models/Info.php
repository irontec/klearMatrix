<?php
class KlearMatrix_Model_Info
{

    protected $_config;
    protected $_fieldInfo = array(
        'type' => 'box',
        'position' => 'left',
        'icon' => 'help',
        'text' => '',
        'label' => '',
        'href' => '',
        'target' => '_blank'
    );


    protected $_textAttrs = array('text','label');


    /**
     * Debemos "engañar" a matrixResponse::toArray
     */
    public function count()
    {
        return 1;
    }

    public function setConfig(Zend_Config $info)
    {
        $this->_config = new Klear_Model_ConfigParser();
        $this->_config->setConfig($info);
        $keys = array_keys($this->_fieldInfo);
        foreach ($keys as $key) {
            if (in_array($key, $this->_textAttrs)) {
                $this->_fieldInfo[$key] = $this->_getTranslatedProperty($key);
            } else {
                $this->_fieldInfo[$key] = $this->_getProperty($key);
            }
        }
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
