<?php
class KlearMatrix_Model_Field_Multiselect_Decorator_Autocomplete extends KlearMatrix_Model_Field_DecoratorAbstract
{
    //Por ahora se gestiona desde template.helper.js [getValuesFromSelectColumn()] y list.js
    const APPLY_TO_LISTS = false;
    const APPLY_TO_LIST_FILTERING = true;

    const DYNAMIC_DATA_LOADING = true;

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

        if ($this->_request->getParam("reverse")) {
            $this->_runReverse();
        } else {
            $this->_run();
        }

        $options = array();
        foreach ($this->_results as $key => $tupla) {
            $options["'".$key."'"] = array();
            if (!is_array($tupla)) {
                $tupla = array($tupla);
            }

            foreach ($tupla as $record) {
                $replace = array();
                foreach ($this->_fields as $fieldName) {
                    $getter = 'get' . ucfirst($record->columnNameToVar($fieldName));
                    $replace['%' . $fieldName . '%'] = $record->$getter();
                }

                $templatedValue = str_replace(array_keys($replace), $replace, $this->_fieldsTemplate);

                $options["'".$key."'"][] = array(
                    'id' => $record->getPrimaryKey(),
                    'value' => strip_tags($templatedValue),
                    'label' => $templatedValue
                );
            }
        }

        $this->_view->totalItems = $this->_view->translate("%d items found", $this->_totalItems);

        if (isset($this->_limit) && !is_null($this->_limit)) {
            $show = ($this->_limit < $this->_totalItems)? $this->_limit : $this->_totalItems;
            $this->_view->totalItems .= ' ' . $this->_view->translate("(showing %d)", $show);
        }

        $this->_view->results = $options;
    }

    protected function _runReverse()
    {
        $lastResults = null;
        $itemIds = array();

        foreach ($this->_request->getParam("value") as $value) {

            if (empty($value)) continue;

            $where = $this->_pkField . ' in (' . $value . ')';

            $this->_results[$value] = $lastResults = $this->_mapper->fetchList($where);
            foreach ($lastResults as $record) {
                $itemIds[] = $record->getPrimaryKey();
            }
        }

        $this->_totalItems = count(array_unique($itemIds));
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

        $preCondition = '';
        if (isset($this->_commandConfiguration->condition)) {
            $preCondition = '(' . $this->_commandConfiguration->condition . ') and ';
        }

        $multiLangColumns = array_keys($this->_model->getMultiLangColumnsList());
        if (in_array($this->_labelField, $multiLangColumns)) {

            $query = array();
            $params = array();
            foreach ( $this->_fields as $field ) {
                foreach ($this->_model->getAvailableLangs() as $language) {
                    $query[] = $field . '_' . $language . ' LIKE ?';
                    $params[] = '%' . $searchTerm . '%';
                }
            }

            $query = '('. implode(" OR ", $query) .')';
            $where =  array(
                $preCondition . $query,
                $params
            );

        } else {

            $query = array();
            $params = array();
            foreach ( $this->_fields as $field ) {
                $query[] = $field . ' LIKE ?';
                $params[] = '%' . $searchTerm . '%';
            }

            $query = '('. implode(" OR ", $query) .')';
            $where =  array(
                    $preCondition . $query,
                    $params
            );
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