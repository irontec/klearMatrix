<?php

      
class KlearMatrix_Model_Field_Select_Mapper extends KlearMatrix_Model_Field_Select_Abstract
{

    
    public function init() {
        
        $parsedValues = new Klear_Model_KConfigParser;
        $parsedValues->setConfig($this->_config->config);
        
        $_mapper = $parsedValues->getProperty("mapperName");
        $_fieldName = $parsedValues->getProperty("fieldName");

        
        //TODO: Meter el where y order en .yaml si fuera necesario
        //TODO: Control de errores?
        $_where = null;
        $_order = null;
        
        $dataMapper = new $_mapper;

        if ($results = $dataMapper->fetchList($_where,$_order)) {
            foreach ($results as $dataModel) {
                
                $_getter = 'get' . $dataModel->columnNameToVar($_fieldName);
                
                $this->_items[] = $dataModel->$_getter();
                $this->_keys[] = $dataModel->getPrimaryKey();
                
            }
        }
           
    }
    
    
    
}