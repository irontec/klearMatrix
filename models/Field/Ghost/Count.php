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
        
        $orderMethod = $this->_config->getProperty("orderMethod");
        $isSortable = true;
        $sortable = $this->_config->getProperty("sortable");
        if (!is_null($sortable)) {
        	$isSortable = (bool) $sortable;
        }

        if ($isSortable) {
	        if (!is_null($orderMethod)) {
		        $this->_parentField->setOrderMethod($orderMethod);        	
	        } else {
	        	$this->_parentField->setOrderMethod('getOrder');
	        }        	
        }
        
        $this->_parentField->setGetterMethod('getValue');
        return $this;
    }

    public function init()
    {
        if (!$this->_parentField) {
            throw new Klear_Exception_MissingConfiguration('Missing parent host for Ghost_Count');
        }


        $this->_isSearchable = false;
        $this->_isSortable = false;
        $mainModel = $this->_parentField->getColumn()->getModel();
    }

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
        	} else {
        		$returnStr = $nCount." / ".$nTotal." (".$percent."%)";
        	}
        } else {
        	$returnStr = $nCount;
        }
		return $returnStr;
    }
}