<?php
class KlearMatrix_Model_Field_Picker_Time extends KlearMatrix_Model_Field_Picker_Abstract
{
    protected function _init()
    {
        $this->_includeTimepicker();
    }

    protected function _setPlugin()
    {
        $this->_plugin = 'timepicker';
        $this->_setPickerTimeFormat();
    }

    public function prepareValue($value)
    {
        if ($value instanceof \DateTime) {
            return $value->format('H:i:s');
        }

        return $value;
    }

    public function filterValue($value)
    {
        return $this->_formatTime($value);
    }

    protected function _formatTime($value)
    {
        return $value
            ? new Datetime('0000-00-00 ' . $value)
            : null;
    }
}
