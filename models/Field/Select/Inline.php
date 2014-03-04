<?php

class KlearMatrix_Model_Field_Select_Inline extends KlearMatrix_Model_Field_Select_Abstract
{
    protected $_showOnSelect = array();
    protected $_hideOnSelect = array();

    public function init()
    {
        $parsedValues = new Klear_Model_ConfigParser;
        $parsedValues->setConfig($this->_config->getProperty('values'));
        $excludeKeys = $this->_getExcludeKeys();

        foreach ($this->_config->getProperty('values') as $key => $value) {

            if (is_array($excludeKeys) && in_array($key, $excludeKeys)) {
                continue;
            }

            $value = $parsedValues->getProperty((string)$key);

            if ($value instanceof Zend_Config) {

                $fieldValue = new Klear_Model_ConfigParser;
                $fieldValue->setConfig($value);

                $value = Klear_Model_Gettext::gettextCheck($fieldValue->getProperty("title"));

                if ($filter = $fieldValue->getProperty("visualFilter")) {

                    if ($filter->show) {

                        $this->_showOnSelect[$key] = $filter->show;
                    }

                    if ($filter->hide) {

                        $this->_hideOnSelect[$key] = $filter->hide;
                    }
                }
            }

            $value = Klear_Model_Gettext::gettextCheck($value);

            $this->_items[] = $value;
            $this->_keys[] = $key;
        }

        if ($this->_config->getProperty('order') === true) {

            $values = $this->_orderValues();
            $this->_keys = array_keys($values);
            $this->_items = array_values($values);
        }
    }

    protected function _getExcludeKeys()
    {
        $filterClassName = $this->_config->getProperty('filterClass');
        if ($filterClassName) {
            $filter = new $filterClassName;
            if ( !$filter instanceof KlearMatrix_Model_Field_Select_Filter_Interface ) {
                throw new Exception('Filters must implement KlearMatrix_Model_Field_Select_Filter_Interface.');
            }
            $filter->setRouteDispatcher($this->_column->getRouteDispatcher());
            return $filter->getCondition();
        }
        return null;
    }

    protected function _orderValues()
    {
        $keys = $this->_keys;
        $items = $this->_items;

        $values = array_combine($keys, $items);
        asort($values);

        return $values;
    }

    public function getCustomOrderField()
    {
        $keys = $this->_keys;

        if ($this->_config->getProperty('order') !== true) {

            $values = $this->_orderValues();
            $keys = array_keys($values);
        }

        if (!count($keys)) {

            return $this->_quoteIdentifier($this->_column->getDbFieldName());
        }

        $priority = 1;
        $response =  '(CASE '. $this->_quoteIdentifier($this->_column->getDbFieldName()) .' ';
        foreach ($keys as $posibleResult) {
            $response .= " WHEN '" . $posibleResult . "' THEN " . $priority++;
        }
        $response .= ' END)';

        return $response;
    }

}

//EOF