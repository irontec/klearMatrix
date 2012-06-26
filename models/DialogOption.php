<?php

/**
* @author jabi
*
*/
class KlearMatrix_Model_DialogOption extends KlearMatrix_Model_AbstractOption
{
    protected $_dialog;

    public function setDialogName($dialog)
    {
        $this->_dialog = $dialog;
    }

    public function getTitle()
    {
        if (null != $this->_title) {
            return $this->_title;
        }

        return 'unnamed option';
    }

    public function toArray()
    {
        $ret = array(
            'icon' => $this->_class,
            'type' => 'dialog',
            'dialog' => $this->_dialog,
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
