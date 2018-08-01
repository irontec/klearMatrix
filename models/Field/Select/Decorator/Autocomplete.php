<?php
class KlearMatrix_Model_Field_Select_Decorator_Autocomplete extends KlearMatrix_Model_Field_DecoratorAbstract
{
    //Por ahora se gestiona desde template.helper.js [getValuesFromSelectColumn()] y list.js
    const APPLY_TO_LISTS = false;
    const APPLY_TO_LIST_FILTERING = true;

    const DYNAMIC_DATA_LOADING = true;
    protected $_commandConfiguration;

    /**
     * @deprecated
     */
    protected $_mapper;
    /**
     * @deprecated
     */
    protected $_model;

    protected $_dataGateway;

    protected $_entity;
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
        $this->_dataGateway = \Zend_Registry::get('data_gateway');
    }

    public function run()
    {
        $mainRouter = $this->_request->getParam("mainRouter");
        $this->_commandConfiguration = $mainRouter->getCurrentCommand()->getConfig()->getRaw()->autocomplete;
        $this->_entity = $this->_commandConfiguration->entity;

        $this->_labelField = $this->_commandConfiguration->label;
        $this->_pkField = 'Id';
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
                $getter = 'get' . ucfirst(str_replace('.', '', $fieldName));
                $replace['%' . $fieldName . '%'] = $record->$getter();
            }

            $templatedValue = str_replace(array_keys($replace), $replace, $this->_fieldsTemplate);

            $options["'".$record->getId()."'"] = array(
                'id' => $record->getId(),
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
                $this->_view->totalItems .= ' ' . $this->_view->translate("(showing %d)KamAccCdrsBrandList", $this->_limit);
            }
        }

        $this->_view->results = $options;
    }

    protected function _getEntityName()
    {
        $entityClassSegments = explode('\\', $this->_entity);
        return end($entityClassSegments);
    }

    protected function _runReverse()
    {
        $where = $this->_getEntityName() . '.';
        $where .= 'id' . ' IN (' . implode(',', $this->_request->getParam("value")) . ')';
        $this->_results = $this->_dataGateway->findBy(
            $this->_entity,
            [$where]
        );

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
            $order = $this->_commandConfiguration->order->toArray();
//            if (strpos($order, ',')) {
//                $order = explode(',', $order);
//            }
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

            $condition = implode(' AND ', $filter->getCondition());

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
            $cleanFldName = str_replace('.', '', $field);
            $query[] = 'self::' . $field . ' LIKE :' . $cleanFldName;
            $stringLike = str_replace(' ', '%', $searchTerm);
            $params[$cleanFldName] = $startParam . $stringLike . $endParam;
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

        foreach ($where as $key => $value) {

            if (!is_string($value)) {
                continue;
            }
            $where[$key] = str_replace('self::', $this->_getEntityName() . '.', $value);
        }

        $records = $this->_dataGateway->findBy(
            $this->_entity,
            $where,
            $order,
            $this->_limit
        );

//        $records = $this->_mapper->fetchList($where, $order, $this->_limit);

        $this->_results = array();

        foreach ($records as $record) {
            $this->_results[$record->getId()] = $record;
        }

        $this->_totalItems = $this->_dataGateway->countBy($this->_entity, $where);
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