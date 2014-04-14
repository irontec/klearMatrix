<?php
class KlearMatrix_Model_Field_Checkbox extends KlearMatrix_Model_Field_Abstract
{
    protected function _init()
    {
    }

    protected function _getConfig()
    {
        return array();
    }
    
    protected function _hasConfig()
    {
        return isset($this->_config->getRaw()->source);
    }
    
    public function getConfig()
    {
        if (!$this->_hasConfig()) {
            return false;
        }
        $values = array();
        foreach ($this->_config->getRaw()->source->values as $key => $val) {
            $values[$key] = array(
            	'key' => $key, 
                'item' => Klear_Model_Gettext::gettextCheck($val->title)
            );
        }
        return array('values'=> $values);
    }

    public function isMassUpdateable()
    {
        return $this->_hasConfig();
    }
}

//EOF