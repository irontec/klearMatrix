<?php

class KlearMatrix_Model_FilterProcessor
{
    protected $_request;
    protected $_columns;
    protected $_model;

    protected $_where = false;
    protected $_log = false;
    protected $_data = false;
    protected $_item = false;

    protected $_isPresetted = false;
    protected $_presettedFilters = array();
    
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
    
    public function setResponseItem(KlearMatrix_Model_ResponseItem $item)
    {
        $this->_item = $item;
        $this->_presettedFilters = $this->_item->getPresettedFilters();
        $this->_isPresetted = $this->_presettedFilters instanceof Zend_Config;
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

    protected function _addSearchAddModifierToData()
    {

        if (false === $this->_data) {
            return;
        }

        $this->_data->addSearchAddModifier(true);
    }
    
    protected function _toggleApplySearchFilters($value)
    {
        if (false === $this->_data) {
            return;
        }
        
        $this->_data->toggleApplySearchFilters($value);
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

        if ($this->_request->getPost("applySearchFilters") == '0') {
            $this->_toggleApplySearchFilters(false);
            $this->_where = '(1=1)';
        } else {
            if ($this->_request->getPost("searchAddModifier") == '1') {
                $this->_addSearchAddModifierToData();
                $this->_where = array('(' . implode(" or ", $expressions) . ')', $values);
            } else {
                $this->_where = array('(' . implode(" and ", $expressions) . ')', $values);
            }
        }

        return true;
    }

    protected function _getSearchWhere()
    {
        $searchWhere = array();

        $searchFields = $this->_getSearchFields();
        if (!$searchFields) {
            return null;
        }

        $searchOps = $this->_getSearchOps();

        
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

    protected function _getSearchOps()
    {
        $searchOps = $this->_request->getPost("searchOps");
        if (is_null($searchOps) && $this->_isPresetted) {
            
            $searchOps = array();
            foreach($this->_presettedFilters as $searchPreSetted) {
                
                $field = $searchPreSetted->field;
                $value = $searchPreSetted->value;
                if (isset($searchPreSetted->op)) {
                    $op = $searchPreSetted->op;
                } else {
                    $op = 'eq';
                }
                
                    
                if (!isset($searchFields[$field])) {
                    $searchOps[$field] = array();
                }
            
                if (!$value instanceof Traversable) {
                    $value = array($value);
                }
                
                if (!$op instanceof Traversable) {
                    $op = array($op);
                }
            
                /*
                 * Por cada valor, deberá haber una "op"
                 */
                $cur = 0;
                foreach($value as $_val) {
                    $curOp = 'eq';
                    if (is_array($op) && (isset($op[$cur]))) {
                            $curOp = $op[$cur];
                    } else {
                        // Si no es array, entendemos que se ha especificado una única op, para el/los posibles valores.
                        $curOp = $op;    
                    }
                    
                    $searchOps[$field][] = $curOp;
                }
            
            }
            
        }
        return $searchOps;
        
    }
    
    protected function _getSearchFields()
    {

        $searchFields = $this->_getPostSearchFields();
        /*
         * Si no hay búsqueda, comprobamos que no existan presettedFilters
         */
        if (is_null($searchFields) && $this->_isPresetted) {
            
            foreach($this->_presettedFilters as $searchPreSetted) {
                $field = $searchPreSetted->field;
                $value = $searchPreSetted->value;
                
                if (!isset($searchFields[$field])) {
                    $searchFields[$field] = array();
                }
                
                if (!$value instanceof Traversable) {
                    $value = array($value);
                }
                
                foreach($value as $_val) {
                    $searchFields[$field][] = $_val;
                }
            
            }
        }
        
        return $searchFields;
    }
    
    
    
    protected function _getPostSearchFields()
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

