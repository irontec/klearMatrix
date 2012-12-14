<?php
class KlearMatrix_Model_ModelSpecification
{

    protected $_config;
    protected $_class;

    protected $_pk;
    protected $_instance;

    public function __construct(Zend_Config $config)
    {
        $this->_config = new Klear_Model_ConfigParser;
        $this->_config->setConfig($config);

        $this->_class = $this->_config->getRequiredProperty("class");
        $this->_instance = new $this->_class;
    }

    public function getInstance()
    {
        if ($this->_instance->getPrimaryKey() != $this->_pk) {

            $this->_instance = $this->_instance->getMapper()->find($this->_pk);
        }

        return $this->_instance;
    }

    public function getClassName()
    {
        return $this->_class;
    }

    public function getField($fName)
    {
        if ($this->_config->exists("fields->" . $fName)) {
            return $this->_config->getRaw()->fields->{$fName};
        }
        return false;
    }

    public function getFields()
    {
        $fields = array();
        $aFields = $this->_config->getRaw()->fields;
        if (is_array($aFields) || $aFields instanceof Zend_Config) {
            foreach ($aFields as $key => $field) {
                $fields[$key] = $field;
            }
        }
        return $fields;
    }

    public function setPrimaryKey($pk)
    {
        $this->_pk = $pk;
        return $this;
    }
}
