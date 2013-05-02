<?php
class KlearMatrix_Model_Field_Textarea_Wym extends KlearMatrix_Model_Field_Textarea_Abstract
{

    protected $_scriptName = 'jquery.wymeditor.js';
    /**
     *
     * @var Klear_Model_Language
     */
    protected $_lang;
    protected $_jsPluginPath = 'klearMatrix/js/plugins/wym/';

    /**
     *
     * @var KlearMatrix_Model_Field_Textarea_Raptor_Template_Abstract
     */
    protected $_template;

    protected $_defaultTemplate = 'simple';

    public function init()
    {
        $this->_loadTemplateClass();
        $this->_loadConfiguration();

        $this->_js = array(
                "/js/plugins/wym/" . $this->_scriptName,
                "/js/plugins/wym/plugins/hovertools/jquery.wymeditor.hovertools.js",
                "/js/plugins/wym/plugins/resizable/jquery.wymeditor.resizable.js",
                "/js/plugins/wym/plugins/fullscreen/jquery.wymeditor.fullscreen.js",
                "/js/plugins/jquery.klearmatrix.wym.js",
                "/js/plugins/wym/plugins/kleargallery/jquery.wymeditor.kleargallery.js",
        );
    }

    protected function _loadTemplateClass()
    {
        if (!$templateClass = $this->_config->getProperty('template')) {
            $templateClass = $this->_defaultTemplate;
        }

        $templateClass = 'KlearMatrix_Model_Field_Textarea_Wym_Template_' . ucfirst($templateClass);

        if (!class_exists($templateClass)) {
            $templateClass = 'KlearMatrix_Model_Field_Textarea_Wym_Template_' . ucfirst($this->_defaultTemplate);
        }

        $this->_template = new $templateClass($this->_config);
    }

    protected function _loadConfiguration()
    {
        $this->_configureDefaults();

        foreach ($this->_config->getRaw() as $key => $val) {

            if (! in_array($key, array('control', 'template'))) {

                if ($val instanceof Zend_Config) {

                    $val = $val->toArray();
                }

                $this->_settings[$key] = $val;
            }
        }
    }

    protected function _configureDefaults()
    {
        $view = new Zend_View;
        $baseUrl = $view->serverUrl() . $view->baseUrl();

        $this->_settings = array(
            'lang' => $this->_getLanguage(),
            'basePath' => $baseUrl . '/klearMatrix/js/plugins/wym/',
            'jQueryPath' => $baseUrl . '/klear/js/libs/jquery.min.js',
            'skinPath' => $baseUrl. '/klearMatrix/js/plugins/wym/skins/default/',
            'wymPath' => $baseUrl . '/klearMatrix/js/plugins/wym/' . $this->_scriptName,
            'logoHtml' => '',
        );
    }

    protected function _getLanguage()
    {
        $currentKlearLanguage = Zend_Registry::get('currentSystemLanguage');
        $language = $currentKlearLanguage->getLanguage();
        return $currentKlearLanguage->getLanguage();

//         if (LANGUAGE EXISTS) {
//             return $language;
//         }
//         return 'es';
    }
    protected function _setPlugin()
    {
        $this->_plugin = 'wym';
    }
}