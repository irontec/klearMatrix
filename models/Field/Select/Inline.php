<?php

class KlearMatrix_Model_Field_Select_Inline extends KlearMatrix_Model_Field_Select_Abstract
{

    protected $_showOnSelect = array();
    protected $_hideOnSelect = array();

    public function init()
    {
        $parsedValues = new Klear_Model_KConfigParser;
        $parsedValues->setConfig($this->_config->getProperty('values'));

        foreach ($this->_config->getProperty('values') as $key=>$value) {

            $value = $parsedValues->getProperty((string)$key);

            if (!is_string($value)) {

                $fieldValue = new Klear_Model_KConfigParser;
                $fieldValue->setConfig($value);
                $value = $fieldValue->getProperty("title");

                if ($filter = $fieldValue->getProperty("visualFilter")) {

                    if ($filter->show) {

                        $this->_showOnSelect[$key] = $filter->show;
                    }

                    if ($filter->hide) {

                        $this->_hideOnSelect[$key] = $filter->hide;
                    }
                }
            }

            $this->_items[] = $value;
            $this->_keys[] = $key;
        }
    }

    public function getExtraConfigArray()
    {
        $ret = array();

        if (sizeof($this->_showOnSelect)>0 || sizeof($this->_hideOnSelect)>0) {

            $ret['visualFilter']['show'] = (array)$this->_showOnSelect;
            $ret['visualFilter']['hide'] = (array)$this->_hideOnSelect;
        }

        return $ret;
    }

}

//EOF