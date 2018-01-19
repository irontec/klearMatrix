<?php
class KlearMatrix_Model_Field_Password_Blowfish extends KlearMatrix_Model_Field_Password_Abstract
{

    protected function _salt()
    {
        $salt = "";
        for ($i = 0; $i < 22; $i++) {
            $salt .= substr("./ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789", mt_rand(0, 63), 1);
        }
        return $salt;
    }

    public function cryptValue()
    {
        $strength = '08';
        $salt = $this->_salt();
        $ret = crypt(
            $this->_clearValue,
            '$2a$' . $strength . '$' . $salt . '$'
        );

        return $ret;
    }

}