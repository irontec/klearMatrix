<?php
class KlearMatrix_Model_Field_Html5_Range extends KlearMatrix_Model_Field_Html5_Abstract
{
    
    public function init()
    {
        $this->_settings = array(
                                "type" => "range"
                            );
        
        $this->_nodeAttributes = array(
                                    "min" => $this->_config->getProperty("minRange"),
                                    "max" => $this->_config->getProperty("maxRange")
                                    );
        
    }
    
    public function filterValue($value)
    {
        
        return $value;
    }
}