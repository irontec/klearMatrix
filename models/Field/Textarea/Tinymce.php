<?php

class KlearMatrix_Model_Field_Textarea_Tinymce extends KlearMatrix_Model_Field_Textarea_Abstract
{
    /**
     *
     * @var Klear_Model_Language
     */
    protected $_lang;

    protected $_jsPluginPath = 'klearMatrix/js/plugins/tinymce/jscripts/tiny_mce';

    /**
     *
     * @var KlearMatrix_Model_Field_Textarea_Tinymce_Template_Abstract
     */
    protected $_template;

    protected $_defaultTemplate = 'simple';

    public function init()
    {
        $this->_loadTemplateClass();

        $this->_js = array(
                "/js/plugins/tinymce/jscripts/tiny_mce/jquery.tinymce.js",
                $this->_template->getJsController(),
                "/js/plugins/jquery.ui.kleartinymce.js"
        );

        $this->_configureDefaults();
    }

    protected function _loadTemplateClass()
    {
        if (!$templateClass = $this->_config->getProperty('template')) {
            $templateClass = $this->_defaultTemplate;
        }

        $templateClass = 'KlearMatrix_Model_Field_Textarea_Tinymce_Template_' . ucfirst($templateClass);

        if (!class_exists($templateClass)) {
            $templateClass = 'KlearMatrix_Model_Field_Textarea_Tinymce_Template_' . ucfirst($this->_defaultTemplate);
        }

        $this->_template = new $templateClass($this->_config);
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
        $this->_settings['script_url'] = $this->_jsPluginPath . '/tiny_mce.js';

        // Libraries
        $this->_settings['content_css'] = $this->_jsPluginPath . 'css/content.css';
        $this->_settings['template_external_list_url'] = $this->_jsPluginPath . '/plugins/lists/template_list.js';
        $this->_settings['external_link_list_url'] = $this->_jsPluginPath . '/plugins/lists/link_list.js';
        $this->_settings['external_image_list_url'] = $this->_jsPluginPath . '/plugins/lists/image_list.js';
        $this->_settings['media_external_list_url'] = $this->_jsPluginPath . '/plugins/lists/media_list.js';

        // Theme
        $this->_settings['theme'] = $this->_template->getTinyTemplate();

        $this->_settings['theme_advanced_toolbar_location'] = $this->_template->getTinyToolBarLocation();//'top';
        $this->_settings['theme_advanced_toolbar_align'] = $this->_template->getTinyToolBarAlign();//'left';
        $this->_settings['theme_advanced_statusbar_location'] = $this->_template->getTinyStatusBarLocation();//'bottom';
        $this->_settings['theme_advanced_resizing'] = $this->_template->getTinyResizing();//true;

        // Plugins
        $this->_settings['plugins'] = $this->_template->getTinyPlugins();

        $buttonsBars = $this->_template->getButtonsBar();

        foreach ($buttonsBars as $index => $set) {
            $this->_settings['theme_advanced_buttons' . ($index+1)] = implode(',', $set);
        }
    }

    protected function _setPlugin()
    {
        $this->_plugin = 'kleartinymce';
    }
}