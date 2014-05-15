<?php

class KlearMatrix_Model_Option_Link extends KlearMatrix_Model_Option_Abstract
{
    protected function _init()
    {
        $this->_type = 'link';
    }

    public function getType()
    {
        return $this->_type;
    }

    public function toArray()
    {
        $ret = $this->_prepareArray();

        $ret['link'] = $this->_name;
        $ret['type'] = 'link';

        //LANDER
        $ret['url'] = 'link';

        if ($this->isDefault()) {
            $ret['defaultOption'] = true;
        }

        return $ret;

    }
}
