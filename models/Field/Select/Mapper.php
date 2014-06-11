<?php
class KlearMatrix_Model_Field_Select_Mapper extends KlearMatrix_Model_Field_Select_Abstract
{
    protected $_showOnSelect = array();
    protected $_hideOnSelect = array();

    /**
     * Estructura inventada para exponer en cada <option> un atributo data con el valor de un campo.
     * Útil para javascripts que extiendan funcionalidades (por ejemplo Timezones por país seleccionado).
     * modo de empleo:
     *
     * config:
     *   dynamicDataAttributes:
     *     etiqueta: campoEnBBDD
     *
     * esto generará en cada <option /> un data-etiqueta="Valor de campoEnBBDD para cada registro"
     * @var Array
     */
    protected $_extraDataAttributes = array();
    protected $_extraDataAttributesValues = array();

    protected $_js = array(
    );

    protected function _parseExtraAttrs(Zend_Config $extraConfig, $dataMapper)
    {

        $model = $dataMapper->loadModel(false);
        $retAttrs = array();
        foreach ($extraConfig as $label => $field) {
            if (!$varName = $model->columnNameToVar($field)) {
                continue;
            }

            $retAttrs[$label] = 'get' . ucfirst($varName);
        }
        return $retAttrs;
    }

    protected function _setValuesForExtraAttributes($model, $key)
    {
        if (sizeof($this->_extraDataAttributes) == 0) {
            return;
        }

        $ret = array();
        foreach ($this->_extraDataAttributes as $label => $getter) {
            $ret[$label] = $model->$getter();
        }

        $this->_extraDataAttributesValues[$key] = $ret;
    }

    public function init()
    {
        if ($this->_dynamicDataLoading() === true) {

            //Nothing to do
            return;
        }

        $mapperName = $this->_config->getProperty("config")->mapperName;
        $dataMapper = new $mapperName;

        if (isset($this->_config->getProperty('config')->extraDataAttributes)) {

            $extraAttrs = $this->_config->getProperty('config')->extraDataAttributes;
            $this->_extraDataAttributes = $this->_parseExtraAttrs($extraAttrs, $dataMapper);
        }

        $where = $this->_getFilterWhere();

        $order = $this->_config->getProperty('config')->order;
        $results = $dataMapper->fetchList($where, $order);
        $this->_setOptions($results);
    }

    /**
     * return bool
     */
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

    protected function _getFilterWhere()
    {

        $manualCondition = $this->_config->getProperty('config')->rawCondition;
        if (!empty($manualCondition)) {
            return $manualCondition;
        }

        $filterClassName = $this->_config->getProperty('config')->filterClass;
        if ($filterClassName) {
            $filter = new $filterClassName;
            if ( !$filter instanceof KlearMatrix_Model_Field_Select_Filter_Interface ) {
                throw new Exception('Filters must implement KlearMatrix_Model_Field_Select_Filter_Interface.');
            }
            return $this->_getFilterCondition($filter);
        }
        return null;
    }

    protected function _getFilterCondition(KlearMatrix_Model_Field_Select_Filter_Interface $filter)
    {
        $filter->setRouteDispatcher($this->_column->getRouteDispatcher());
        return $filter->getCondition();
    }

    protected function _setOptions($results)
    {
        if ($results) {
            foreach ($results as $dataModel) {
                $this->_keys[] = $dataModel->getPrimaryKey();
                $this->_items[] = $this->_getItemValue($dataModel);

                $this->_setValuesForExtraAttributes($dataModel, $dataModel->getPrimaryKey());

                $this->_initVisualFilter($dataModel);
            }
        }
    }

    protected function _getItemValue($dataModel)
    {
        $customValueMethod = $this->_config->getProperty('config')->customValueMethod;
        if ($customValueMethod) {
            return $dataModel->$customValueMethod();
        }

        $fields = $this->_getFields();
        $fieldsTemplate = Klear_Model_Gettext::gettextCheck($this->_getFieldsTemplate());
        $replace = array();
        foreach ($fields as $fieldName) {
            $getter = 'get' . ucfirst($dataModel->columnNameToVar($fieldName));
            $replace['%' . $fieldName . '%'] = $dataModel->$getter();
        }

        return str_replace(array_keys($replace), $replace, $fieldsTemplate);
    }

