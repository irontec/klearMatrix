<?php
// TODO: Crear clase abstracta
class KlearMatrix_Model_Field_Picker_Date extends KlearMatrix_Model_Field_Picker_Abstract
{
    protected $_config;

    protected $_mapperFormat = 'YYYY-MM-dd';

    
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