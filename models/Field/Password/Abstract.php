<?php 


abstract class KlearMatrix_Model_Field_Password_Abstract
{
    
    protected $_clearValue;
    
    public function setClearValue($value)
    {
        $this->_clearValue = $value;
        return $this;
    }
    
    
    public function cryptValue()
    {
        return $this->_clearValue;
        
    }
    
}


    