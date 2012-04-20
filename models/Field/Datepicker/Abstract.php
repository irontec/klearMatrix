<?php
class KlearMatrix_Model_Field_Datepicker_Abstract
{

    protected $_settings = array(

        'disabled' => null,
        'altField' => null,
        'altFormat' => null,
        'appendText' => null,
        'autoSize' => null,
        'buttonImage' => null,
        'buttonImageOnly' => null,
        'buttonText' => null,
        'calculateWeek' => null,
        'changeMonth' => null,
        'changeYear' => null,
        'closeText' => null,
        'constrainInput' => null,
        'currentText' => null,
        'dateFormat' => null,
        'dayNames' => null,
        'dayNamesMin' => null,
        'dayNamesShort' => null,
        'defaultDate' => null,
        'duration' => null,
        'firstDay' => null,
        'gotoCurrent' => null,
        'hideIfNoPrevNext' => null,
        'isRTL' => null,
        'maxDate' => null,
        'minDate' => null,
        'monthNames' => null,
        'monthNamesShort' => null,
        'navigationAsDateFormat' => null,
        'nextText' => null,
        'numberOfMonths' => null,
        'prevText' => null,
        'selectOtherMonths' => null,
        'shortYearCutoff' => null,
        'showAnim' => null,
        'showButtonPanel' => null,
        'showCurrentAtPos' => null,
        'showMonthAfterYear' => null,
        'showOn' => null,
        'showOptions' => null,
        'showOtherMonths' => null,
        'showWeek' => null,
        'stepMonths' => null,
        'weekHeader' => null,
        'yearRange' => null,
        'yearSuffix' => null
    );

    public function setConfig($config) {

        foreach ($config->settings as $key => $value) {

            if (array_key_exists($key, $this->_settings)) {

                $this->_settings[$key] = $value;
            }
        }

        return $this;
    }

    public function getConfig() {

        $filteredSettings = array();

        foreach ($this->_settings as $key => $val) {

            if (! is_null($val)) {

                 $filteredSettings[$key] = $val;
            }
        }

        return $filteredSettings;
    }
}
