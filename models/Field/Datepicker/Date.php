<?php
// TODO: Crear clase abstracta
class KlearMatrix_Model_Field_Datepicker_Date extends KlearMatrix_Model_Field_Datepicker_Abstract
{
    protected $_config;

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
                    "plugin"=>'datepicker',
                    "settings" => $baseSettings,
                );

         return $config;
    }
}