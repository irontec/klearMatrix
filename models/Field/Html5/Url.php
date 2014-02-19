<?php
class KlearMatrix_Model_Field_Html5_Url extends KlearMatrix_Model_Field_Html5_Abstract
{
    public function init()
    {
        $this->_settings = array(
                "type" => "text",
                "class" => "h5-url"
        );
    }

    protected function _validateUrl($value)
    {
        if (empty($value)) {
            return '';
        }

        if (false === filter_var($value, FILTER_VALIDATE_URL)) {
            $translator = Zend_Registry::get(Klear_Plugin_Translator::DEFAULT_REGISTRY_KEY);
            throw new Klear_Exception_Default($translator->translate("Field is not a valid URL."));
        }
        return $value;
    }


    public function filterValue($value)
    {
        if (!is_array($value)) {
            return $this->_validateUrl($value);
        }

        foreach ($value as $idx => $_val) {
            $value[$idx] = $this->_validateUrl($_val);
        }

        return $value;
    }
}