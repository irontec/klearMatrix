<?php

class KlearMatrix_Model_Field_Multiselect_Mapper extends KlearMatrix_Model_Field_Multiselect_Abstract
{
    protected $_parsedValues;

    protected $_relationMapper;
    protected $_relationProperty;
    protected $_relatedMapper;

    protected $_editableFields;

    public function init()
    {
        $this->_parsedValues = new Klear_Model_ConfigParser;
        $this->_parsedValues->setConfig($this->_config->config);

        // Mapper de las relaciones. Aquí se guardarán las coincidencias.
        $this->_relationMapper = $this->_parsedValues->getProperty("relationMapper");
        $this->_relationProperty = $this->_parsedValues->getProperty("relationProperty");
        $this->_relatedMapper = $this->_parsedValues->getProperty("relatedMapperName");

        $dataMapperName = $this->_relatedMapper;
        $dataMapper = new $dataMapperName;

        $where = $this->_getFilterWhere();
        $order = $this->_parsedValues->getProperty("relatedOrder");

        $results = $dataMapper->fetchList($where, $order);

        if ($results) {

            $relatedFields = $this->_getRelatedFields();
            $relatedFieldsTemplate = $this->_getRelatedFieldsTemplate();

            foreach ($results as $dataModel) {

                $replace = array();
                foreach ($relatedFields as $fieldName) {

                    $getter = 'get' . ucfirst($dataModel->columnNameToVar($fieldName));
                    $replace['%' . $fieldName . '%'] = $dataModel->$getter();
                }

                $this->_keys[] = $dataModel->getPrimaryKey();
                $this->_items[] = str_replace(array_keys($replace), $replace, $relatedFieldsTemplate);
            }
        }
    }

    protected function _getFilterWhere()
    {
        //TODO: Control de errores?
        $filterClassName = $this->_parsedValues->getProperty("filterClass");
        if ($filterClassName) {

            $filter = new $filterClassName;

            if ($filter->setRouteDispatcher($this->_column->getRouteDispatcher())) {

                return $filter->getCondition();
            }
        }
        return null;
    }

    protected function _getRelatedFields()
    {
        $fieldName = $this->_parsedValues->getProperty("relatedFieldName");

        if (!is_object($fieldName)) {
            return array($fieldName);
        }

        $fieldConfig = new Klear_model_ConfigParser();
        $fieldConfig->setConfig($fieldName);
        return $fieldConfig->getProperty('fields');
    }

    protected function _getRelatedFieldsTemplate()
    {
        $fieldName = $this->_parsedValues->getProperty("relatedFieldName");

        if (!is_object($fieldName)) {
            return '%' . $fieldName . '%';
        }

        $fieldConfig = new Klear_model_ConfigParser();
        $fieldConfig->setConfig($fieldName);
        return $fieldConfig->getProperty("template");
    }

    public function prepareValue($value, $model)
    {
        if (sizeof($value) == 0) {

            return array();
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
        foreach ($value as $model) {

            if ( (!is_object($model))
                || (!$model->getMapper() instanceof $this->_relationMapper) ) {

                    Throw New Zend_Exception('El valor ('.get_class($model).') no tiene una estructura válida para mapper multiselect ('.$this->_relationMapper.')');
            }

            $fkName = false;

            $parents = $model->getParentList();
            foreach ($parents as $_fk => $parentData) {

                if (strtolower($parentData['table_name']) == strtolower($tableRelatedName)) {

                    if ($this->_relationProperty == $parentData['property']) {

                        $fkName = $_fk;
                        break;
                    }
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
                'relatedId'=>$model->{'get' . ucfirst($relationAttributte)}()
            );

            $relationIndex[$retStruct['relatedId']] = $retStruct['pk'];
            $ret[$retStruct['pk']] = $retStruct;
        }

        $value = array(
            "relStruct" => $ret,
            "relIndex" => $relationIndex
        );

        return $value;
    }

    /* $value => Array con los ID de los objetos related
     * $original => array de modelos de relation.
     *
     *
     * @see KlearMatrix_Model_Field_Multiselect_Abstract::filterValue()
     */
    public function filterValue($value, $original)
    {
        // Devolveremos un array de modelos de relaciones
        $retRelations = array();

        // Recupero el nombre de la tabla, para poder llegar a la FK del modelo de relation
        $dataMapperName = $this->_relatedMapper;
        $dataMapper = new $dataMapperName;
        $tableRelatedName = $dataMapper->getDbTable()->getTableName();

        $fkColumn = false;

        if (is_array($original) && is_array($value)) {

            foreach ($original as $model) {

                if ( (!is_object($model))
                    || (!$model->getMapper() instanceof $this->_relationMapper) ) {

                        Throw New Zend_Exception('El valor ('.get_class($model).') no tiene una estructura válida para mapper multiselect ('.$this->_relationMapper.')');
                }

                if (false === $fkColumn) {

                    $fkColumn = $model->getColumnForParentTable($tableRelatedName, $this->_relationProperty);
                }

                $getter = 'get' . ucfirst($fkColumn);
                foreach ($value as $idx=>$idRelatedItem) {

                    if ($idRelatedItem ==  $model->{$getter}()) {

                        $retRelations[] = $model;
                        unset($value[$idx]);
                    }
                }
            }
        }

        $relationMapperName = $this->_relationMapper;
        if (is_array($value)) {

            foreach ($value as $idRelated) {

                $relationMapper = new $relationMapperName;
                $relationModel = $relationMapper->loadModel(null);

                if (false === $fkColumn) {

                    $fkColumn = $relationModel->getColumnForParentTable($tableRelatedName, $this->_relationProperty);
                }

                $setter = 'set' . ucfirst($fkColumn);

                $relationModel->{$setter}($idRelated);
                $retRelations[] = $relationModel;
            }
        }

        return $retRelations;
    }

    public function getEditableFieldsConfig()
    {
        if (!isset($this->_editableFields)) {
            $this->_editableFields = $this->_getEditableFields();
        }
        return $this->_editableFields;
    }

    protected function _getEditableFields()
    {
        $editableFieldList = $this->_parsedValues->getProperty("editableFields");

        $parsedEditableFields = array();

        if ($editableFieldList) {

            foreach ($editableFieldList as $name => $editableField) {

                $parsedEditableFields[] = $this->_parseEditableField($name, $editableField);
            }
        }

        return $parsedEditableFields;
    }

    protected function _parseEditableField($name, Zend_Config $editableField)
    {
        $_editFieldConfig = new Klear_Model_ConfigParser;
        $_editFieldConfig->setConfig($editableField);

        $_field = array(
            'name' => $name,
            'type' => $_editFieldConfig->getProperty("type"),
            'label' => $_editFieldConfig->getProperty("label")
        );

        return $_field;
    }
}
