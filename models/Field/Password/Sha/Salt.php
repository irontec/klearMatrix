<?php

class KlearMatrix_Model_Field_Password_Sha_Salt extends KlearMatrix_Model_Field_Password_Abstract
{
    protected function _salt()
    {
        $ret = substr(md5(random_int(0, mt_getrandmax()), false), 0, 8);
        return $ret;
    }

    public function cryptValue()
    {
        $salt = $this->_salt();
        $ret = crypt($this->_clearValue, '$5$rounds=5000$' . $salt . '$');

        return $ret;
    }
}
//EOF
