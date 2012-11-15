<?php
class KlearMatrix_Model_Field_Video extends KlearMatrix_Model_Field_Abstract
{
    protected $_availableSettings = array(
        'feed',
        'paste'
    );

    public function _init()
    {
        $this->_js = array(
           "/js/plugins/jquery.video.youtube.js",
           "/js/plugins/jquery.video.vimeo.js",
           "/js/plugins/jquery.video.js",
        );

        $this->_css = array(
           "/css/jquery.video.css",
        );

        $config = $this->_config->getRaw()->source;
        $this->_setConfig($config);
    }

    protected function _setConfig($config)
    {
        if ($config->settings) {

            foreach ($config->settings as $key => $value) {

                $this->_setSetting($key, $value);
            }
        }

        return $this;
    }

    protected function _setSetting($key, $value)
    {
        if ($value instanceof Zend_Config) {

            $value = $value->toArray();
        }

        if (in_array($key, $this->_availableSettings)) {
            $this->_properties[$key] = $value;
        }
        if (!$value) {
            var_dump(debug_backtrace());
        }
        return $this;
    }

    /*
     * Prepara el valor de un campo, después del getter
     */
    public function prepareValue($value)
    {
        $columnName = $this->_column->getDbFieldName();

        $ret = array(
            'video' => $value,
            'source' => $this->_column->getModel()->{'get' . ucfirst($columnName) . 'Source'}(),
            'title' => $this->_column->getModel()->{'get' . ucfirst($columnName) . 'Title'}(),
            'thumbnail' => $this->_column->getModel()->{'get' . ucfirst($columnName) . 'Thumbnail'}(),
        );

        return $ret;
    }

}