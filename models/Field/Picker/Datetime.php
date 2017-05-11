<?php
class KlearMatrix_Model_Field_Picker_Datetime extends KlearMatrix_Model_Field_Picker_Abstract
{
    protected function _init()
    {
        $this->_includeTimepicker();
    }

    protected function _setPlugin()
    {
        $this->_plugin = 'datetimepicker';
        $this->_setPickerDateFormat();
        $this->_setPickerTimeFormat();
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
        $date->setDate(substr($value, 0, 10), 'yyyy-MM-dd');
        $date->setTime(substr($value, -8), 'HH:mm:ss');
        $date->setTimezone(date_default_timezone_get());

        return $date->toString($this->_getZendDateTimeFormat());
    }

    public function filterValue($value)
    {
        if (empty($value)) {
            return null;
        }

        $date = new Zend_Date($value, $this->_getZendDateTimeFormat());
        $date->setTimezone('UTC');

        return $date->toString(Zend_Date::ISO_8601);
    }

    protected function _getZendDateTimeFormat()
    {
        return parent::_getZendDateFormat() . ' ' . $this->_getZendTimeFormat();
    }
}
