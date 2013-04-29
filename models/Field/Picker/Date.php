<?php
// TODO: Crear clase abstracta
class KlearMatrix_Model_Field_Picker_Date extends KlearMatrix_Model_Field_Picker_Abstract
{
    public function __init()
    {
        $format = $this->getFormat($this->getLocale());
        if ($format) {
            $this->_setSetting('dateFormat', $format);
        }
    }

    protected function _setPlugin()
    {
        $this->_plugin = 'datepicker';
    }

    public function filterValue($value)
    {
        if (empty($value)) return NULL;
        
        $date = new Zend_Date();
        $date->setTimeZone('UTC');
        $date->setDate($value, null, $this->getLocale());
        $date->setHour(0)->setMinute(0)->setSecond(0);

        return $date->toString(Zend_Date::ISO_8601);
    }
}
