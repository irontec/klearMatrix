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

        if (!($value instanceof \Datetime)) {
            return $value;
        }

        return $value->format('Y-m-d');
    }

    public function filterValue($value)
    {
        if (empty($value)) {
            return null;
        }

        $dateTime = new \Datetime(
            $value
        );
        $dateTime->setTime(0,0,0);

        return $dateTime;
    }
}
