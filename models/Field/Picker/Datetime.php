<?php

class KlearMatrix_Model_Field_Picker_Datetime extends KlearMatrix_Model_Field_Picker_Abstract
{
    protected function _setPlugin()
    {
        $this->_plugin = 'datetimepicker';
    }

    public function filterValue($value, $original)
    {
        $date = new Zend_Date($value, false, $this->getLocale());
        $date->setTimezone('UTC');

        return $date->toString(Zend_Date::ISO_8601);
    }

    protected function _getDateFormatFixed($locale)
    {
        return parent::_getDateFormatFixed($locale) . ' ' . $this->_timeFormats;
    }

}

//EOF