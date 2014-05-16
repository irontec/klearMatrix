<?php

class KlearMatrix_Model_Link
{

    protected $_config;

    /**
     *
     * @var \KlearMatrix_Model_Field_Abstract
     */
    protected $_field;

    protected $_link = array(
        'href' => '',
        'text' => 'link',
        'class' => 'link',
        'target' => '__blank'
    );

    public function setConfig(Zend_Config $info)
    {
        $this->_config = new Klear_Model_ConfigParser();
        $this->_config->setConfig($info);
        return $this;
    }

    public function setSetField(KlearMatrix_Model_Field_Abstract $field)
    {
        $this->_field = $field;
        return $this;
    }

    protected function _build()
    {
        $col = $this->_field->getColumn();
        $mainModel = $col->getModel();
        $configFields = $this->_config->getRaw()->fields;
        $view = Zend_Layout::getMvcInstance()->getView();
        $params = array(
                        'controller' => $this->_config->getRaw()->controller,
                        'action' => $this->_config->getRaw()->action
        );
        foreach ($configFields as $fieldKey=>$fieldName) {
            $getter = 'get' . $mainModel->columnNameToVar($fieldName);
            $params[$fieldKey] = $mainModel->{$getter}();
        }

        $this->_link['href'] = $view->serverUrl() . $view->url($params, 'default', true);

        if ($this->_config->getRaw()->text) {
            $text = Klear_Model_Gettext::gettextCheck($this->_config->getRaw()->text);
            $this->_link['text'] = $text;
        }
    }

    /**
     * devuelve la intel preparada para ser JSON-eada.
     * @return multitype:
     */
    public function toArray()
    {
        $this->_build();
        return $this->_link;
    }
}