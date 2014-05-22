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
        return $this->_formatTime($value);
    }

    public function filterValue($value)
    {
        return $this->_formatTime($value);
    }

    protected function _formatTime($value)
    {
        if (empty($value)) {
            return '';
        }

        $time = new Iron_Time($value);
        return $time->getFormattedString($this->_getSetting('timeFormat'));
    }
}
