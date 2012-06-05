<?php

class KlearMatrix_Model_Field_Picker_Datetime extends KlearMatrix_Model_Field_Picker_Abstract
{
    protected $_config;

    protected $_css = array(
        "/js/plugins/datetimepicker/jquery-ui-timepicker-addon.css"
    );

    protected $_js = array(
        "/js/plugins/datetimepicker/jquery-ui-timepicker-addon.js"        
    );

    public function setConfig($config)
    {
        parent::setConfig($config);
        $this->_config = $config;

        return $this;
    }

    public function init()
    {
        //TODO: lang global getter (lander donde andaba eso¿)
        $lang = 'es';
        $this->_js[] = "/js/plugins/datetimepicker/localization/jquery-ui-timepicker-".$lang.".js";
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

    public function getPhpDateFormat()
    {
        $ret = str_replace(array('mm', 'yy'), array('MM','yyyy'), $this->getFormat());

        return $ret;
    }

    public function getDateFormat($locale = null)
    {
        return parent::getFormat();
    }

    public function getExtraJavascript()
    {
        return $this->_js;
    }

    public function getExtraCss()
    {
        return $this->_css;
    }

}

//EOF