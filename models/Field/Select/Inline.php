<?php

      
class KlearMatrix_Model_Field_Select_Inline extends KlearMatrix_Model_Field_Select_Abstract
{

    
    public function init() {
        
        $parsedValues = new Klear_Model_KConfigParser;
        $parsedValues->setConfig($this->_config->values);
           
        foreach($this->_config->values as $key=>$value) {
            $this->_items[] = $parsedValues->getProperty($key);
            $this->_keys[] = $key;            
               
        }
           
    }
    
    
    
}