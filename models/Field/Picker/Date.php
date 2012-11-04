<?php
// TODO: Crear clase abstracta
class KlearMatrix_Model_Field_Picker_Date extends KlearMatrix_Model_Field_Picker_Abstract
{
    public function __construct($config)
    {
        parent::__construct($config);
        $this->_setSetting('dateFormat', $this->getFormat($this->getLocale()));
    }

    protected function _setPlugin()
    {
        $this->_plugin = 'datepicker';
    }

    public function filterValue($value)
    {
        $date = new Zend_Date();
        $date->setTimeZone('UTC');
        $date->setDate($value, null, $this->getLocale());
        $date->setHour(0)->setMinute(0)->setSecond(0);

        return $date->toString(Zend_Date::ISO_8601);
    }
}

//EOF
