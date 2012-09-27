<?php
// TODO: Crear clase abstracta
class KlearMatrix_Model_Field_Picker_Date extends KlearMatrix_Model_Field_Picker_Abstract
{
    protected $_config;

    public function __construct()
    {
        parent::__construct();
        $this->_settings['dateFormat'] = $this->getFormat($this->getLocale());
    }

    public function setConfig($config)
    {
        parent::setConfig($config);
        $this->_config = $config;

        return $this;
    }

    public function init()
    {
        return $this;
    }

    public function filterValue($value, $original)
    {
        $date = new Zend_Date();
        $date->setTimeZone('UTC');
        $date->setDate($value, null, $this->getLocale());
        $date->setHour(0)->setMinute(0)->setSecond(0);

        return $date->toString(Zend_Date::ISO_8601);
    }

    public function getConfig()
    {
        $baseSettings = parent::getConfig();


        $config = array(
            "plugin"=>'datepicker',
            "settings" => $baseSettings,
        );

        return $config;
    }

}

//EOF
