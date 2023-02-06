<?php

class KlearMatrix_Model_Field_Select_Custom extends KlearMatrix_Model_Field_Select_Abstract
{
    protected $_showOnSelect = array();
    protected $_hideOnSelect = array();

    public function init()
    {
        if ($this->_dynamicDataLoading() === true) {

            //Nothing to do
            return;
        }

        $excludeKeys = $this->_getExcludeKeys();

        $className = $this->_config->getProperty("class");
        $methodName = $this->_config->getProperty("method");
        $this->_customClass = new $className();

        $data = $this->_customClass->$methodName();

        foreach ($data as $key => $value) {

            if (is_array($excludeKeys) && in_array($key, $excludeKeys)) {
                continue;
            }

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

        if (!(is_countable($keys) ? count($keys) : 0)) {
            return $this->_column->getDbFieldName();
        }

        $priority = 1;
        $response =  '(CASE '. $this->_column->getDbFieldName() .' ';
        foreach ($keys as $posibleResult) {
            $response .= " WHEN '" . $posibleResult . "' THEN " . $priority++;
        }
        $response .= ' END)';

        return $response;
    }

    protected function _dynamicDataLoading()
    {
        if (isset($this->_column->getKlearConfig()->getRaw()->decorators)) {
            $selfClassName = get_class($this);
            $classBasePath = substr($selfClassName, 0, strrpos($selfClassName, '_') + 1);
            $decoratorClassBaseName = $classBasePath . 'Decorator_';

            $decorators = $this->_column->getKlearConfig()->getRaw()->decorators;

            foreach ($decorators as $decoratorName => $decorator) {

                $decorator; //Avoid PMD UnusedLocalVariable warning
                $decoratorClassName = $decoratorClassBaseName . ucfirst($decoratorName);

                if (class_exists($decoratorClassName)
                        && defined($decoratorClassName . '::DYNAMIC_DATA_LOADING')
                        && $decoratorClassName::DYNAMIC_DATA_LOADING
                        ) {

                            $this->_loadJsDependencies($decoratorName);
                            return true;
                        }
            }
        }

        return false;
    }

    protected function _loadJsDependencies($decoratorName)
    {
        $jsDependencies = array();
        switch ($decoratorName) {
            case 'autocomplete':
                $jsDependencies[] = '/js/plugins/jquery.klearmatrix.selectautocomplete.js';
                break;
        }

        $this->_js += $jsDependencies;
    }
}
//EOF
