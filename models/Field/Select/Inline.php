<?php

      
class KlearMatrix_Model_Field_Select_Inline extends KlearMatrix_Model_Field_Select_Abstract
{

    
    public function init() {
        
        $parsedValues = new Klear_Model_KConfigParser;
        $parsedValues->setConfig($this->_config->values);
        file_put_contents("/tmp/tuputamadre",print_r($this->_config->values,true) . "go\n");
        foreach($this->_config->values as $key=>$value) {
            file_put_contents("/tmp/tuputamadre",print_r($key ,true).' => ' . print_r($value,true) ."\n",FILE_APPEND);
            $this->_items[] = $parsedValues->getProperty((string)$key);
            $this->_keys[] = $key;          
               
        }
           
    }
    
    
    
}