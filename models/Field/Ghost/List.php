<?php
class KlearMatrix_Model_Field_Ghost_List extends KlearMatrix_Model_Field_Ghost_Abstract
{
    protected $_config;
    protected $_parentField;

    protected $_templateFields = array();

    protected $_searchedValues;
    
    protected $_extraDataAttributes = array();
    protected $_extraDataAttributesValues = array();

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
    	foreach ($fields as $fieldName) {
    		$getter = 'get' . ucfirst($dataModel->columnNameToVar($fieldName));
    		$replace['%' . $fieldName . '%'] = $dataModel->$getter();
    	}
    
    	return str_replace(array_keys($replace), $replace, $fieldsTemplate);
    }
    
    protected function _setOptions($results)
    {
    	if ($results) {
    		foreach ($results as $dataModel) {
    			$this->_keys[] = $dataModel->getPrimaryKey();
    			$this->_items[] = $this->_getItemValue($dataModel);
    
    			$this->_setValuesForExtraAttributes($dataModel, $dataModel->getPrimaryKey());
    
    			
    		}
    	}
    }
    
    public function init()
    {
        if (!$this->_parentField) {
            throw new Klear_Exception_MissingConfiguration('Missing parent host for Ghost_Concat');
        }

    }


    public function getValue($model)
    {
        $mainModel = $this->_parentField->getColumn()->getModel();
        
        $mapperName = $this->_config->getProperty("config")->mapperName;
        $dataMapper = new $mapperName;
        
        if (isset($this->_config->getProperty('config')->extraDataAttributes)) {
        
        	$extraAttrs = $this->_config->getProperty('config')->extraDataAttributes;
        	$this->_extraDataAttributes = $this->_parseExtraAttrs($extraAttrs, $dataMapper);
        }
        
        $whereParts = array();
        if (isset($this->_config->getProperty('config')->filterField)) {
            $whereParts[] = "(".$this->_config->getProperty('config')->filterField." = '".$model->getPrimaryKey()."')";
            $whereParts[] = "(".$this->_config->getProperty('config')->filterField." = '".$model->getPrimaryKey()."')";
        }
        if ($filterWhere = $this->_getFilterWhere() && trim($filterWhere)!="") {
            $whereParts[] = $filterWhere;
        }
        
        $where = implode(" and ", $whereParts);

        $order = $this->_config->getProperty('config')->order;
        $results = $dataMapper->fetchList($where, $order);
        $this->_setOptions($results);
        
        
        $ulParts = array();
        foreach ($this->_items as $i=>$item) {
            $id = $this->_keys[$i];
            $li = '<li data-id="' . $id . '">';
            $li.= $item;
            $li.= '</li>';
            $ulParts[] = $li;
        }
        
        $ul = '<ul class="ghostList">';
        $ul.= implode("\n", $ulParts);
        $ul.= '</ul>'; 
        return $ul;
    }


    public function getSearch($values, $searchOps, $model)
    {
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

    }

    public function getOrder($model)
    {
        $model; // Avoid PMD UnusedLocalVariable warning
        return array();
//         return array_keys($this->_templateFields);
    }


}