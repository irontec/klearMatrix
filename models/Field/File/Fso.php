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

    protected $_js = array(
        "/js/plugins/jquery.jplayer.min.js",
        "/js/plugins/qq-fileuploader.js"
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

    public function getConfig()
    {
        $ret = array();
        $ret['allowed_extensions'] = $this->_getAllowedExtensions();
        $ret['size_limit'] = $this->_getSizeLimit();

        $defaultOptions = array(
            'type' => 'command',
            'class' => ''
        );

        if ($fileOptions = $this->_config->options) {

            $ret['options'] = array();
            $parser = new Klear_Model_ConfigParser;
            $parser->setConfig($fileOptions);

            foreach ($fileOptions as $option => $opObject) {

                if ($opObject instanceof Zend_Config) {

                    $parser->setConfig($opObject);

                    foreach ($opObject as $k => $v) {

                        $ret['options'][$option][$k] = $parser->getProperty($k); //$v;
                    }
                } else {

                    $ret['options'][$option] = $opObject;
                }

                foreach ($defaultOptions as $key => $value) {

                    if (is_array($ret['options'][$option]) &&
                            !isset($ret['options'][$option][$key])) {
                        $ret['options'][$option][$key] = $value;
                    }
                }
            }
        }

        return $ret;
    }

    public function getFetchMethod($dbName)
    {
        return 'fetch' . $dbName;
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