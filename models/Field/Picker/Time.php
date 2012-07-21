<?php

class KlearMatrix_Model_Field_Picker_Time extends KlearMatrix_Model_Field_Picker_Abstract
{
    protected $_config;

    protected $_mapperFormat = 'HH:mm:ss';

    public function __construct()
    {
        parent::__construct();
    }

    public function setConfig($config)
    {
        parent::setConfig($config);

        return $this;
    }

    public function init()
    {
        //TODO: lang global getter (lander donde andaba eso¿)

        return $this;
    }

    public function getConfig()
    {
        $baseSettings = parent::getConfig();

        $config = array(
            "plugin" => 'timepicker',
            "settings" => $baseSettings,
        );

        return $config;
    }

    public function getFormat($locale = null)
    {
        return $this->_timeFormats;
    }

    public function getExtraJavascript()
    {
        return $this->_js;
    }

    public function getExtraCss()
    {
        return $this->_css;
    }


    public function filterValue($value, $original)
    {

        if (empty($value)) {
            return '';
        }

        $time = new Iron_Time($value);
        return $time->getFormattedString($this->_mapperFormat);

    }


    /**
     * @param mixed $value Valor devuelto por el getter del model
     * @param object $model Modelo cargado
     * @return unknown
     */
    public function prepareValue($value, $model)
    {

        if (empty($value)) {
            return '';
        }

        $time = new Iron_Time($value);

        return $time->getFormattedString($this->_timeFormats);

    }


}

//EOF