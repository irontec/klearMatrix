<?php


class KlearMatrix_Model_Field_Multiselect_Mapper extends KlearMatrix_Model_Field_Multiselect_Abstract
{
    
    protected $_relationMapper;
    
    protected $_relatedMapper;
    protected $_fieldConfig;
    
    public function init() {
    
        $parsedValues = new Klear_Model_KConfigParser;
        $parsedValues->setConfig($this->_config->config);
    
        
        // Mapper de las relaciones. Aquí se guardarán las coincidencias.
        $this->_relationMapper = $parsedValues->getProperty("relationMapper");
        
        $this->_relatedMapper = $parsedValues->getProperty("relatedMapperName");
        $this->_fieldName = $parsedValues->getProperty("relatedFieldName");
    
        if (is_object($this->_fieldName)) {
            $_fieldConfig = new Klear_Model_KConfigParser;
            $_fieldConfig->setConfig($this->_fieldName);
    
            $fields = $_fieldConfig->getProperty("fields");
            $fieldTemplate = $_fieldConfig->getProperty("template");
    
        } else {
    
            // Si sólo queremos mostrar un campo, falseamos un template simple
            
            $fields = array($this->_fieldName);
            $fieldTemplate = '%' . $this->_fieldName . '%';
        }
    
    
        //TODO: Meter el where y order en .yaml si fuera necesario
        //TODO: Control de errores?
        $_where = null;
        $_order = null;
    
        $dataMapperName = $this->_relatedMapper;
        $dataMapper = new $dataMapperName;
    
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
    
    
    public function prepareValue($value) {

        if (sizeof($value) == 0) {
            return array();
            Throw New Zend_Exception('Estructura de valores inválida para mapper multiselect');
        }
        
        // Instancio el mapper de los valores a relacionar
        // dentro de los modelos de relation
        $dataMapperName = $this->_relatedMapper;
        $dataMapper = new $dataMapperName;
        
        // Recupero el nombre de la tabla, para poder llegar a la FK del modelo de relation
        $tableRelatedName = $dataMapper->getDbTable()->getTableName();
        
        $retStruct = array();
        $relationIndex = array();
        // Itero en value, que supuestamente es un array de modelos de relación 
        foreach($value as $model) {
            if ( (!is_object($model)) || 
                    (!$model->getMapper() instanceof $this->_relationMapper) ) {
                
                Throw New Zend_Exception('El valor ('.get_class($model).') no tiene una estructura válida para mapper multiselect ('.$this->_relationMapper.')');
                
            }
            
            $fkName = false;
            $parents = $model->getParentList();
            foreach ($parents as $_fk => $parentData) {
                if ($parentData['table_name'] == $tableRelatedName) {
                    $fkName = $_fk;
                    break;
                }
            }
            
            if (false === $fkName) {
                Throw New Zend_Exception('No se encuentra el valor de la FK.');
            }
            
            // Recuperamos el atributo de bd de la tabla de relación, que coincide con la clave foránea de la tabla relacionada
            $columnName = $model->getMapper()->getDbTable()->getReferenceMap($fkName);
            
            $relationAttributte = $model->columnNameToVar($columnName);
            
            
            
            $retStruct = array(
                    'pk'=> $model->getPrimaryKey(),
                    'relatedId'=>$model->{'get' . $relationAttributte}()
                    );
            
             
            $relationIndex[$retStruct['relatedId']] = $retStruct['pk']; 
            $ret[$retStruct['pk']] = $retStruct;     
        }
        
        return array("relStruct"=>$ret,"relIndex"=>$relationIndex);
        
    }
    
    
    
    /* $value => Array con los ID de los objetos related
     * $original => array de modelos de relation.
     *     
     * 
     * @see KlearMatrix_Model_Field_Multiselect_Abstract::filterValue()
     */
    public function filterValue($value, $original) {
        // Devolveremos un array de modelos de relaciones
        $retRelations = array();
        
        
        // Recupero el nombre de la tabla, para poder llegar a la FK del modelo de relation
        $dataMapperName = $this->_relatedMapper;
        $dataMapper = new $dataMapperName;
        $tableRelatedName = $dataMapper->getDbTable()->getTableName();
        
        $fkColumn = false;

        if (is_array($original)) {
            
            foreach ($original as $model) {
                if ( (!is_object($model)) ||
                        (!$model->getMapper() instanceof $this->_relationMapper) ) {
                    
                    Throw New Zend_Exception('El valor ('.get_class($model).') no tiene una estructura válida para mapper multiselect ('.$this->_relationMapper.')');
                }
                
                if (false === $fkColumn) {
                    $fkColumn = $model->getColumnForParentTable($tableRelatedName);
                }
                
                $getter = 'get' . $fkColumn;
                
                if ( (is_array($value)) && ($idx = array_search($model->{$getter}(),$value)) ) {
                    $retRelations[] = $model;
                    unset($value[$idx]);
                }
            }
        }
        $relationMapperName = $this->_relationMapper; 
        if (is_array($value)) {
            foreach ($value as $idRelated) {
            
                $relationMapper = new $relationMapperName;
                $relationModel = $relationMapper->loadModel(null);
            
                if (false === $fkColumn) {
                    $fkColumn = $relationModel->getColumnForParentTable($tableRelatedName);
                }
                $setter = 'set'.$fkColumn;
                
                $relationModel->{$setter}($idRelated);
                $retRelations[] = $relationModel;
            }
        }
        return $retRelations;
        
        
    }
    
    
}

