<?php
// TODO: Crear clase abstracta
class KlearMatrix_Model_Field_Number_Spinner
{

    protected $_max = null;
    protected $_min = null;
    protected $_step = 1;
    protected $_preconfiguredValues = array();

    protected $_config;

    protected $_js = array(
        "/js/plugins/jquery.ui.spinner.js"
    );

    protected $_css = array(
        "/css/jquery.ui.spinner.css"
    );

    public function __construct(Zend_Config $config)
    {
        $this->setConfig($config);
        $this->init();
    }

    public function setConfig(Zend_Config $config)
    {
        $this->_config = $config;

        return $this;
    }

    public function init()
    {
        if (isset($this->_config->min)) {
            $this->_min = $this->_config->min;
        }

        if (isset($this->_config->max)) {
            $this->_max = $this->_config->max;
        }

        if (isset($this->_config->step)) {
            $this->_step = $this->_config->step;
        }

        if (isset($this->_config->preconfiguredValues)) {
            foreach ($this->_config->preconfiguredValues as $value) {
                $this->_preconfiguredValues[] = $value;
            }
        }

        return $this;
    }

    public function getExtraJavascript()
    {
        return $this->_js;
    }

    public function getExtraCss()
    {
        return $this->_css;
    }

    public function getConfig()
    {
        $ret = array(
            "plugin" => 'spinner',
            "settings" => array(
                'min' => $this->_min,
                'max' => $this->_max,
                'step' => $this->_step,
                'preconfiguredValues' => $this->_preconfiguredValues
            )
        );

        return $ret;
    }
}

//EOF