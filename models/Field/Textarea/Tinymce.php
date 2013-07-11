<?php
class KlearMatrix_Model_Field_Textarea_Tinymce extends KlearMatrix_Model_Field_Textarea_Abstract
{
    /**
     *
     * @var Klear_Model_Language
     */
    protected $_lang;

    protected $_jsPluginPath = 'klearMatrix/js/plugins/tinymce/jscripts/tinymce';

    /**
     *
     * @var KlearMatrix_Model_Field_Textarea_Tinymce_Template
     */
    protected $_template;

   public function init()
    {
        $this->_loadTemplateClass();

        $this->_js = array(
                "/js/plugins/tinymce/jscripts/tinymce/jquery.tinymce.min.js",
                $this->_template->getJsController(),
                "/js/plugins/jquery.ui.kleartinymce.js"
        );

        $this->_configureDefaults();
    }

    protected function _loadTemplateClass()
    {
        $this->_template = new KlearMatrix_Model_Field_Textarea_Tinymce_Template($this->_config);
        
    }

    protected function _configureDefaults()
    {
        // base url
        $front = Zend_Controller_Front::getInstance();
        $baseUrl = $front->getBaseUrl();
        $this->_jsPluginPath = $baseUrl . DIRECTORY_SEPARATOR . $this->_jsPluginPath;

        // JS template
        $this->_settings['tinyJsController'] = $this->_template->getJsControllerClass();

        // Language Settings
        $this->_lang = Zend_Registry::get('currentSystemLanguage');
        $this->_settings['language'] = $this->_lang->getLanguage();

        // Location of TinyMCE script
        $this->_settings['script_url'] = $this->_jsPluginPath . '/tinymce.min.js';
        
        
        $this->_settings = array_merge($this->_settings, $this->_template->getSettings());
        
    }

    protected function _setPlugin()
    {
        $this->_plugin = 'kleartinymce';
    }
}