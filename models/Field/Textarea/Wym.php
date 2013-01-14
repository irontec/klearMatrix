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
                "/js/plugins/jquery.klearmatrix.wym.js"
        );

        $this->_configureDefaults();
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

                $this->_settings[$key] = $val;
            }
        }
    }

    protected function _configureDefaults()
    {
        $this->_settings = array(
            'lang' => 'es',
            'basePath' => '../klearMatrix/js/plugins/wym/',
            'skinPath' => '..//klearMatrix/js/plugins/wym/skins/default/',
            'wymPath' => '../klearMatrix/js/plugins/wym/' . $this->_scriptName,
            'logoHtml' => ''
        );
    }

    protected function _setPlugin()
    {
        $this->_plugin = 'wym';
    }
}