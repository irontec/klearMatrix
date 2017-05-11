<?php
class KlearMatrix_Model_Field_Picker_Date extends KlearMatrix_Model_Field_Picker_Abstract
{
    protected function _setPlugin()
    {
        $this->_plugin = 'datepicker';
        $this->_setPickerDateFormat();
    }

    public function prepareValue($value)
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof \Datetime) {
            $value = $value->format('Y-m-d H:i:s');
        }

        $date = new Zend_Date();
        $date->setTimeZone('UTC');
        $date->setDate($value, 'yyyy-MM-dd');
        $date->setHour(0)->setMinute(0)->setSecond(0);

        return $date->toString($this->_getZendDateFormat());
    }

    public function filterValue($value)
    {
        if (empty($value)) {
            return null;
        }

        $date = new Zend_Date();
        $date->setTimeZone('UTC');
        $date->setDate($value, $this->_getZendDateFormat());
        $date->setHour(0)->setMinute(0)->setSecond(0);

        return $date->toString(Zend_Date::ISO_8601);
    }
}
