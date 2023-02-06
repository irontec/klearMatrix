<?php

class KlearMatrix_Model_Field_Select_Decorator_Autocomplete extends KlearMatrix_Model_Field_DecoratorAbstract
{
    //Por ahora se gestiona desde template.helper.js [getValuesFromSelectColumn()] y list.js
    public const APPLY_TO_LISTS = false;
    public const APPLY_TO_LIST_FILTERING = true;

    public const DYNAMIC_DATA_LOADING = true;

    protected $_commandConfiguration;

    protected $_mapper;
    protected $_model;
    protected $_pkField;
    protected $_labelField;
    protected $_fields;
    protected $_fieldsTemplate;

    protected $_limit;
    protected $_totalItems = 0;
    protected $_results = array();

    protected function _init()
    {
        $this->_helper->viewRenderer->setNoRender(true);
    }

    public function run()
    {
        $mainRouter = $this->_request->getParam("mainRouter");
        $this->_commandConfiguration = $mainRouter->getCurrentCommand()->getConfig()->getRaw()->autocomplete;

        $this->_mapperName = $this->_commandConfiguration->mapperName;
        $this->_mapper = new $this->_mapperName;
        $this->_model = $this->_mapper->loadModel(null);

        $this->_labelField = $this->_commandConfiguration->label;
        $this->_pkField = $this->_model->getPrimaryKeyName();
        $this->_fields = $this->_getFields();
        $this->_fieldsTemplate = Klear_Model_Gettext::gettextCheck($this->_getFieldsTemplate());

        $options = array();

        if ($this->_request->getParam("reverse")) {
            $this->_runReverse();
        } else {
            $this->_run();
            if ($this->_commandConfiguration->null) {
                $options["'__null__'"] = array(
                    'id' => "__NULL__",
                    'value' => strip_tags(Klear_Model_Gettext::gettextCheck($this->_commandConfiguration->null)),
                    'label' => Klear_Model_Gettext::gettextCheck($this->_commandConfiguration->null),
                );
            }
        }



        foreach ($this->_results as $record) {

            $replace = array();
            foreach ($this->_fields as $fieldName) {
                $getter = 'get' . ucfirst($record->columnNameToVar($fieldName));
                $replace['%' . $fieldName . '%'] = $record->$getter();
            }

            $templatedValue = str_replace(array_keys($replace), $replace, $this->_fieldsTemplate);

            $options["'".$record->getPrimaryKey()."'"] = array(
                'id' => $record->getPrimaryKey(),
                'value' => strip_tags($templatedValue),
                'label' => $templatedValue,
            );
        }

        $maxReached = false;
        if ($this->_totalItems === false) {
            $this->_totalItems = count($this->_results);
            $maxReached = true;
        }
        $this->_view->totalItems = $this->_view->translate("%d items found", $this->_totalItems);

        if (isset($this->_limit) && !is_null($this->_limit)) {
            $show = ($this->_limit < $this->_totalItems)? $this->_limit : $this->_totalItems;
            $this->_view->totalItems .= ' ' . $this->_view->translate("(showing %d)", $show);

            if ($maxReached === true && count($this->_results) == $this->_limit) {
                $this->_view->totalItems = $this->_view->translate("More than %d items found", $this->_limit);
                $this->_view->totalItems .= ' ' . $this->_view->translate("(showing %d)", $this->_limit);
            }
        }

        $this->_view->results = $options;
    }

    protected function _runReverse()
    {
        $this->_results = $this->_mapper->findByField($this->_pkField, $this->_request->getParam("value"));
        $this->_totalItems = sizeof($this->_results);
    }


    protected function _run()
    {
        $searchTerm = $this->_request->getParam("term");

        $this->_limit = null;
        $order = null;

        if (isset($this->_commandConfiguration->limit)) {
            $this->_limit = intval($this->_commandConfiguration->limit);
        }

        if (isset($this->_commandConfiguration->order)) {
            $order = $this->_commandConfiguration->order;
            if (strpos($order, ',')) {
                $order = explode(',', $order);
            }
        }

        $condition = '';
        if (isset($this->_commandConfiguration->filterClass)) {
            if (isset($this->_commandConfiguration->condition)) {
                throw new Exception('Defined condition is not going to work because filterClass is set.', 100);
            }
            $filterClassName = $this->_commandConfiguration->filterClass;
            $filter = new $filterClassName;
            if ( !$filter instanceof KlearMatrix_Model_Field_Select_Filter_Interface ) {
                throw new Exception('Filters must implement KlearMatrix_Model_Field_Select_Filter_Interface.');
            }
            $filter->setRouteDispatcher($this->_request->getParam("mainRouter"));

            $condition = $filter->getCondition();

            if (!$this->_commandConfiguration->ignoreWhereDefault) {
                $condition .= ' AND ';
            }
        } elseif (isset($this->_commandConfiguration->condition)) {
            $condition = '(' . $this->_commandConfiguration->condition . ')';
            if (!$this->_commandConfiguration->ignoreWhereDefault) {
                $condition = '(' . $this->_commandConfiguration->condition . ') and ';
            }
        }

        $query = array();
        $params = array();

        $startParam = "%";
        $endParam = "%";
        if ($this->_commandConfiguration->matchAt) {
            switch ($this->_commandConfiguration->matchAt) {
                case "start":
                    $startParam = "";
                    $endParam = "%";
                    break;
                case "end":
                    $startParam = "%";
                    $endParam = "";
                    break;
                default:
                    $startParam = "%";
                    $endParam = "%";
                    break;
            }
        }

        foreach ( $this->_fields as $field ) {
            $query[] = Zend_Db_Table::getDefaultAdapter()->quoteIdentifier($field) . ' LIKE ?';
            $stringLike = str_replace(" ", "%",$searchTerm);
            $params[] = $startParam . $stringLike . $endParam;
        }

        $query = '('. implode(" OR ", $query) .')';
        if (!$this->_commandConfiguration->ignoreWhereDefault) {
            $where =  array(
                    $condition . $query,
                    $params
            );
        } else {
            $where =  $condition;
        }

        $records = $this->_mapper->fetchList($where, $order, $this->_limit);

        $this->_results = array();

        foreach ($records as $record) {
            $this->_results[$record->getPrimaryKey()] = $record;
        }

        $this->_totalItems = $this->_mapper->countByQuery($where);
    }

    protected function _getFields()
    {
        $fieldName = $this->_commandConfiguration->fieldName;

        if (!is_object($fieldName)) {
            return isset($fieldName) ? array($fieldName) : array($this->_labelField);
        }

        $fieldConfig = new Klear_Model_ConfigParser();
        $fieldConfig->setConfig($fieldName);
        return $fieldConfig->getProperty("fields");
    }

    protected function _getFieldsTemplate()
    {
        $fieldName = $this->_commandConfiguration->fieldName;

        if (!is_object($fieldName)) {
            return isset($fieldName) ? '%' . $fieldName .'%' : '%' . $this->_labelField . '%';
        }

        $fieldConfig = new Klear_Model_ConfigParser();
        $fieldConfig->setConfig($fieldName);
        return $fieldConfig->getProperty("template");
    }
}
