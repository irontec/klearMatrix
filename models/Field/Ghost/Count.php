<?php
class KlearMatrix_Model_Field_Ghost_Count extends KlearMatrix_Model_Field_Ghost_Abstract
{
    protected $_config;
    protected $_parentField;

    protected $_templateFields = array();

    protected $_searchedValues;

    protected $_model;

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
//         $this->_parentField->getColumn()->markAsDirty();

        return $this;
    }

    public function init()
    {
        if (!$this->_parentField) {
            throw new Klear_Exception_MissingConfiguration('Missing parent host for Ghost_Concat');
        }

        $mainModel = $this->_parentField->getColumn()->getModel();

//         foreach ($this->_config->getRaw()->source->template as $field => $fConfig) {
//             $curField = array();
//             $curField['getter'] = 'get' . $mainModel->columnNameToVar($field);
//             if (is_string($fConfig)) {
//                 $curField['literal'] =  Klear_Model_Gettext::gettextCheck($fConfig);
//             } else {
//                 if (isset($fConfig->literal)) {
//                     $curField['literal'] =  Klear_Model_Gettext::gettextCheck($fConfig->literal);
//                 } else {
//                     $curField['literal'] = '%' . $field . '%';
//                 }

//                 if (isset($fConfig->checkEmpty)) {
//                     $curField['checkEmpty'] = (bool)$fConfig->checkEmpty;
//                 }

//                 if (isset($fConfig->noSearch)) {
//                     $curField['noSearch'] = (bool)$fConfig->noSearch;
//                 }
//             }

//             $this->_templateFields[$field] = $curField;

//         }
//         $this->_templateFields["name"] = array(
//         		"getter" => "getName",
//         		"literal" => "hola"
//         		);

    }

//     protected function _highlightFoundString($returnStr)
//     {
//         if (sizeof($this->_searchedValues) == 0) {
//             return $returnStr;
//         }

//         foreach ($this->_searchedValues as $value) {
//             $returnStr = preg_replace(
//                 '/('.preg_quote(trim($value)).')(?=[^><]*<|.$)/i',
//                 '<span class="ui-state-highlight">\1</span>',
//                 $returnStr
//             );
//         }
//         return $returnStr;



//     }

    public function getValue($model)
    {
		$this->_model = $model;
        $mapperName = $this->_config->getRaw()->source->mapper;
        if(!$mapperName){
        	throw new Klear_Exception_MissingConfiguration('Missing mapper for Ghost_count');
        }
		if ($countCondition = $this->_config->getRaw()->source->countCondition){
        	$pattern = "|%(.*)%|U";
        	if (preg_match_all($pattern, $countCondition, $matches)){
        		$countCondition = preg_replace_callback($pattern, function($matches){
        		$fieldName = ucfirst($matches[1]);
        		$getter = "get".$fieldName;
        		$fieldValue = $this->_model->$getter();
        		return $fieldValue;
        		}, $countCondition);
        	}
        	$countWhere = $countCondition;
        } else {
        	if($relatedField = $this->_config->getRaw()->source->relatedField){
        		$countWhere = $relatedField." = ".$model->getPrimaryKey();
        	} else {
        		$countWhere = "";
        	}
        }
        $mapper = new $mapperName();
        $nCount = $mapper->countByQuery($countWhere);

        if ($totalCondition = $this->_config->getRaw()->source->totalCondition){
        	$pattern = "|%(.*)%|U";
        	if (preg_match_all($pattern, $totalCondition, $matches)){
        		$totalCondition = preg_replace_callback($pattern, function($matches){
        			$fieldName = ucfirst($matches[1]);
        			$getter = "get".$fieldName;
        			$fieldValue = $this->_model->$getter();
        			return $fieldValue;
        		}, $totalCondition);
        	}
        	$nTotal = $mapper->countByQuery($totalCondition);
        	$percent = round(($nCount/$nTotal*100),2);
        	if ($template = $this->_config->getRaw()->source->template){
				$template = str_replace("%count%", $nCount, $template);
				$template = str_replace("%total%", $nTotal, $template);
				$template = str_replace("%percent%", $percent, $template);
				$returnStr = $template;
        	} else { //'%count% / %total% (%percent%%)'
        		$returnStr = $nCount." / ".$nTotal." (".$percent."%)";
        	}
        } else {
        	$returnStr = $nCount;
        }







//         foreach ($this->_templateFields as $field => $fConfig) {

//             $value = $model->{$fConfig['getter']}();

//             if (isset($fConfig['checkEmpty'])) {
//                 if (empty($value)) {
//                     continue;
//                 }
//             }

//             $returnStr .= str_replace('%' . $field . '%', $value, $fConfig['literal']);

//         }
//         return $this->_highlightFoundString($returnStr);
		return $returnStr;
    }


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

//     public function getOrder($model)
//     {
//         $model; // Avoid PMD UnusedLocalVariable warning
//         return array_keys($this->_templateFields);
//     }


}