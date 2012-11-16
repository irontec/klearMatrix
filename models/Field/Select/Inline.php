<?php

class KlearMatrix_Model_Field_Select_Inline extends KlearMatrix_Model_Field_Select_Abstract
{

    use Klear_Model_Trait_Gettext;
    
    protected $_showOnSelect = array();
    protected $_hideOnSelect = array();

    public function init()
    {
        $parsedValues = new Klear_Model_ConfigParser;
        $parsedValues->setConfig($this->_config->getProperty('values'));

        foreach ($this->_config->getProperty('values') as $key=>$value) {

            $value = $parsedValues->getProperty((string)$key);

            if ($value instanceof Zend_Config) {

                $fieldValue = new Klear_Model_ConfigParser;
                $fieldValue->setConfig($value);
                $value = $this->_gettextCheck($fieldValue->getProperty("title"));
                
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

}

//EOF