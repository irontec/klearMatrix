<?php

/**
* @author jabi
*
*/
class KlearMatrix_Model_DialogOption extends KlearMatrix_Model_AbstractOption
{
    protected function _init()
    {
        $this->_type = 'dialog';
    }

    public function getType()
    {
        return $this->_type;
    }

    public function toArray()
    {
        $ret = $this->_prepareArray();

        $ret['dialog'] = $this->_name;
        $ret['type'] = 'dialog';

       // $ret = $this->_removeFalse($ret);

        if ($this->isDefault()) {
            $ret['defaultOption'] = true;
        }

        return $ret;
    }
}
