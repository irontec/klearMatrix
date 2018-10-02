<?php
/*
 * TODO: Abstracta de File
 */
class KlearMatrix_Model_Field_File_Fso
{
    protected $_config;

    protected $_fileName;
    protected $_fileSize;
    protected $_mimeType;

    protected $_defaultOptionAttributtes = array(
        'type' => 'command',
        'class' => ''
    );

    protected $_js = array(
        "/js/plugins/jquery.jplayer.min.js",
        "/js/plugins/qq-fileuploader.js",
        "/js/plugins/jquery.klearmatrix.file.js"
    );

    protected $_css = array(
        "/css/jquery.jplayer.css",
        "/css/qq-fileuploader.css"
    );

    public function __construct($config)
    {
        $this->setConfig($config);
    }

    public function setConfig($config)
    {
        $this->_config = $config;

        return $this;
    }

    protected function _getAllowedExtensions()
    {
        $exts = array();

        if (!isset($this->_config->extensions)) {

            return array();
        }

        foreach ($this->_config->extensions as $ext) {

            $exts[] = $ext;
        }
        return $exts;
    }

    protected function _getSizeLimit()
    {
        if (isset($this->_config->size_limit)) {

            return $this->_config->size_limit;
        }

        return null;
    }

    protected function _parseOptions()
    {
        $ret = array();
        foreach ($this->_config->options as $optionIndex => $option) {
            $ret[$optionIndex] = $this->_parseOption($option);
        }

        return $ret;

    }

    protected function _parseOption($option)
    {
        if (!$option instanceof Zend_Config) {
            return $option;
        }

        $ret = array();
        // Attributes to be translated
        $textAttrs = array('title','text');

        $parser = new Klear_Model_ConfigParser;
        $parser->setConfig($option);

        foreach ($option as $k => $v) {
            $v; //Avoid PMD UnusedLocalVariable warning
            $data = $parser->getProperty($k);
            if (is_object($data) && method_exists($data, 'toArray')) {
                $data = $data->toArray();
            }

            if (in_array($k, $textAttrs)) {
                $data = Klear_Model_Gettext::gettextCheck($data);
            }
            $ret[$k] = $data;
        }

        $ret += $this->_fillWithDefaultAttrs($ret);
        return $ret;
    }

    protected function _fillWithDefaultAttrs($curRet)
    {
        $ret = array();
        foreach ($this->_defaultOptionAttributtes as $attr => $value) {
            if (!isset($curRet[$attr])) {
                $ret[$attr] = $value;
            }
        }
        return $ret;
    }

    public function getConfig()
    {
        $ret = array();
        $ret['allowed_extensions'] = $this->_getAllowedExtensions();
        $ret['size_limit'] = $this->_getSizeLimit();

        if ($this->_config->options) {
            $ret['options'] = $this->_parseOptions();
        }
        return $ret;
    }

    public function getFetchMethod($dbName)
    {
        return 'get' . ucfirst($dbName) . 'Path';
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