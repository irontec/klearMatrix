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

        if (!($value instanceof \Datetime)) {
            return $value;
        }

        $value->setTimezone(
            new \DateTimeZone(
                date_default_timezone_get()
            )
        );

        return $value->format('Y-m-d H:i:s');
    }

    public function filterValue($value)
    {
        $dateTime = new \Datetime(
            $value
        );

        $dateTime->setTimezone(new \DateTimeZone('UTC'));

        return $dateTime;
    }
}
