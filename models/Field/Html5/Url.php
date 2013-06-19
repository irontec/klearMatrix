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
}