    protected function _getFields()
    {
        $fieldName = $this->_config->getProperty('config')->fieldName;

        if (!is_object($fieldName)) {
            return array($fieldName);
        }

        $fieldConfig = new Klear_Model_ConfigParser();
        $fieldConfig->setConfig($fieldName);
        return $fieldConfig->getProperty("fields");
    }

    protected function _getFieldsTemplate()
    {
        $fieldName = $this->_config->getProperty('config')->fieldName;

        if (!is_object($fieldName)) {
            return '%' . $fieldName . '%';
        }

        $fieldConfig = new Klear_Model_ConfigParser();
        $fieldConfig->setConfig($fieldName);
        return $fieldConfig->getProperty("template");
    }

    public function _initVisualFilter($dataModel)
    {
        $visualFilter = $this->_config->getProperty('config')->visualFilter;

        if ($visualFilter) {

            foreach ($visualFilter as $key => $config) {

                if ($this->_config->getProperty("null")) {

                    if ($config->null) {

                        $this->_showOnSelect['__null__'] = $config->null->show;
                        $this->_hideOnSelect['__null__'] = $config->null->hide;

                    } else {

                        $this->_showOnSelect['__null__'] = array();
                        $this->_hideOnSelect['__null__'] = array();
                    }
                }

                $getter = 'get' . ucfirst($dataModel->columnNameToVar($key));
                $value = $dataModel->$getter();

                if ($config->$value) {

                    $this->_showOnSelect[$dataModel->getPrimaryKey()] = $config->$value->show;
                    $this->_hideOnSelect[$dataModel->getPrimaryKey()] = $config->$value->hide;
                }
            }
        }
    }


    /* (non-PHPdoc)
     * Sobreescrito para "llevar" extraDataAttributtes (si los hubiere)
     * @see KlearMatrix_Model_Field_Select_Abstract::_toArray()
     */
    protected function _toArray()
    {
        $ret = array();

        foreach ($this as $key => $value) {
            $_val = array('key' => $key, 'item' => $value);
            if (isset($this->_extraDataAttributesValues[$key])) {
                $_val['data'] = array();
                foreach ($this->_extraDataAttributesValues[$key] as $label => $dataVal) {
                    $_val['data'][$label] = $dataVal;
                }
            }
            $ret[] = $_val;
        }
        return $ret;
    }

    //Hace la ordenación en el mapper por el campo o campos que se van a mostrar y devuelve los ids ordenados
    public function getCustomOrderField()
    {
        $values = array();

        $fieldName = $this->_config->getProperty('config')->fieldName;

        if (is_object($fieldName)) {

            $fieldConfig = new Klear_Model_ConfigParser();
            $fieldConfig->setConfig($fieldName);
            $template = $fieldConfig->getProperty("template");
            preg_match_all('/%(.*)%/U', $template, $matches);

            if (!count($matches[1])) {

                return $this->_quoteIdentifier($this->_column->getDbFieldName());
            }

            $fieldName = $matches[1];
        }

        $mapperName = $this->_config->getProperty("config")->mapperName;
        $dataMapper = new $mapperName;
        $results = $dataMapper->fetchList('', $fieldName);

        foreach ($results as $result) {
            $values[] = $result->getPrimaryKey();
        }

        if (! count($values)) {
            return $this->_quoteIdentifier($this->_column->getDbFieldName());
        }

        $priority = 1;
        $response =  '(CASE '. $this->_quoteIdentifier($this->_column->getDbFieldName()) .' ';
        foreach ($values as $posibleResult) {
            $response .= " WHEN '" . $posibleResult . "' THEN " . $priority++;
        }
        $response .= ' END)';

        return $response;
    }
}

//EOF