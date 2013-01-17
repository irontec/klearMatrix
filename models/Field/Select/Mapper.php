<?php

class KlearMatrix_Model_Field_Select_Mapper extends KlearMatrix_Model_Field_Select_Abstract
{
    protected $_showOnSelect = array();
    protected $_hideOnSelect = array();

    protected $_js = array(
        "/js/plugins/jquery.klearmatrix.select.js"
    );

    public function init()
    {
        $mapperName = $this->_config->getProperty("config")->mapperName;
        $dataMapper = new $mapperName;

        $where = $this->_getFilterWhere();
        $order = $this->_config->getProperty('config')->order;

        $results = $dataMapper->fetchList($where, $order);

        if ($results) {

            $fields = $this->_getFields();
            $fieldsTemplate = $this->_getFieldsTemplate();

            foreach ($results as $dataModel) {

                $replace = array();
                foreach ($fields as $fieldName) {

                    $getter = 'get' . ucfirst($dataModel->columnNameToVar($fieldName));
                    $replace['%' . $fieldName . '%'] = $dataModel->$getter();
                }

                $this->_keys[] = $dataModel->getPrimaryKey();
                $this->_items[] = str_replace(array_keys($replace), $replace, $fieldsTemplate);

                $this->_initVisualFilter($dataModel);
            }
        }
    }

    protected function _getFilterWhere()
    {
        $filterClassName = $this->_config->getProperty('config')->filterClass;
        if ($filterClassName) {

            $filter = new $filterClassName;

            if ($filter->setRouteDispatcher($this->_column->getRouteDispatcher())) {

                return $filter->getCondition();
            }
        }
        return null;
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
}

//EOF