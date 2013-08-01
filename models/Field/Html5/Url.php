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
        if (false === filter_var($value, FILTER_VALIDATE_URL)) {
            throw new Klear_Exception_Default($translator->translate("El campo no es una URL válida."));
        }

        return $value;

    }
}