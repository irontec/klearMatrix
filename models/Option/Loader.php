<?php

class KlearMatrix_Model_Option_Loader
{

    protected $_config = false;

    protected $_parentConfig = false;

    protected $_defaultOption = null;

    protected $_availableOptionTypes = array(
    	'screen',
        'dialog',
        'command',
        'link'
    );

    protected $_extraParamsFunction = false;

    protected $_conditionalFunction = array();
    /**
     *
     * @var \KlearMatrix_Model_Option_Collection
     */
    protected $_fieldOptions;

    public function __construct()
    {
        $this->_fieldOptions = new KlearMatrix_Model_Option_Collection();
    }

    public function setDefaultOption($option)
    {
        $this->_defaultOption = $option;
        return $this;
    }

    public function setMainConfig($config)
    {
//         KlearMatrix_Model_MainConfig
        $this->_config = $config;
        return $this;
    }

    public function setParentConfig($config)
    {
//         Klear_Model_ConfigParser
        $this->_parentConfig = $config;
        return $this;
    }

    public static function getFieldsOptionsConfig($type, $parent)
    {
        $retArray = array();
        switch($type) {
        	case 'dialog':
        	    $property = 'dialogs';
        	    break;
        	case 'screen':
        	    $property = 'screens';
        	    break;
        	case 'command':
        	    $property = 'commands';
        	    break;
        	case 'link':
        	    $property = 'links';
        	    break;
        	default:
        	    Throw new Zend_Exception("Undefined Option Type");
        	    break;
        }
        $_items = $parent->getProperty($property);
        if (!$_items) {
            return array();
        }
        foreach ($_items  as $_item=> $_enabled) {
            if (!(bool)$_enabled) {
                continue;
            }
            $retArray[] = $_item;
        }
        return $retArray;
    }

    protected function _getFieldsOptionsConfig($type)
    {
        return $this->getFieldsOptionsConfig($type, $this->_parentConfig);
    }

    protected function _parseOptionsConfigs($configs, $type)
    {
        foreach ($configs as $conf) {
            $class = 'KlearMatrix_Model_Option_' . ucfirst($type);
            $option = new $class();
            $option->setName($conf);
            if ($conf === $this->_defaultOption) {
                $option->setAsDefault();
                $defaultOption = false;
            }
            $option->setConfig($this->_config->{'get' . ucfirst($type) . 'Config'}($conf));
            if (gettype($this->_extraParamsFunction) == 'object'
                    && $this->_extraParamsFunction instanceof Closure) {
                call_user_func($this->_extraParamsFunction, $option);
            }
            if (isset($this->_conditionalFunction[$type])
            && gettype($this->_conditionalFunction[$type]) == 'object'
            && $this->_conditionalFunction[$type] instanceof Closure) {
                call_user_func($this->_conditionalFunction[$type], $option);
            }
            if ($option->getIsSkipped() != true) {
                $this->_fieldOptions->addOption($option);
            }
        }
    }

    public function registerConditionalFunction($type, $function)
    {
        $this->_conditionalFunction[$type] = $function;
    }

    protected function _parseOptions()
    {
        foreach ($this->_availableOptionTypes as $type) {
            $config = $this->_getFieldsOptionsConfig($type);
            $this->_parseOptionsConfigs($config, $type);
        }
    }

    public function setExtraParamsFunction($extraParamsFunction)
    {
        $this->_extraParamsFunction = $extraParamsFunction;
    }

    public function getFieldOptions()
    {
        if ($this->_parentConfig && $this->_config) {
            $this->_parseOptions();
        }
        return $this->_fieldOptions;
    }
}
