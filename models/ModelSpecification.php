<?php
class KlearMatrix_Model_ModelSpecification
{

    protected $_config;

    /**
     * @deprecated
     */
    protected $_class;
    protected $_dto;
    protected $_entity;

    protected $_pk;
    protected $_instance;

    public function __construct(Zend_Config $config)
    {
        $this->_config = new Klear_Model_ConfigParser;
        $this->_config->setConfig($config);

        $this->_class = $this->_config->getProperty("class");
        $this->_dto = $this->_config->getRequiredProperty("dto");
        $this->_entity = $this->_config->getRequiredProperty("entity");

        if ($GLOBALS['sf']) {
            $this->_instance = new $this->_dto;
        } else if (!$GLOBALS['sf']) {
            $this->_instance = new $this->_class;
        }
    }

    public function getInstance()
    {
        if ($GLOBALS['sf']) {

            $requestIntance = isset($this->_pk);
            if (
                $this->_instance &&
                $this->_pk &&
                $this->_instance->getId() == $this->_pk
            ) {
                $requestIntance = false;
            }

            if ($requestIntance) {
                $dataGateway = \Zend_Registry::get('data_gateway');
                $this->_instance = $dataGateway->find($this->_entity, $this->_pk);
            }

        } else if (!$GLOBALS['sf']) {
            if ($this->_instance->getPrimaryKey() != $this->_pk) {

                $this->_instance = $this->_instance->getMapper()->find($this->_pk);
            }
        }

        return $this->_instance;
    }

    public function getClassName()
    {
        return $GLOBALS['sf'] ? $this->_dto : $this->_class;
    }

    public function getEntityClassName()
    {
        return $this->_entity;
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


    public function getMultilangFields()
    {
        $response = [];
        foreach ($this->getFields() as $fieldName => $spec) {
            if (isset($spec->isMultilang) && $spec->isMultilang) {
                $response[] = $fieldName;
            }
        }

        return $response;
    }

    public function setPrimaryKey($pk)
    {
        $this->_pk = $pk;
        return $this;
    }
}
