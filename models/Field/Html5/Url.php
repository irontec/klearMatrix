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

    public function filterValue($value)
    {
        if (!is_array($value)) 
            $value = array($value);

        if ($value != ""
            && false === filter_var($value, FILTER_VALIDATE_URL)) {
            $translator = Zend_Registry::get(Klear_Plugin_Translator::DEFAULT_REGISTRY_KEY);
            throw new Klear_Exception_Default($translator->translate("Field is not a valid URL."));
        }

        return $value;

    }
}