<?php 

class KlearMatrix_Model_Field_Textarea_Tinymce extends KlearMatrix_Model_Field_Textarea_Abstract
{
    protected $_mainConfig = array();
    
    protected $_css = array();
    
    /**
     * 
     * @var Klear_Model_Language
     */
    protected $_lang;
    
    protected $_js = array(
            "/js/plugins/tinymce/jscripts/tiny_mce/jquery.tinymce.js",
            "/js/plugins/jquery.ui.kleartinymce.js"
            
    );
    
    protected $_jsPluginPath = 'klearMatrix/js/plugins/tinymce/jscripts/tiny_mce';
    
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
    
    protected function _loadMainConfig()
    {
        // base url
        $front = Zend_Controller_Front::getInstance();
        $baseUrl = $front->getBaseUrl();
        $this->_jsPluginPath = $baseUrl . DIRECTORY_SEPARATOR . $this->_jsPluginPath;
        
        // Language Settings
        $this->_lang = Zend_Registry::get('currentSystemLanguage');
        $this->_mainConfig['language'] = $this->_lang->getIden();
        
        // Location of TinyMCE script
        $this->_mainConfig['script_url'] = $this->_jsPluginPath . '/tiny_mce.js';
        
        // Plugins
        $this->_mainConfig['plugins'] = implode(',', $this->_tinyMcePlugins);
        
        // Theme
        $this->_mainConfig['theme'] = 'advanced';
        
        
        $this->_mainConfig['theme_advanced_buttons1'] = implode(',', array(
                'bold',
                'italic',
                'underline',
                'strikethrough',
                '|',
                'bullist',
                'numlist',
                'blockquote',
                '|',
                'justifyleft',
                'justifycenter',
                'justifyright',
                '|',
                'link',
                'unlink',
                'pagebreak',
                'fullscreen'
        ));

        /*$this->_mainConfig['theme_advanced_buttons1'] = implode(',', array(
                'save',
                'newdocument',
                '|',
                'bold',
                'italic',
                'underline',
                'strikethrough',
                '|',
                'justifyleft',
                'justifycenter',
                'justifyright',
                'justifyfull',
                'styleselect',
                'formatselect',
                'fontselect',
                'fontsizeselect'));
        $this->_mainConfig['theme_advanced_buttons2'] = implode(',', array(
                'cut',
                'copy',
                'paste',
                'pastetext',
                'pasteword',
                '|',
                'search',
                'replace',
                '|',
                'bullist',
                'numlist',
                '|',
                'outdent',
                'indent',
                'blockquote',
                '|',
                'undo',
                'redo',
                '|',
                'link',
                'unlink',
                'anchor',
                'image',
                'cleanup',
                'help',
                'code',
                '|',
                'insertdate',
                'inserttime',
                'preview',
                '|',
                'forecolor',
                'backcolor'));
        $this->_mainConfig['theme_advanced_buttons3'] = implode(',', array(
                'tablecontrols',
                '|',
                'hr',
                'removeformat',
                'visualaid',
                '|',
                'sub',
                'sup',
                '|',
                'charmap',
                'emotions',
                'iespell',
                'media',
                'advhr',
                '|',
                'print',
                '|',
                'ltr',
                'rtl',
                '|',
                'fullscreen'));
        $this->_mainConfig['theme_advanced_buttons4'] = implode(',', array(
                'insertlayer',
                'moveforward',
                'movebackward',
                'absolute',
                '|',
                'styleprops',
                '|',
                'cite',
                'abbr',
                'acronym',
                'del',
                'ins',
                'attribs',
                '|',
                'visualchars',
                'nonbreaking',
                'template',
                'pagebreak'));*/        
        

        $this->_mainConfig['theme_advanced_toolbar_location'] = 'top';
        $this->_mainConfig['theme_advanced_toolbar_align'] = 'left';
        $this->_mainConfig['theme_advanced_statusbar_location'] = 'bottom';
        $this->_mainConfig['theme_advanced_resizing'] = true;
        
        $this->_mainConfig['content_css'] = $this->_jsPluginPath . 'css/content.css';
        
        $this->_mainConfig['template_external_list_url'] = $this->_jsPluginPath . '/plugins/lists/template_list.js';
        $this->_mainConfig['external_link_list_url'] = $this->_jsPluginPath . '/plugins/lists/link_list.js';
        $this->_mainConfig['external_image_list_url'] = $this->_jsPluginPath . '/plugins/lists/image_list.js';
        $this->_mainConfig['media_external_list_url'] = $this->_jsPluginPath . '/plugins/lists/media_list.js';

    }
    
    public function getConfig() 
    {
        $this->_loadMainConfig();
        return
            array(
                "plugin"=>'kleartinymce',
                "settings" => $this->_mainConfig
            );
    }
    
    
    public function getExtraJavascript()
    {
        return $this->_js;
    
    }
    
    public function getExtraCss()
    {
        return $this->_css;
    }
    
    public function init() {
    
    
    
    }
    
}