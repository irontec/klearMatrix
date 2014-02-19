<?php

abstract class KlearMatrix_Model_Field_Abstract
{
    /**
     * @var KlearMatrix_Model_Column
     */
    protected $_column;
    protected $_config;
    protected $_isSearchable = true;
    protected $_isSortable = true;

    protected $_decorators = null;

    protected $_propertyMaster = array(
            "required",
            "pattern",
            "placeholder",
            "nullIfEmpty",
            "maxLength",
            "expandable",
            "showsize",
            "defaultValue" // Valor por defecto en caso de new
            );

    protected $_properties = array();

    /*valid error index */
    protected $_errorIndex = array(
        'patternMismatch',
        'rangeOverflow',
        'rangeUnderflow',
        'stepMismatch',
        'tooLong',
        'typeMismatch',
        'valueMissing'
    );
    protected $_errorMessages = array();

    protected $_js = array();
    protected $_css = array();

    protected $_adapter;

    /**
     * Constructor must not be directly called from outside. Use the factory method instead
     */
    public function __construct(KlearMatrix_Model_Column $column)
    {
        $this->setColumn($column);
        $this->_config = $this->_column->getKlearConfig();

        if (is_object($this->_config)) {

            foreach ($this->_propertyMaster as $property) {

                $this->_properties[$property] = $this->_config->getProperty($property);
            }

            $this->_parseErrorMessages();
        }

        $this->_applyDefaultCutomConfiguration()
             ->_initSortable()
             ->_initSearchable()
             ->_loadDecorators();

        $this->_init();
    }

    protected function _applyDefaultCutomConfiguration()
    {
        $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        $siteConfig = $bootstrap->getResource('modules')->offsetGet('klear')->getOption('siteConfig');

        $autoShowSizeOnExpandable = $siteConfig->getDefaultCustomConfiguration('autoShowSizeOnExpandableFields');
        if ($autoShowSizeOnExpandable && isset($this->_properties['expandable'])) {
            if ($this->_properties['expandable'] === true) {
                $this->_properties['showsize'] = true;
            }
        }

        return $this;

    }

    protected function _initSortable()
    {
        if (is_object($this->_config)
                           && $this->_config->exists("sortable")) {

            $this->_isSortable =(bool)$this->_config->getProperty('sortable');
        }

        return $this;
    }

    protected function _initSearchable()
    {
        if (is_object($this->_config)
                && $this->_config->exists("searchable")) {

            $this->_isSearchable =(bool)$this->_config->getProperty('searchable');
        }

        return $this;
    }

    public function setColumn($column)
    {
        $this->_column = $column;
        return $this;
    }

    public function getColumn()
    {
        return $this->_column;
    }

    protected function _init()
    {
        // Leave this body empty in the abstract class
    }

    protected function _parseErrorMessages()
    {
        $_errorMsgs = $this->_config->getProperty("errorMessages");

        if (!$_errorMsgs) {
            return;
        }

        $errorConfig = new Klear_Model_ConfigParser;
        $errorConfig->setConfig($_errorMsgs);

        foreach ($this->_errorIndex as $errorIndex) {
            if (isset($_errorMsgs->$errorIndex)) {
                $errorData = $_errorMsgs->$errorIndex;

                if (is_string($errorData)) {
                    $errorString = Klear_Model_Gettext::gettextCheck($errorData);
                } else {
                    $errorString = $errorConfig->getProperty($errorData);
                }

                $this->_errorMessages[$errorIndex] = $errorString;
            }
        }
    }

    /**
     * Returns array with field's view configuration
     * @return array
     */
    public function getConfig()
    {
        $fieldConfig = $this->_getAdapterConfig();
        $fieldConfig['attributes'] = $this->_getAttributes($fieldConfig);


        return $fieldConfig;
    }

    protected function _getAdapterConfig()
    {
        if (isset($this->_adapter)) {
            return $this->_adapter->getConfig();
        }

        return array();
    }

    protected function _getAttributes($fieldConfig)
    {
        $fieldAttributes = array();

        if (is_object($this->_config)) {
            $fieldAttributes = $this->_config->getRaw()->attributes;
        }

        if ($fieldAttributes) {
            $fieldAttributes = $fieldAttributes->toArray();
        }

        if (isset($fieldConfig['attributes'])) {
            return $fieldConfig['attributes'] + $fieldAttributes;
        }

        return $fieldAttributes;
    }

    public function getProperties()
    {
        if (sizeof($this->_properties) <= 0) {
            return false;
        }

        return $this->_properties;
    }

    public function getCustomOrderField()
    {
        return null;
    }


    protected function _loadDecorators()
    {
        if (!$this->_config) {

            return $this;
        }

        $decorators = $this->_config->getRaw()->decorators;

        if (is_null($decorators)) {

            return $this;
        }

        $this->_decorators = array();
        foreach ($decorators as $decorator => $configuration) {

            $config = $configuration instanceof \Zend_Config ? $configuration->toArray() : $configuration;
            $this->_decorators[$decorator] = $config;
        }

        return $this;
    }

    public function getDecorators()
    {
        return $this->_decorators;
    }

    /*
     * Filtra (y adecua) el valor del campo antes del setter
     *
     */
    public function filterValue($value)
    {
        if ($this->_column->isMultilang()) {

            $retValue = array();
            foreach ($value as $lang => $_value) {
                $retValue[$lang] = $this->_filterValue($_value);
            }

        } else {

            $retValue = $this->_filterValue($value);

        }

        return $retValue;
    }

    protected function _filterValue($value)
    {
        if ($this->_isNullIfEmpty()) {
            if (empty($value)) {
                return NULL;
            }
        }

        return $value;
    }

    protected function _isNullIfEmpty()
    {
        return  isset($this->_properties['nullIfEmpty']) && (bool)$this->_properties['nullIfEmpty'];
    }

    /**
     * Prepara el valor de un campo, después del getter
     * @param mixed $value Valor devuelto por el getter del model
     * @return unknown
     */
    public function prepareValue($value)
    {
        return $value;
    }

    /**
     * Returns paths to extra javascript to be loaded
     * @return array
     */
    public function getExtraJavascript()
    {
        return $this->_js;
    }

    /**
     * Returns paths to extra css to be loaded
     * @return array
     */
    public function getExtraCss()
    {
        return $this->_css;
    }

    public function isSearchable()
    {
        return (bool)$this->_isSearchable;
    }

    //Si existe sortable en la configuración del campo en el model de yaml, lo devuelve. Sino, devuelve true.
    public function isSortable()
    {
        return $this->_isSortable;
    }

    public function getCustomErrors()
    {
        if (sizeof($this->_errorMessages) == 0) {
            return false;
        }

        return $this->_errorMessages;
    }

    /**
     * Factory method to create any of KlearMatrix_Model_Field_Abstract subtypes
     *
     * @param string $fieldType Name of Field Type to construct
     * @param KlearMatrix_Model_Column $column
     * @return KlearMatrix_Model_Field_Abstract
     */
    public static function create($fieldType, KlearMatrix_Model_Column $column)
    {
        $fieldClassName = 'KlearMatrix_Model_Field_' . ucfirst($fieldType);
        $field = new $fieldClassName($column);
        return $field;
    }
}

//EOF