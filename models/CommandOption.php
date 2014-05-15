<?php

/**
* @author jabi
*
*/
class KlearMatrix_Model_CommandOption extends KlearMatrix_Model_AbstractOption
{
    protected function _init()
    {
        $this->_type = 'command';
    }

    public function getType()
    {
        return $this->_type;
    }

    public function toArray()
    {
        $ret = $this->_prepareArray();

        $ret['command'] = $this->_name;
        $ret['type'] = 'command';
        $ret['external'] = true;

        if ($this->getConfig()->getProperty('disabledTime')) {
            $ret['disabledTime'] = $this->getConfig()->getProperty('disabledTime');
        }

        if ($this->isDefault()) {
            $ret['defaultOption'] = true;
        }

        return $ret;
    }
}
