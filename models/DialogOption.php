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
        $ret = array(
            'icon' => $this->_class,
            'type' => 'dialog',
            'dialog' => $this->_name,
            'title' => $this->getTitle(),
            'defaultOption' => $this->isDefault(),
            'label' => $this->_label,
            'showOnlyOnNotNull' => $this->_showOnlyOnNotNull,
            'showOnlyOnNull' => $this->_showOnlyOnNull
        );

        if ($this->isDefault()) {
            $ret['defaultOption'] = true;
        }

        return $ret;
    }
}
