<?php
// TODO: Crear clase abstracta
class KlearMatrix_Model_Field_Picker_Time extends KlearMatrix_Model_Field_Picker_Abstract
{
    protected $_config;

    protected $_css = array(
        "/js/plugins/datetimepicker/jquery-ui-timepicker-addon.css"
    );

    protected $_js = array(
        "/js/plugins/datetimepicker/jquery-ui-timepicker-addon.js"
    );

    public function __construct()
    {
        parent::__construct();
        $this->_settings['timeFormat'] = $this->getFormat();
    }

    public function setConfig($config)
    {
        parent::setConfig($config);

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
            "plugin" => 'timepicker',
            "settings" => $baseSettings,
        );

        return $config;
    }

    public function getPhpFormat()
    {
        return 'H:i:s';
    }

    public function getFormat()
    {
        return 'hh:mm:ss';
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