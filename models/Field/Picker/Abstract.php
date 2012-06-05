<?php
class KlearMatrix_Model_Field_Picker_Abstract
{
    private $_locale;

    protected $_css = array();
    protected $_js = array();

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
        'timeFormat' => null,
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

    protected $_dateFormats = array(
        'af' => 'dd/mm/yy',
        'ar' => 'dd/mm/yy',
        'ar-DZ' => 'dd/mm/yy',
        'az' => 'dd.mm.yy',
        'bg' => 'dd.mm.yy',
        'bs' => 'dd.mm.yy',
        'ca' => 'dd/mm/yy',
        'cs' => 'dd.mm.yy',
        'cy-GB' => 'dd/mm/yy',
        'da' => 'dd-mm-yy',
        'de' => 'dd.mm.yy',
        'el' => 'dd/mm/yy',
        'en-AU' => 'dd/mm/yy',
        'en-GB' => 'dd/mm/yy',
        'en-NZ' => 'dd/mm/yy',
        'eo' => 'dd/mm/yy',
        'es' => 'dd/mm/yy',
        'et' => 'dd.mm.yy',
        'eu' => 'yy/mm/dd',
        'fa' => 'yy/mm/dd',
        'fi' => 'dd.mm.yy',
        'fo' => 'dd-mm-yy',
        'fr' => 'dd/mm/yy',
        'fr-CH' => 'dd.mm.yy',
        'ge' => 'dd-mm-yy',
        'gl' => 'dd/mm/yy',
        'he' => 'dd/mm/yy',
        'hi' => 'mm/dd/yy',
        'hr' => 'dd.mm.yy.',
        'hu' => 'yy.mm.dd.',
        'hy' => 'dd.mm.yy',
        'id' => 'dd/mm/yy',
        'is' => 'dd/mm/yy',
        'it' => 'dd/mm/yy',
        'ja' => 'yy/mm/dd',
        'kk' => 'dd.mm.yy',
        'km' => 'dd-mm-yy',
        'ko' => 'yy-mm-dd',
        'lb' => 'dd.mm.yy',
        'lt' => 'yy-mm-dd',
        'lv' => 'dd-mm-yy',
        'mk' => 'dd.mm.yy',
        'ml' => 'dd/mm/yy',
        'ms' => 'dd/mm/yy',
        'nl' => 'dd-mm-yy',
        'nl-BE' => 'dd/mm/yy',
        'no' => 'dd.mm.yy',
        'pl' => 'dd.mm.yy',
        'pt' => 'dd/mm/yy',
        'pt-BR' => 'dd/mm/yy',
        'rm' => 'dd/mm/yy',
        'ro' => 'dd.mm.yy',
        'ru' => 'dd.mm.yy',
        'sk' => 'dd.mm.yy',
        'sl' => 'dd.mm.yy',
        'sq' => 'dd.mm.yy',
        'sr' => 'dd/mm/yy',
        'sr-SR' => 'dd/mm/yy',
        'sv' => 'yy-mm-dd',
        'ta' => 'dd/mm/yy',
        'th' => 'dd/mm/yy',
        'tj' => 'dd.mm.yy',
        'tr' => 'dd.mm.yy',
        'uk' => 'dd/mm/yy',
        'vi' => 'dd/mm/yy',
        'zh-CN' => 'yy-mm-dd',
        'zh-HK' => 'dd-mm-yy',
        'zh-TW' => 'yy/mm/dd',
    );

    protected $_timeFormats = 'hh:mm:ss';

    public function __construct()
    {
        $bootstrap = \Zend_Controller_Front::getInstance()->getParam('bootstrap');

        if (is_null($bootstrap)) {

            $conf = new \Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini',APPLICATION_ENV);
            $conf = (Object) $conf->toArray();

        } else {

            $conf = (Object) $bootstrap->getOptions();
        }

        if (isset($options->defaultLanguageZendRegistryKey)) {

            $langKey = $options->defaultLanguageZendRegistryKey;

        } else {

            $langKey = 'defaultLang';
        }

        $this->_locale = Zend_Registry::get($langKey);
    }

    protected function getLocale()
    {
        return $this->_locale;
    }

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

    public function getPhpFormat()
    {
        return $this->getFormat();
    }

    public function getFormat($locale = null)
    {
        if (empty($locale)) {

            $locale = $this->_locale;
        }

        if (isset($this->_dateFormats[$locale])) {

            return $this->_dateFormats[$locale];
        }

        return null;
    }

    public function getExtraJavascript() {
        return $this->_js;
    }

    public function getExtraCss() {
        return $this->_css;
    }
}
