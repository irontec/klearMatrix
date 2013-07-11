<?php

class KlearMatrix_Model_Field_Textarea_Tinymce_Template
{

    protected $_jsController;
    protected $_jsControllerClass;

    protected $_defaultTemplate = 'simple';
    
    protected $_templateConfig;

    /*
     * TinyMCE settings
     */
    protected $_settings = array();
    
    protected $_toolBars = array();

    
    protected $_tinyMceToolbar = array(
            'core' => array(
                    'newdocument',
                    'bold',
                    'italic',
                    'underline',
                    'strikethrough',
                    'alignleft',
                    'aligncenter',
                    'alignright',
                    'alignjustify',
                    'styleselect',
                    'formatselect',
                    'fontselect',
                    'fontsizeselect',
                    'cut',
                    'copy',
                    'paste',
                    'bullist',
                    'numlist',
                    'outdent',
                    'indent',
                    'blockquote',
                    'undo',
                    'redo',
                    'removeformat',
                    'subscript',
                    'superscript'),
            'hr' => array('hr'),
            'link' => array(
                    'link', 
                    'unlink'),
            'image' => array('image'),
            'charmap' => array('charmap'),
            'print' => array('print'),
            'preview' => array('preview'),
            'anchor' => array('anchor'),
            'pagebreak' => array('pagebreak'),
            'spellchecker' => array('spellchecker'),
            'searchreplace' => array('searchreplace'),
            'visualblocks' => array('visualblocks'),
            'visualchars' => array('visualchars'),
            'code' => array('code'),
            'fullscreen' => array('fullscreen'),
            'insertdatetime' => array('inserttime'),
            'media' => array('media'),
            'nonbreaking' => array('nonbreaking'),
            'save' => array(
                    'save', 
                    'cancel'),
            'table' => array('table'),
            'directionality' => array(
                    'ltr', 
                    'rtl'),
            'emoticons' => array('emoticons'),
            'template' => array('template'),
            'textcolor' => array(
                    'forecolor', 
                    'backcolor')
            );
    
    protected $_tinyMceMenu = array(
            'core' => array('newdocument',
                    'undo',
                    'redo',
                    'visualaid',
                    'cut',
                    'copy',
                    'paste',
                    'selectall',
                    'bold',
                    'italic',
                    'underline',
                    'strikethrough',
                    'subscript',
                    'superscript',
                    'removeformat',
                    'formats'),
            'link' => array('link'),
            'image' => array('image'),
            'charmap' => array('charmap'),
            'paste' => array('pastetext'),
            'print' => array('print'),
            'preview' => array('preview'),
            'hr' => array('hr'),
            'anchor' => array('anchor'),
            'pagebreak' => array('pagebreak'),
            'spellchecker' => array('spellchecker'),
            'searchreplace' => array('searchreplace'),
            'visualblocks' => array('visualblocks'),
            'visualchars' => array('visualchars'),
            'code' => array('code'),
            'fullscreen' => array('fullscreen'),
            'insertdatetime' => array('insertdatetime'),
            'media' => array('media'),
            'nonbreaking' => array('nonbreaking'),
            'table' => array('inserttable',
                    'tableprops',
                    'deletetable',
                    'cell',
                    'row',
                    'column')
            );    
    

    
    
    protected $_tinyMcePlugins = array(
            'advlist',
            'anchor',
            'autolink',
            'autoresize',
            'autosave',
            'bbcode',
            'charmap',
            'code',
            'compat3x',
            'contextmenu',
            'directionality',
            'emoticons',
            'example',
            'example_dependency',
            'fullpage',
            'fullscreen',
            'hr',
            'image',
            'insertdatetime',
            'layer',
            'legacyoutput',
            'link',
            'lists',
            'media',
            'nonbreaking',
            'noneditable',
            'pagebreak',
            'paste',
            'preview',
            'print',
            'save',
            'searchreplace',
            'spellchecker',
            'tabfocus',
            'table',
            'template',
            'textcolor',
            'visualblocks',
            'visualchars',
            'wordcount'
            );

    protected $_config;

    public function __construct($config)
    {
        $this->_config = $config;
        
        if (!$template = $this->_config->getProperty('template')) {
            $template = $this->_defaultTemplate;
        }
        
        $templateFile = __DIR__ . '/Template/' . ucfirst($template) . '.yaml';
        
        if (!file_exists($templateFile)) {
            $templateFile = __DIR__ . '/Template/' . ucfirst($this->_defaultTemplate) . '.yaml';
        }
        $this->_templateConfig = new Zend_Config_Yaml(
            $templateFile,
            APPLICATION_ENV,
            array(
                "yamldecoder"=>"yaml_parse"
            )
        );

        $this->_jsController = "/js/plugins/tinymce/templates/jquery.tinymce.".strtolower($template).".js";
        $this->_jsControllerClass = ucfirst($template);

        $this->_settings = array_merge(
            $this->_templateConfig->toArray(),
            $this->_config->getProperty('settings')->toArray()
        );
        
    }
    
    public function getJsController()
    {
        return $this->_jsController;
    }

    public function getJsControllerClass()
    {
        return $this->_jsControllerClass;
    }

    protected function _loadBars()
    {
        $toolbarCounter = 1;
        while (true) {
            if (!isset($this->_settings['toolbar' . $toolbarCounter])) {
                break;
            }
            $this->_toolBars[$toolbarCounter] = array();
            $toolbarCounter++;
        }

        foreach ($this->_toolBars as $barIndex => $bar) {
            $this->_toolBars[$barIndex] = explode(" ", $this->_settings['toolbar' . $barIndex]);
            unset($this->_settings['toolbar' . $barIndex]);
        } 
    }

    public function getButtonsBar()
    {
        $this->_loadBars();
        $this->_removeButtons();
        $this->_addButtons();
        $this->_addBar();
        return $this->_toolBars;
    }

    protected function _removeButtons()
    {
        if ($this->_config->getProperty('removeButtons')) {
            foreach ($this->_toolBars as $barIndex => $bar) {
                foreach ($this->_config->getProperty('removeButtons') as $button) {
                    if ($index = array_search($button, $bar)) {
                        unset($this->_toolBars[$barIndex][$index]);
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
            foreach ($this->_toolBars as $barIndex => $bar) {
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
                    $this->_toolBars[$barIndex] = $bar;
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
            $this->_toolBars[$order] = $tmpBar;
        }
    }

    public function getSettings() 
    {
        $buttonsBars = $this->getButtonsBar();
        foreach ($buttonsBars as $index => $set) {
            $this->_settings['toolbar' . ($index)] = implode(',', $set);
        }
        return $this->_settings;
    }

}

//EOF