<?php


class KlearMatrix_Model_Field_Select_Mapper extends KlearMatrix_Model_Field_Select_Abstract
{


    public function init() {


        $_mapper = $this->_config->getProperty("config")->mapperName;

        $_fieldConf = $this->_config->getProperty('config')->fieldName;

        $_where = null;
        if ($filterClassName = $this->_config->getProperty('config')->filterClass) {

            $filter = new $filterClassName;

            if ($filter->setRouteDispatcher($this->_column->getRouteDispatcher())) {
                $_where = $filter->getCondition();

            }
        }


        $_order = $this->_config->getProperty('config')->order;

        if (is_object($_fieldConf)) {
            $_fieldConfig = new Klear_Model_KConfigParser;
            $_fieldConfig->setConfig($_fieldConf);

            $fields = $_fieldConfig->getProperty("fields");
            $fieldTemplate = $_fieldConfig->getProperty("template");

        } else {

             // Si sólo queremos mostrar un campo, falseamos un template simple
            $_fieldName = $_fieldConf;

            $fields = array($_fieldName);
            $fieldTemplate = '%' . $_fieldName . '%';
        }


        $dataMapper = new $_mapper;

        if ($results = $dataMapper->fetchList($_where,$_order)) {
            foreach ($results as $dataModel) {

                $replace = array();
                foreach ($fields as $_fieldName) {
                    $_getter = 'get' . $dataModel->columnNameToVar($_fieldName);
                    $replace['%' . $_fieldName . '%'] = $dataModel->$_getter();
                }

                $this->_items[] = str_replace(array_keys($replace),$replace,$fieldTemplate);
                $this->_keys[] = $dataModel->getPrimaryKey();

            }
        }

    }



}