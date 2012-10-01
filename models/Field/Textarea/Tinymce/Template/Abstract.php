<?php

abstract class KlearMatrix_Model_Field_Textarea_Tinymce_Template_Abstract
{

    protected $_jsController;
    protected $_jsControllerClass;

    /*
     * TinyMCE settings
     */
    protected $_tinyTemplate = 'advanced';
    protected $_tinyToolBarLocation = 'top';
    protected $_tinyToolBarAlign = 'left';
    protected $_tinyStatusBarLocation = 'bottom';
    protected $_tinyResizing = true;
    protected $_bars = array();

    protected $_tinyMceButtons = array(
            'save',
            'newdocument',
            'bold',
            'italic',
            'underline',
            'strikethrough',
            'justifyleft',
            'justifycenter',
            'justifyright',
            'justifyfull',
            'styleselect',
            'formatselect',
            'fontselect',
            'fontsizeselect',
            'cut',
            'copy',
            'paste',
            'pastetext',
            'pasteword',
            'search',
            'replace',
            'bullist',
            'numlist',
            'outdent',
            'indent',
            'blockquote',
            'undo',
            'redo',
            'link',
            'unlink',
            'anchor',
            'image',
            'cleanup',
            'help',
            'code',
            'insertdate',
            'inserttime',
            'preview',
            'forecolor',
            'backcolor',
            'tablecontrols',
            'hr',
            'removeformat',
            'visualaid',
            'sub',
            'sup',
            'charmap',
            'emotions',
            'iespell',
            'media',
            'advhr',
            'print',
            'ltr',
            'rtl',
            'fullscreen',
            'insertlayer',
            'moveforward',
            'movebackward',
            'absolute',
            'styleprops',
            'cite',
            'abbr',
            'acronym',
            'del',
            'ins',
            'attribs',
            'visualchars',
            'nonbreaking',
            'template',
            'pagebreak');

    protected $_tinyMcePlugins = array(
            'autolink',
            'lists',
            'pagebreak',
            'style',
            'layer',
            'table',
            'save',
            'advhr',
            'advimage',
            'advlink',
            'emotions',
            'iespell',
            'inlinepopups',
            'insertdatetime',
            'preview',
            'media',
            'searchreplace',
            'print',
            'contextmenu',
            'paste',
            'directionality',
            'fullscreen',
            'noneditable',
            'visualchars',
            'nonbreaking',
            'xhtmlxtras',
            'template',
            'advlist');

    protected $_config;

    public function __construct($config)
    {
        $this->_config = $config;
    }

    public function getJsController()
    {
        return $this->_jsController;
    }

    public function getJsControllerClass()
    {
        return $this->_jsControllerClass;
    }

    public function getTinyTemplate()
    {
        if (!$ret = $this->_config->getProperty('tinyTemplate')) {
            $ret = $this->_tinyTemplate;
        }
        return $ret;
    }

    public function getTinyToolBarLocation()
    {
        if (!$ret = $this->_config->getProperty('tinyToolBarLocation')) {
            $ret = $this->_tinyToolBarLocation;
        }
        return $ret;
    }

    public function getTinyToolBarAlign()
    {
        if (!$ret = $this->_config->getProperty('tinyToolBarAlign')) {
            $ret = $this->_tinyToolBarAlign;
        }
        return $ret;
    }

    public function getTinyStatusBarLocation()
    {
        if (!$ret = $this->_config->getProperty('tinyStatusBarLocation')) {
            $ret = $this->_tinyStatusBarLocation;
        }
        return $ret;
    }

    public function getTinyResizing()
    {
        if (!$ret = $this->_config->getProperty('tinyResizing')) {
            $ret = $this->_tinyResizing;
        }
        return $ret;
    }

    public function getTinyPlugins()
    {
        return implode(',', $this->_tinyMcePlugins);
    }

    public function getButtonsBar()
    {
        $this->_loadBars();
        $this->_removeButtons();
        $this->_addButtons();
        $this->_addBar();
        return $this->_bars;
    }

    protected function _removeButtons()
    {
        if ($this->_config->getProperty('removeButtons')) {
            foreach ($this->_bars as $barIndex => $bar) {
                foreach ($this->_config->getProperty('removeButtons') as $button) {
                    if ($index = array_search($button, $bar)) {
                        unset($this->_bars[$barIndex][$index]);
                    }
                }
            }
        }
    }

    protected function _addButtons()
    {
        if ($config = $this->_config->getProperty('addButtons')) {
            $bConfig = new Klear_Model_ConfigParser;
            $bConfig->setConfig($config);

            $order = $bConfig->getProperty('order');
            $buttons = $bConfig->getProperty('buttons');
            foreach ($this->_bars as $barIndex => $bar) {
                if ($order == $barIndex) {
                    if ($bConfig->getProperty('method') == 'append') {
                        foreach ($buttons as $button) {
                            array_push($bar, $button);
                        }
                    }
                    if ($bConfig->getProperty('method') == 'prepend') {
                        foreach ($buttons as $button) {
                            array_unshift($bar, $button);
                        }
                    }
                    $this->_bars[$barIndex] = $bar;
                }
            }
        }
    }

    protected function _addBar()
    {
        if ($config = $this->_config->getProperty('addBar')) {
            $bConfig = new Klear_Model_ConfigParser;
            $bConfig->setConfig($config);

            $order = $bConfig->getProperty('order');
            $buttons = $bConfig->getProperty('buttons');
            $tmpBar = array();
            foreach ($buttons as $button) {
                array_push($tmpBar, $button);
            }
            $this->_bars[$order] = $tmpBar;
        }
    }

}

//EOF