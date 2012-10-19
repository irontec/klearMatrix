<?php

abstract class KlearMatrix_Model_Field_Abstract
{

    /**
     * @var KlearMatrix_Model_Column
     */
    protected $_column;
    protected $_config;
    protected $_canBeSearched = true;
    protected $_canBeSorted = true;

    static protected $_propertyMaster = array(
            "required",
            "pattern",
            "placeholder",
            "nullIfEmpty",
            "expandable",
            "defaultValue" // Valor por defecto en caso de new
            );

    protected $_properties = array();

    /*valid error index */
    protected $_errorIndex = array('patternMismatch', 'rangeOverflow', 'rangeUnderflow', 'stepMismatch', 'tooLong', 'typeMismatch', 'valueMissing');
    protected $_errorMessages = array();

    public function setColumn($column)
    {
        $this->_column = $column;
        return $this;
    }

    public function getColumn($column)
    {
        return $this->_column;
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
                $this->_errorMessages[$errorIndex] = $errorConfig->getProperty($errorIndex);
            }
        }
    }

    /**
     * Dejar este método vacio, se invocara siempre que se genera desde Column
     */
    public function init()
    {
        $this->_config = $this->_column->getKlearConfig();

        if (is_object($this->_config)) {

            foreach (self::$_propertyMaster as $_prop) {

                $this->_properties[$_prop] = $this->_config->getProperty($_prop);
            }

            $this->_parseErrorMessages();
        }

        return $this;
    }

    public function getConfig()
    {
        return false;
    }

    public function getProperties()
    {
        if (sizeof($this->_properties) <= 0) {
            return false;
        }

        return $this->_properties;
    }

    /*
     * Filtra (y adecua) el valor del campo antes del setter
     *
     */
    public function filterValue($value, $original)
    {

        if (isset($this->_properties['nullIfEmpty'])
            && (bool)$this->_properties['nullIfEmpty']) {

                if (empty($value)) {

                    return NULL;
                }
        }

        return $value;
    }

    /*
     * Prepara el valor de un campo, después del getter
     */
    /**
     * @param mixed $value Valor devuelto por el getter del model
     * @param object $model Modelo cargado
     * @return unknown
     */
    public function prepareValue($value, $model)
    {
        return $value;
    }

    public function getExtraJavascript()
    {
        return false;
    }

    public function getExtraCss()
    {
        return false;
    }

    public function canBeSearched()
    {
        return $this->_canBeSearched;
    }

    //Si existe sortable en la configuración del campo en el model de yaml, lo devuelve. Sino, devuelve true.
    public function canBeSorted()
    {
        if (is_object($this->_config)
            && $this->_config->exists("sortable")) {

                return $this->_config->getProperty("sortable");
        }

        return true;
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
        $field = new $fieldClassName;
        $field->setColumn($column)->init();

        return $field;
    }

    /**
     * Constructor must not be directly called from outside. Use the factory method instead
     */
    private function __construct()
    {}
}

//EOF