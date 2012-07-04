<?php

class KlearMatrix_Model_Field_Picker_Datetime extends KlearMatrix_Model_Field_Picker_Abstract
{
    protected $_config;

    protected $_mapperFormat = 'YYYY-MM-dd HH:mm:ss';

    
    
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
            "plugin"=>'datetimepicker',
            "settings" => $baseSettings,
        );

        return $config;
    }

    public function getFormat($locale = null)
    {
        
        if (isset($this->_settings['format'])) {
            return $this->_setting['format'];
        }
        
        if (empty($locale)) {
            $locale = $this->_jqLocale;
        }

        if (isset($this->_dateFormats[$locale])) {
            return $this->_getDateFormatFixed($locale) . ' ' . $this->_timeFormats;
        }

        return null;
    }

}

//EOF