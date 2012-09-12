<?php

class KlearMatrix_Model_Field_Select_Mapper extends KlearMatrix_Model_Field_Select_Abstract
{
    protected $_showOnSelect = array();
    protected $_hideOnSelect = array();

    public function init()
    {
        $_mapper = $this->_config->getProperty("config")->mapperName;
        $_fieldConf = $this->_config->getProperty('config')->fieldName;

        $visualFilter = $this->_config->getProperty('config')->visualFilter;

        $_where = null;

        if ($filterClassName = $this->_config->getProperty('config')->filterClass) {

            $filter = new $filterClassName;

            if ($filter->setRouteDispatcher($this->_column->getRouteDispatcher())) {

                $_where = $filter->getCondition();
            }
        }

        $_order = $this->_config->getProperty('config')->order;

        if (is_object($_fieldConf)) {

            $_fieldConfig = new Klear_Model_ConfigParser;
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
        $results = $dataMapper->fetchList($_where, $_order);
        if ($results) {

            foreach ($results as $dataModel) {

                $replace = array();
                foreach ($fields as $_fieldName) {

                    $_getter = 'get' . $dataModel->columnNameToVar($_fieldName);
                    $replace['%' . $_fieldName . '%'] = $dataModel->$_getter();
                }

                $this->_items[] = str_replace(array_keys($replace), $replace, $fieldTemplate);
                $this->_keys[] = $dataModel->getPrimaryKey();

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

                        $_getter = 'get' . $dataModel->columnNameToVar($key);
                        $value = $dataModel->$_getter();

                        if ($config->$value) {

                            $this->_showOnSelect[$dataModel->getPrimaryKey()] = $config->$value->show;
                            $this->_hideOnSelect[$dataModel->getPrimaryKey()] = $config->$value->hide;
                        }
                    }
                }
            }
        }
    }

    public function getExtraConfigArray()
    {
        $ret = array();

        if (sizeof($this->_showOnSelect)>0 || sizeof($this->_hideOnSelect)>0) {

            $ret['visualFilter']['show'] = (array)$this->_showOnSelect;
            $ret['visualFilter']['hide'] = (array)$this->_hideOnSelect;
        }

        return $ret;
    }
}

//EOF