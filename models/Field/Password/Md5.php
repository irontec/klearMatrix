<?php 


class KlearMatrix_Model_Field_Password_Md5 extends KlearMatrix_Model_Field_Password_Abstract
{
    
    
    public function cryptValue()
    {
        return md5($this->_clearValue);
        
    }
    
}


    