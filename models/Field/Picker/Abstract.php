<?php
class KlearMatrix_Model_Field_Picker_Abstract
{
    protected $_locale;
    protected $_jqLocale;

    protected $_mapperFormat = 'YYYY-MM-dd';

    protected $_css = array(
            "/js/plugins/datetimepicker/jquery-ui-timepicker-addon.css"
    );
    
    protected $_js = array(
            "/js/plugins/datetimepicker/jquery-ui-timepicker-addon.js"
    );
    
    protected $_settings = array(

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
        
        $currentKlearLanguage = Zend_Registry::get('currentSystemLanguage');
        $this->_locale = $currentKlearLanguage->getLocale();
        
        $this->_jqLocale = $currentKlearLanguage->getjQLocale();
        
        if (false === $this->_jqLocale) {
            Throw new \Exception('Klear locale not available in current picker');
        }
        
        $this->_js[] = "/js/plugins/datetimepicker/localization/jquery-ui-timepicker-".$this->_jqLocale.".js";
        return $this;
        
    }

    public function getLocale()
    {
        return $this->_locale;
    }

    public function setConfig($config)
    {
        if ($config->settings) {
            
            foreach ($config->settings as $key => $value) {

                if (array_key_exists($key, $this->_settings)) {

                    $this->_settings[$key] = $value;
                }
            }
        }

        return $this;
    }

    public function getConfig()
    {
        $filteredSettings = array();
        
        foreach ($this->_settings as $key => $val) {

            if (! is_null($val)) {

                 $filteredSettings[$key] = $val;
            }
        }
        return $filteredSettings;
    }

  

    /**
     * Devuelve el formato de fecha "Localizado" segun jQ; y fixeado para formato Zend_Date 
     */
    protected function _getDateFormatFixed($locale)
    {
        
        $_dateFormat = $this->_dateFormats[$locale];
        return str_replace(array('mm', 'yy'), array('MM','yyyy'), $_dateFormat);

    }
    
    
    public function getFormat($locale = null)
    {
        
        if (isset($this->_settings['format'])) {
            return $this->_setting['format'];
        }
        
        if (empty($locale)) {
            $locale = $this->_jqLocale;
        }

        if (isset($this->_dateFormats[$locale])) {
            return $this->_getDateFormatFixed($locale);
        }

        return null;
    }
   
    public function getMapperFormat()
    {
        return $this->_mapperFormat;    
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

//EOF