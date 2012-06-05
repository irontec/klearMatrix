<?php

class KlearMatrix_Model_Field_Password_Md5_Salt extends KlearMatrix_Model_Field_Password_Abstract
{

    protected function _salt()
    {
        $ret = substr(md5(mt_rand(), false), 0, 8);

        return $ret;
    }

    public function cryptValue()
    {
        $salt = $this->_salt();
        $ret = crypt($this->_clearValue, '$1$' . $salt . '$');

        return $ret;
    }

}

//EOF