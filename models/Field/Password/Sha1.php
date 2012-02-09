<?php 


class KlearMatrix_Model_Field_Password_Sha1 extends KlearMatrix_Model_Field_Password_Abstract
{
    
    
    public function cryptValue()
    {
        return sha1($this->_clearValue);
        
    }
    
}


    