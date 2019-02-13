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

        $format = $this->toPhpFormat(
            $this->_getSetting('dateFormat'),
            $this->_getSetting('timeFormat')
        );

        $value->setTimezone(
            new \DateTimeZone(
                date_default_timezone_get()
            )
        );

        return $value->format($format);
    }

    public function filterValue($value)
    {
        if (!$value) {
            return;
        }

        $format = $this->toPhpFormat(
            $this->_getSetting('dateFormat'),
            $this->_getSetting('timeFormat')
        );

        $dateTime = \DateTime::createFromFormat(
            $format,
            $value
        );

        if (!$dateTime) {
            // Filters use mysql format
            $dateTime = \DateTime::createFromFormat(
                'Y-m-d H:i:s',
                $value
            );
        }

        $dateTime->setTimezone(new \DateTimeZone('UTC'));

        return $dateTime;
    }

    private function toPhpFormat(string $dateFormat, string $timeFormat)
    {
        $dateFormat = str_replace(
            ['yy', 'mm', 'dd'],
            ['Y',  'm',  'd'],
            $dateFormat
        );

        $timeFormat = str_replace(
            ['hh', 'mm', 'ss'],
            ['H',  'i',  's'],
            $timeFormat
        );

        return $dateFormat . ' ' . $timeFormat;
    }
}
