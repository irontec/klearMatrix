<?php
class KlearMatrix_Model_Field_Ghost_List extends KlearMatrix_Model_Field_Ghost_Abstract
{
    protected $_config;
    protected $_parentField;

    protected $_templateFields = array();

    protected $_searchedValues;

    protected $_extraDataAttributes = array();
    protected $_extraDataAttributesValues = array();

    protected $_items = array();
    protected $_keys = array();

    protected $_dirty;

    public function setConfig(Zend_Config $config)
    {
        $kconfig = new Klear_Model_ConfigParser;
        $kconfig->setConfig($config);

        $this->_config = $kconfig;
        return $this;
    }

    public function configureHostFieldConfig(KlearMatrix_Model_Field_Abstract $field)
    {
        $this->_parentField = $field;
        $this->_parentField->setSearchMethod('getSearch');
        $this->_parentField->setOrderMethod('getOrder');
        $this->_parentField->setGetterMethod('getValue');

        // por definición toda las columnas de Concat serán dirty (nos ahorramos ponerlo, y habrá HTML casi siempre)
        $this->_parentField->getColumn()->markAsDirty();

        return $this;
    }

    protected function _getFilterWhere()
    {
        $this->_config->getProperty('config')->filterClass;
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

//     protected function _getLabels()
//     {
//         $fieldName = $this->_config->getProperty('config')->fieldName;

//         if (!is_object($fieldName)) {
//             return array($fieldName);
//         }

//         $fieldConfig = new Klear_Model_ConfigParser();
//         $fieldConfig->setConfig($fieldName);
//         return $fieldConfig->getProperty("labels");
//     }

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

    protected function _getItemValue($dataModel)
    {
        $customValueMethod = $this->_config->getProperty('config')->customValueMethod;
        if ($customValueMethod) {
            return $dataModel->$customValueMethod();
        }

        $fields = $this->_getFields();
        $fieldsTemplate = Klear_Model_Gettext::gettextCheck($this->_getFieldsTemplate());
        $replace = array();

        $tableFields = array();
        foreach ($fields as $key => $fieldName) {
            if (is_object($fieldName)) {
                $tableFields[$key] = $fieldName;
            } else {
                $getter = 'get' . ucfirst($dataModel->columnNameToVar($fieldName));
                $replace['%' . $fieldName . '%'] = $dataModel->$getter();
            }
        }
        if (count($tableFields) > 0) {
            return $tableFields;
        }
        return str_replace(array_keys($replace), $replace, $fieldsTemplate);
    }

    protected function _setOptions($results)
    {
        $this->_keys = array();
        $this->_items = array();

        if ($results) {
            foreach ($results as $dataModel) {
                $this->_keys[] = $dataModel->getId();
                $this->_items[] = $this->_getItemValue($dataModel);

                $this->_setValuesForExtraAttributes($dataModel, $dataModel->getId());

            }
        }
    }

    public function init()
    {
        if (!$this->_parentField) {
            throw new Klear_Exception_MissingConfiguration('Missing parent host for Ghost_Concat');
        }
        $this->_dirty = $this->_config->getProperty('dirty');

    }


    public function getValue($model)
    {
        if (isset($this->_config->getProperty('config')->extraDataAttributes)) {
            $extraAttrs = $this->_config->getProperty('config')->extraDataAttributes;
            $this->_extraDataAttributes = $this->_parseExtraAttrs($extraAttrs, $dataMapper);
        }

        $whereParts = array();

        if (isset($this->_config->getProperty('config')->filterField)) {
            if ($GLOBALS['sf']) {
                $whereParts[] = "(".$this->_config->getProperty('config')->filterField." = '".$model->getId()."')";
            } else if (!$GLOBALS['sf']) {
                $whereParts[] = "(".$this->_config->getProperty('config')->filterField." = '".$model->getPrimaryKey()."')";
            }
        }

        if (isset($this->_config->getProperty('config')->forcedValues)) {
            foreach ($this->_config->getProperty('config')->forcedValues as $fieldName => $valueField) {
                $whereParts[] = "(".$fieldName." = '".$valueField."')";
            }
        }

        $filterWhere = $this->_getFilterWhere();
        if ($filterWhere && trim($filterWhere)!="") {
            $whereParts[] = $filterWhere;
        }

        $where = implode(" and ", $whereParts);

        $order = $this->_config->getProperty('config')->order;

        if (is_object($order)) {
            $orderParts = array();
            foreach ($order as $orderField) {
                $orderParts[] = $orderField;
            }
            $order = $orderParts;
        }

        if ($GLOBALS['sf']) {
            $dataGateway = \Zend_Registry::get('data_gateway');
            $entity = $this->_config->getProperty("config")->entity;
            $results = $dataGateway->findBy($entity, [$where]);
        } else if (!$GLOBALS['sf']) {
            $results = $dataMapper->fetchList($where, $order);
        }

        $this->_setOptions($results);
        $options = $this->_parseOptions();

        $asTable = $this->_config->getProperty('config')->showAsTable;
        if ($asTable === true) {
            $content = $this->_getTable($options, $results, $model);
            $ghostCounterClass = "ghostTableCounter";
        } else {
            $content = $this->_getList($options);
            $ghostCounterClass = "ghostListCounter";
        }

        $noSearch = $this->_config->getProperty('config')->noSearch;
        $ret = "";
        if (is_null($noSearch) || $noSearch !== true) {
            $ret .= '<div class="'.$ghostCounterClass.'">';
            $ret .= '<span class="ui-icon ui-icon-search"></span>';
            $ret .= '<input type="text" /><br>(<span class="counter"></span> items)</div>';
        }
        $ret .= $content;
        $ret .= '<div class="ghostListOptions">';
        if ($asTable !== true) {
            foreach ($options as $option) {
                $option->setParentHolderSelector("li");
                $ret .= $option->toAutoOption();
            }
        }
        $ret .= '</div>';
        return $ret;
    }


    protected function _parseOptions()
    {
        $fieldOptions = new KlearMatrix_Model_Option_Collection();
        if (!isset($this->_config->getProperty('config')->options)) {
            return array();
        }

        $this->_parseOptionSection($fieldOptions, 'screen');
        $this->_parseOptionSection($fieldOptions, 'dialog');
        $this->_parseOptionSection($fieldOptions, 'command');

        return $fieldOptions;
    }

    protected function _parseOptionSection($fieldOptions, $itemName)
    {


        $mainRouter = $this->_parentField->getColumn()->getRouteDispatcher();
        $options = $this->_config->getProperty('config')->options;

        $collection = strtolower($itemName) . 's';
        $getter = 'get' . ucfirst($itemName) . 'Config';
        $className = 'KlearMatrix_Model_Option_'  . ucfirst($itemName);

        if (!isset($options->$collection)) {
            return;
        }

        foreach ($options->$collection as $item => $enabled) {
            $enabled = (bool)$enabled;
            if (false === $enabled) {
                continue;
            }

            $itemOption = new $className;
            $itemOption->setName($item);
            $itemOption->setConfig($mainRouter->getConfig()->$getter($item));
            $fieldOptions->addOption($itemOption);
        }
    }

//TODO: hacer el getSearch
//     public function getSearch($values, $searchOps, $model)
//     {
//         $searchOps; // Avoid PMD UnusedLocalVariable warning
//         $model; // Avoid PMD UnusedLocalVariable warning
//         $this->_searchedValues = $values;
//         $masterConditions = array();
//         $fieldValues = array();
//         $namedParams = $this->_parentField->getColumn()->namedParamsAreSupported();
//         $cont = 0;
//         foreach ($this->_templateFields as $field => $fConfig) {
//             $auxCondition = array();
//             if (isset($fConfig['noSearch']) &&
//                 $fConfig['noSearch']) {
//                 continue;
//             }
//             foreach ($values as $value) {
//                 $template = $field . $cont++;
//                 if ($namedParams) {
//                     $auxCondition[] =  $field . ' like :' . $template;
//                     $fieldValues[$template] = '%' . $value . '%';
//                 } else {
//                     $auxCondition[] = $field . ' like ?';
//                     $fieldValues[] = '%' . $value . '%';
//                 }
//             }
//             $masterConditions[] = '(' . implode(' or ', $auxCondition) . ')';
//         }
//         return array(
//                 '(' . implode(' or ', $masterConditions). ')',
//                 $fieldValues
//         );
//     }

    public function getOrder($model)
    {
        $model; // Avoid PMD UnusedLocalVariable warning
        return array();
    }

    protected function _getFilterCondition($filter)
    {
        $mainRouter = $this->_parentField->getColumn()->getRouteDispatcher();
        $filter->setRouteDispatcher($mainRouter);
        return $filter->getCondition();
    }

    protected function _getList($options)
    {
        $ulParts = array();
        $i = 0;
        foreach ($this->_items as $i=>$item) {
            $id = $this->_keys[$i];
            $class = "";
            if ($i%2 == 0){
                $class = 'class="highlight"';
            }
            $li = '<li data-id="' . $id . '" '.$class.'>';
            $li .= $item;
            foreach ($options as $option) {
                $option->setParentHolderSelector("li");
//                 $li .= $option->toAutoOption();
                $li .= '<span class="opClone" data-link="'.$option->getName().'"></span>';
            }
            $li .= '</li>';
            $ulParts[] = $li;
        }
        $list = '<ul class="ui-widget-content ui-corner-all ghostList">';
        $list .= implode("\n", $ulParts);
        $list .= '</ul>';
        return $list;
    }

    protected function _getTable($options, $results, $model = null)
    {
        $tableParts = array();
        $i = 0;
        $tr = '<tr>';
        $fields = $this->_getFields();
        foreach ($fields as $key => $value) {
            $width = "auto";
            if (is_object($value)) {
                $fieldConfig = new Klear_Model_ConfigParser();
                $fieldConfig->setConfig($value);
                $label = Klear_Model_Gettext::gettextCheck($fieldConfig->getProperty("title"));
                if ($fieldConfig->getProperty("percentWidth")) {
                    $width = $fieldConfig->getProperty("percentWidth")."%";
                }
            } else {
                $label = $value;
            }
            $tr .= '<th class="ui-widget-header" data-field="'.$label.'" style="cursor: pointer; width: '.$width.';">';
            $tr .= '<span class="title">'.$label.'</span>';
            $tr .= '</th>';
        }
        if (isset($this->_config->getProperty('config')->options)) {
            $tr .= '<th class="ui-widget-header notSortable" data-field="_fieldOptions">';
            $optionsTranslated = Klear_Model_Gettext::gettextCheck("_('Options')");
            $tr .= '<span class="title">'.$optionsTranslated.'</span>';
            $tr .= '</th>';
        }
        $tr .= "</tr>";

        $klearBootstrap = Zend_Controller_Front::getInstance()
            ->getParam("bootstrap")->getResource('modules')->offsetGet('klear');
        $siteLanguage = $klearBootstrap->getOption('siteConfig')->getLang();
        $currentLanguage = $siteLanguage->getLanguage();

        $rows = array();
        $dataModels = array();
        foreach ($results as $dataModel) {
//             $currentLanguage = $dataModel->getCurrentLanguage();

            /**
             * @todo
             */
            $dataMlFields = []; //array_keys($dataModel->getMultiLangColumnsList());
            $fieldsValues = array();
            foreach ($fields as $key => $value) {

                $fieldName = $key;

                if (is_object($value)) {

                    $fieldConfig = new Klear_Model_ConfigParser();
                    $fieldConfig->setConfig($value);

                    if (in_array($fieldName, $dataMlFields)) {
                        $getter = 'get' . ucfirst($fieldName) . ucfirst($currentLanguage);
                    } else if ($fieldConfig->getProperty("entity")) {
                        $getter = 'get' . ucfirst($fieldName) . 'Id';
                    } else {
                        $getter = 'get' . ucfirst($fieldName);
                    }

                    if ($fieldConfig->getProperty("entity")) {
                        $entityClass = $fieldConfig->getProperty("entity");
                        $id = $dataModel->$getter();

                        $dataGateway = \Zend_Registry::get('data_gateway');
                        $targetModel = $dataGateway->find($entityClass, $id);

                        if (is_null($targetModel)) {
                            $fieldsValues[] = $id;
                            continue;
                        }
                        /**
                         * @todo
                         */
                        $targetMlFields = []; //array_keys($targetModel->getMultiLangColumnsList());
                        $mapperField = $fieldConfig->getProperty("field");
                        if (is_object($mapperField)) {
                            $pattern = $fieldConfig->getProperty("pattern");
                            $_fields = array();
                            foreach ($mapperField as $_field) {
                                if (in_array($_field, $targetMlFields)) {
                                    $getter = 'get' . ucfirst($_field).ucfirst($currentLanguage);
                                } else {
                                    $getter = 'get' . ucfirst($_field);
                                }
                                $_fields['%'.$_field.'%'] = $targetModel->$getter();
                            }
                            $fieldsValues[] = str_replace(array_keys($_fields), $_fields, $pattern);
                        } else {
                            $getter = 'get' . ucfirst($mapperField);
                            $fieldsValues[] = $targetModel->$getter();
                        }
                    } else {

                        $fieldsOptions = $this->_config->getProperty('config')->fieldName->fields;

                        if (!is_null($fieldsOptions->$fieldName)) {
                            if (!is_null($fieldsOptions->$fieldName->mapValues)) {

                                $mapValues = $fieldsOptions->$fieldName->mapValues;
                                $modelValue = $dataModel->$getter();

                                $fieldsValues[] = Klear_Model_Gettext::gettextCheck(
                                    $mapValues->$modelValue
                                );

                            } else if (!is_null($fieldsOptions->$fieldName->type)) {
                                $type = $fieldsOptions->$fieldName->type;
                                if ($type == "datetime") {
                                    $value = $dataModel->$getter(true);
                                    $value->setTimezone(date_default_timezone_get());
                                    $fieldsValues[] = $value;
                                } else if ($type == "date") {
                                    $date = $dataModel->$getter(true);
                                    $date->setTimezone(date_default_timezone_get());
                                    $dateParts = explode(" ", $date->getDate()->toString());
                                    $value = $dateParts[0];
                                    $fieldsValues[] = $value;
                                } else {
                                    $fieldsValues[] = $dataModel->$getter();
                                }
                            } else {
                                $fieldsValues[] = $dataModel->$getter();
                            }
                        } else {
                            $fieldsValues[] = $dataModel->$getter();
                        }

                    }
                } else {
                    $fieldName = $value;
                    if (in_array($fieldName, $dataMlFields)) {
                        $getter = 'get' . ucfirst($dataModel->columnNameToVar($fieldName)).ucfirst($currentLanguage);
                    } else {
                        $getter = 'get' . ucfirst($dataModel->columnNameToVar($fieldName));
                    }
                    $fieldsValues[] = $dataModel->$getter();
                }
            }

            $rows[] = $fieldsValues;
            $dataModels[] = $dataModel;
        }

//         $defaultOption = null;
//         $optionsConfig = $this->_config->getProperty('config')->options;
//         if ($optionsConfig) {
//             $defaultConfig = new Klear_Model_ConfigParser();
//             $defaultConfig->setConfig($optionsConfig);
//             $defaultOptionName = $defaultConfig->getProperty("default");
//             if ($defaultOptionName) {
//                 foreach ($options as $option) {
//                     if ($option->getName() == $defaultOptionName) {
//                         $defaultOption = $option;
//                     }
//                 }
//             }
//         }

        foreach ($rows as $i=>$fieldsValues) {
            $id = $this->_keys[$i];
            $tr .= '<tr class="hideable" data-id="'. $id . '">';

            foreach ($fieldsValues as $value) {
                if (!$this->_dirty) {
                    $value = htmlentities($value);
                }
                $tr .= '<td class="ui-widget-content default">'.$value.'</td>';
            }

            if (count($options) > 0) {
                $tr .= '<td class="ui-widget-content options">';
                foreach ($options as $option) {
                    $filterClassName = $option->getConfig()->getProperty("filterClass");
                    if (!is_null($filterClassName)) {
                        $filterClass = new $filterClassName($dataModels[$i], $model);
                        if (!$filterClass->show()) {
                            continue;
                        }
                    }
                    $option->setParentHolderSelector("tr");
                    $tr .= $option->toAutoOption();
                }
                $tr .= '</td></tr>';
            }
        }
        $tableParts[] = $tr;
        $table = '<div class="ui-widget-content ui-corner-all ghostTableContainer">';
        $table .= '<table class="kMatrix ghostTable">';
        $table .= implode("\n", $tableParts);
        $table .= '</table>';
        $table .= '</div>';

        return $table;

    }

}