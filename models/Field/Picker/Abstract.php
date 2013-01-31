<?php
abstract class KlearMatrix_Model_Field_Picker_Abstract
{
    protected $_locale;
    protected $_jqLocale;

    protected $_css = array(
            "/js/plugins/datetimepicker/jquery-ui-timepicker-addon.css"
    );

    protected $_js = array(
            "/js/plugins/datetimepicker/jquery-ui-timepicker-addon.js"
    );

    protected $_availableSettings = array(
        'altField' ,
        'altFormat' ,
        'appendText' ,
        'autoSize' ,
        'buttonImage' ,
        'buttonImageOnly' ,
        'buttonText' ,
        'calculateWeek' ,
        'changeMonth' ,
        'changeYear' ,
        'closeText' ,
        'constrainInput' ,
        'currentText' ,
        'dateFormat' ,
        'timeFormat' ,
        'dayNames' ,
        'dayNamesMin' ,
        'dayNamesShort' ,
        'defaultDate' ,
        'duration' ,
        'firstDay' ,
        'gotoCurrent' ,
        'hideIfNoPrevNext' ,
        'isRTL' ,
        'maxDate' ,
        'minDate' ,
        'monthNames' ,
        'monthNamesShort' ,
        'navigationAsDateFormat' ,
        'nextText' ,
        'numberOfMonths' ,
        'prevText' ,
        'selectOtherMonths' ,
        'shortYearCutoff' ,
        'showAnim' ,
        'showButtonPanel' ,
        'showCurrentAtPos' ,
        'showMonthAfterYear' ,
        'showOn' ,
        'showOptions' ,
        'showOtherMonths' ,
        'showWeek' ,
        'stepMonths' ,
        'weekHeader' ,
        'yearRange' ,
        'yearSuffix'
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

    protected $_timeFormats = 'HH:mm:ss';

    protected $_settings = array();
    protected $_plugin;

    public function __construct($config)
    {
        $currentKlearLanguage = Zend_Registry::get('currentSystemLanguage');
        $this->_locale = $currentKlearLanguage->getLocale();

        $this->_jqLocale = $currentKlearLanguage->getjQLocale();

        if (false === $this->_jqLocale) {
            throw new \Exception('Klear locale not available in current picker');
        }

        $this->_js[] = "/js/plugins/datetimepicker/localization/jquery-ui-timepicker-".$this->_jqLocale.".js";

        $this->_init();
        $this->_setConfig($config);
        $this->_setPlugin();
    }

    protected function _setConfig($config)
    {
        if ($config->settings) {

            foreach ($config->settings as $key => $value) {

                $this->_setSetting($key, $value);

            }
        }

        return $this;
    }

    abstract protected function _setPlugin();

    protected function _init()
    {
        // To be overriden by child objects
    }

    protected function _setSetting($key, $value)
    {
        if (in_array($key, $this->_availableSettings)) {
            $this->_settings[$key] = $value;
        }
        if (!$value) {
            var_dump(debug_backtrace());
        }
        return $this;
    }


    public function getLocale()
    {
        return $this->_locale;
    }

    protected function _hasSetting($key)
    {
        return isset($this->_settings[$key]);
    }

    protected function _getSetting($key)
    {
        return $this->_settings[$key];
    }

    public function getConfig()
    {
        return array(
            'settings' => $this->_settings,
            'plugin' => $this->_plugin
        );
    }

    /**
     * Devuelve el formato de fecha "Localizado" segun jQ; y fixeado para formato Zend_Date
     */
    public function getFormat($locale = null)
    {
        if ($this->_hasSetting('format')) {
            return $this->_getSetting('format');
        }

        if (empty($locale)) {
            $locale = $this->_jqLocale;
        }

        if (isset($this->_dateFormats[$locale])) {
            return $this->_getDateFormatFixed($locale);
        }

        return null;
    }

    protected function _getDateFormatFixed($locale)
    {
        $_dateFormat = $this->_dateFormats[$locale];
        return str_replace(array('mm', 'yy'), array('MM', 'yyyy'), $_dateFormat);
    }

    public function getExtraJavascript()
    {
        return $this->_js;
    }

    public function getExtraCss()
    {
        return $this->_css;
    }
}
