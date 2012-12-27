<?php

class KlearMatrix_Model_FilterProcessor
{
    protected $_request;
    protected $_columns;
    protected $_model;

    protected $_where = false;
    protected $_log = false;
    protected $_data = false;

    public function setRequest(Zend_Controller_Request_Abstract $request)
    {
        $this->_request = $request;
        return $this;
    }


    public function setColumnCollection(KlearMatrix_Model_ColumnCollection $cols)
    {
        $this->_columns = $cols;
        return $this;
    }

    public function setModel($model)
    {
        $this->_model = $model;
        return $this;
    }

    public function setLogger($log)
    {
        $this->_log = $log;
        return $this;
    }


    public function setResponseData(KlearMatrix_Model_MatrixResponse $data)
    {
        $this->_data = $data;
        return $this;
    }

    protected function _log($log)
    {
        if ((false === $this->_log) || (!is_object($this->_log))) {
            return;
        }
        $this->_log->log($log);
    }

    protected function _addSearchToData($field, $values, $valuesOp)
    {
        if (false === $this->_data) {
            return;
        }

        $this->_data->addSearchField($field, $values, $valuesOp);
    }

    protected function _addSearchAddModifierToData($value)
    {

        if (false === $this->_data) {
            return;
        }

        $this->_data->addSearchAddModifier(true);
    }


    protected function _generate()
    {
        if ((!isset($this->_request)) || (!isset($this->_columns)) || (!isset($this->_model))) {
            Throw new Exception("FilterProcessor not properly invocated");
        }

        $searchWhere = $this->_getSearchWhere();

        if (!$searchWhere) {
            return false;
        }

        $expressions = $values = array();

        foreach ($searchWhere as $condition) {

            if (is_array($condition)) {

                $expressions[] = $condition[0];
                $values = array_merge($values, $condition[1]);

            } else {

                $expressions[] = $condition;
            }
        }

        if ($this->_request->getPost("searchAddModifier") == '1') {
            $this->_addSearchAddModifierToData(true);

            $this->_where = array('(' . implode(" or ", $expressions) . ')', $values);

        } else {

            $this->_where = array('(' . implode(" and ", $expressions) . ')', $values);
        }

        return true;
    }

    protected function _getSearchWhere()
    {
        $searchWhere = array();
        $searchOps = $this->_request->getPost("searchOps");

        $searchFields = $this->_getSearchFields();
        if (!$searchFields) {

            return null;
        }
        $this->_log('Search arguments found for: ');

        foreach ($searchFields as $field => $values) {

            $valuesOp = $searchOps[$field];
            $column = $this->_columns->getColFromDbName($field);
            if ($column) {
                $searchWhere[] = $column->getSearchCondition($values, $valuesOp, $this->_columns->getLangs());
                $this->_addSearchToData($field, $values, $valuesOp);
            }
        }

        return $searchWhere;
    }

    protected function _getSearchFields()
    {
        $searchFields = $this->_request->getPost("searchFields");
        if (is_array($searchFields)) {

            foreach ($searchFields as $key => $val) {

                if (empty($val)) {
                    unset($searchFields[$key]);
                }
            }

            if (empty($searchFields)) {
                $searchFields = null;
            }
        }
        return $searchFields;
    }

    public function isFilteredRequest()
    {
        return $this->_generate();
    }

    public function getCondition()
    {
        return $this->_where;
    }
}

