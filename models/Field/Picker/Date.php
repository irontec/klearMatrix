
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

        $value->setTimezone(
            new \DateTimeZone(
                date_default_timezone_get()
            )
        );

        $format = $this->toPhpFormat($this->_getSetting('dateFormat'));
        return $value->format($format);
    }

    public function filterValue($value)
    {
        if (empty($value)) {
            return null;
        }

        $dateTime = \DateTime::createFromFormat(
            'Y-m-d',
            $value
        );

        $dateTime->setTime(0,0,0);

        return $dateTime;
    }

    private function toPhpFormat(string $format)
    {
        return str_replace(
            ['yy', 'mm', 'dd'],
            ['Y', 'm', 'd'],
            $format
        );
    }
}
