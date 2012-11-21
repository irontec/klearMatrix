<?php

class KlearMatrix_Model_Field_Picker_Time extends KlearMatrix_Model_Field_Picker_Abstract
{
    protected function _setPlugin()
    {
        $this->_plugin = 'timepicker';
    }


    protected function _init()
    {
        $this->_availableSettings[] = 'showSeconds';
    }

    public function filterValue($value)
    {
        if (empty($value)) {
            return '';
        }

        $time = new Iron_Time($value);
        return $time->getFormattedString($this->_timeFormats);
    }

    /**
     * @param mixed $value Valor devuelto por el getter del model
     * @param object $model Modelo cargado
     * @return unknown
     */
    public function prepareValue($value)
    {
        if (empty($value)) {
            return '';
        }

        $time = new Iron_Time($value);

        return $time->getFormattedString($this->_timeFormats);
    }
}
