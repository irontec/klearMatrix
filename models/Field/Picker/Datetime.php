<?php
// TODO: Crear clase abstracta
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
        return $this;
    }

    public function getConfig() {

        $baseSettings = parent::getConfig();

        $config = array(
                    "plugin"=>'datetimepicker',
                    "settings" => $baseSettings,
                );

         return $config;
    }

    public function getPhpDateFormat()
    {
        return str_replace(array('mm', 'yy'), array('MM','yyyy'), $this->getFormat());
    }

    public function getDateFormat($locale = null)
    {
        return parent::getFormat();
    }

    public function getExtraJavascript() {
        return $this->_js;
    }

    public function getExtraCss() {
        return $this->_css;

    }
}