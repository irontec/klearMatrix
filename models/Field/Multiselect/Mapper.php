<?php

class KlearMatrix_Model_Field_Multiselect_Mapper extends KlearMatrix_Model_Field_Multiselect_Abstract
{
    protected $_parsedValues;

    protected $_relation;
    protected $_relationProperty;
    protected $_relatedProperty;
    protected $_related;

    protected $_editableFields;

    protected $_js = array();

    protected $_extraDataAttributes = array();
    protected $_extraDataAttributesValues = array();

    public function init()
    {
        $this->_parsedValues = new Klear_Model_ConfigParser;
        $this->_parsedValues->setConfig($this->_config->config);

        // Mapper de las relaciones. Aquí se guardarán las coincidencias.
        $this->_relation = $this->_parsedValues->getProperty("relation");
        $this->_relationProperty = $this->_parsedValues->getProperty("relationProperty");
        $this->_relatedProperty =  $this->_parsedValues->getProperty("relatedProperty");
        $this->_related = $this->_parsedValues->getProperty("related");

        if ($this->_dynamicDataLoading() === true) {

            //Nothing to do
            return;
        }

        if ($this->_config->config->get("extraDataAttributes", false)) {

            $extraAttrs = $this->_config->config->get("extraDataAttributes");
            $this->_extraDataAttributes = $this->_parseExtraAttrs($extraAttrs);
        }

        $where = $this->_getFilterWhere();
        $order = $this->_getRelatedOrder();

        $dataGateway = \Zend_Registry::get('data_gateway');
        $results = $dataGateway->findBy($this->_related, $where, $order);

        if ($results) {

            $relatedFields = $this->_getRelatedFields();
            $relatedFieldsTemplate = $this->_getRelatedFieldsTemplate();

            foreach ($results as $dto) {

                $replace = array();
                foreach ($relatedFields as $fieldName) {
                    $getter = 'get' . ucfirst($fieldName);
                    $replace['%' . $fieldName . '%'] = $dto->$getter();
                }

                $keyGetter = 'getId';
                if ($keyProperty = $this->_parsedValues->getProperty("relatedKeyProperty")) {
                    $keyGetter = 'get' . ucfirst($keyProperty);
                }

                $this->_keys[] = $dto->{$keyGetter}();
                $this->_items[] = str_replace(array_keys($replace), $replace, $relatedFieldsTemplate);
            }

            $this->_setOptions($results);
        }
    }

    protected function _setOptions($results)
    {
        if ($results) {

            $keyGetter = 'getId';
            if ($keyProperty = $this->_config->config->get("keyProperty")) {
                $keyGetter = 'get' . ucfirst($keyProperty);
            }

            foreach ($results as $dataModel) {
                $this->_setValuesForExtraAttributes($dataModel, $dataModel->{$keyGetter}());
            }
        }
    }

    protected function _parseExtraAttrs(Zend_Config $extraConfig)
    {
        $retAttrs = array();
        foreach ($extraConfig as $label => $field) {
            $retAttrs[$label] = 'get' . ucfirst($field);
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


    /**
     * return bool
     */
    protected function _dynamicDataLoading()
    {
        if (isset($this->_column->getKlearConfig()->getRaw()->decorators)) {
            $selfClassName = get_class($this);
            $classBasePath = substr($selfClassName, 0, strrpos($selfClassName, '_') + 1);
            $decoratorClassBaseName = $classBasePath . 'Decorator_';

            $decorators = $this->_column->getKlearConfig()->getRaw()->decorators;

            foreach ($decorators as $decoratorName => $decorator) {

                $decorator; //Avoid PMD UnusedLocalVariable warning
                $decoratorClassName = $decoratorClassBaseName . ucfirst($decoratorName);

                if (class_exists($decoratorClassName)
                    && defined($decoratorClassName . '::DYNAMIC_DATA_LOADING')
                    && $decoratorClassName::DYNAMIC_DATA_LOADING
                ) {

                    $this->_loadJsDependencies($decoratorName);
                    return true;
                }
            }
        }

        return false;
    }


    protected function _loadJsDependencies($decoratorName)
    {
        $jsDependencies = array();
        switch ($decoratorName) {
            case 'autocomplete':
                $jsDependencies[] = '/js/plugins/jquery.klearmatrix.multiselectautocomplete.js';
                break;
        }

        $this->_js += $jsDependencies;
    }

    protected function _getFilterWhere()
    {
        $filterClassName = $this->_parsedValues->getProperty("filterClass");
        if ($filterClassName) {
            $filter = new $filterClassName;
            // Se "aligera" la comprobación ya que pueden reusarse filtros que implementen Select
            // Mientras que MultiSelect ya implementa Select
            if ( !$filter instanceof KlearMatrix_Model_Field_Select_Filter_Interface ) {
                throw new Exception('Filters must implement KlearMatrix_Model_Field_Multiselect_Filter_Interface.');
            }

            /**
             * @todo
             */
            throw new \Exception('TODO');
            return $this->_getFilterCondition($filter);
        }
        return null;
    }

    protected function _getFilterCondition(KlearMatrix_Model_Field_Select_Filter_Interface $filter)
    {
        $filter->setRouteDispatcher($this->_column->getRouteDispatcher());
        return $filter->getCondition();
    }

    protected function _getRelatedOrder()
    {
        $order = $this->_parsedValues->getProperty("relatedOrder");
        if ($order instanceof Zend_Config) {
            return $order->toArray();
        }
        return $order;
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

        $fieldConfig = new Klear_Model_ConfigParser();
        $fieldConfig->setConfig($fieldName);
        return $fieldConfig->getProperty("template");
    }

    public function prepareValue($value)
    {
        if (sizeof($value) == 0) {
            return array();
        }

        $relationIndex = array();

        foreach ($value as $model) {

            $relationAttributte = $this->_relatedProperty . 'Id';

            $retStruct = array(
                'pk'=> $model->getId(),
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
    /**
     * @param $value
     * @param $original
     * @return array
     * @throws Exception
     * @throws Zend_Exception
     */
    public function filterValue($value, $original)
    {
        // Devolveremos un array de modelos de relaciones
        $retRelations =[];

        //En EditController se comprueba si llega un campo para guardarlo.
        //Cuando se deja un multiselect vacío, no se enviaba el campo, por lo que no actualizaba
        //En el template del multiselect siempre va un input[hidden] con el nombre del campo y value=""
        //Ese valor siempre se desecha
        $value = array_filter($value, function ($value) {
            return !empty($value);
        });

        if (is_array($original) && is_array($value)) {

            $getter = 'get' . ucfirst($this->_relatedProperty) . 'Id';
            foreach ($original as $model) {

                foreach ($value as $idx => $idRelatedItem) {

                    if ($idRelatedItem == $model->{$getter}()) {

                        $retRelations[] = $model;
                        unset($value[$idx]);
                    }
                }
            }
        }

        if (is_array($value)) {

            $relationEntityName = $this->_relation;
            $relationModelReflectionClass = new \ReflectionClass($relationEntityName);
            $relatedPropertySetter = 'set' . ucfirst($this->_relatedProperty) . 'Id';

            foreach ($value as $idRelated) {

                $relationModel = $relationModelReflectionClass
                    ->newInstanceWithoutConstructor()
                    ->createDTO();

                $relationModel->{$relatedPropertySetter}($idRelated);
                $retRelations[] = $relationModel;
            }
        }

        return $retRelations;
    }

    protected function _getEditableFieldsConfig()
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

    /**
     * Método para que multiselect funcione con filtrado nativamente
     * @param unknown_type $values
     * @param unknown_type $searchOps
     */
    public function getCustomSearchCondition($values, $searchOps)
    {
        /**
         * @todo
         */
        throw new \Exception('TODO');

        $dataIds = array();
        // Comprobamos que los Ids que nos llegan desde el buscador, estén en los Ids disponibles
        // Cuando el campo va acompañado de un decorator autocomplete no disponemos de las ids, damos fe
        foreach ($values as $value) {
            if (is_null($this->_keys) || in_array($value, $this->_keys)) {
                $dataIds[] = $value;
            }
        }

        if (sizeof($dataIds) == 0) {
            return '';
        }

        $relationMapperName = $this->_relation;
        $relationMapper = new $relationMapperName;
        $relationModel = $relationMapper->loadModel(null);

        $dataMapperName = $this->_related;
        $dataMapper = new $dataMapperName;
        $tableRelatedName = $dataMapper->getDbTable()->getTableName();

        // Campo relacionado con la tabla de data, el que tengo que filtrar por los valores que llegan en values
        $dataColumnName = $relationModel->getColumnForParentTable($tableRelatedName, $this->_relationProperty);

        $originalModel = $this->_column->getModel();
        $originalMapper = $originalModel->getMapper();

        // Si el mapper tiene el método getTableName, se consulta (EKT)
        // Si no, se tira directamente de DbTable? - quizás es mejor que tenga ese método siempre ó excepción?
        if (method_exists($originalMapper, 'getTableName')) {
            $originalTableName = $originalMapper->getTableName();
        } else {
            $originalTableName = $originalMapper->getDbTable()->getTableName();
        }

        $originalColumnName = null;

        // Necesitamos el nombre del modelo (relación con la tbala principal) en la tabla de relación
        $parents = $relationModel->getParentList();
        foreach ($parents as $parentData) {
            // El campo no tiene que ser el "otro" (para n-m de una misma tabla...
            if (
                (strtolower($parentData['table_name']) == strtolower($originalTableName))
                && ($parentData['property'] != $dataColumnName)
            ) {
                $originalColumnName = $parentData['property'];
                break;
            }
        }

        if (is_null($originalColumnName)) {
            return '';
        }

        // Campo relacionado con la tabla principal en la tabla de relación
        $idColumnName = $relationModel->getColumnForParentTable($originalTableName, $originalColumnName);

        // Instanciamos mapper de relacion, para conseguir todos los IDs de
        $mapper = new $relationMapperName;
        $relationModels = $mapper->fetchList($dataColumnName . ' in ('.implode(',', $dataIds).')');

        $returnIds = array();
        $getter = 'get' . ucfirst($idColumnName);
        foreach ($relationModels as $relModel) {
            $returnIds[] = $relModel->$getter();
        }

        if (sizeof($returnIds) == 0) {
            return '';
        }

        return $originalModel->getPrimaryKeyName() . ' in (' . implode(',', $returnIds). ')';
    }

    public function _toArray()
    {
        $ret = array();

        foreach ($this as $key => $value) {
            $_val = array('key' => $key, 'item' => $value);
            if (isset($this->_extraDataAttributesValues[$key])) {
                $_val['data'] = array();
                foreach ($this->_extraDataAttributesValues[$key] as $label => $dataVal) {
                    $_val['data'][$label] = $dataVal;
                }
            }
            $ret[] = $_val;
        }
        return $ret;
    }

    /**
     * @deprecated
     */
    public function getRelationMapper()
    {
        return $this->_relation;
    }

    public function getRelationEntity()
    {
        return $this->_relation;
    }

    public function getRelationProperty()
    {
        return $this->_relationProperty;
    }

    public function getRelatedProperty()
    {
        return $this->_relatedProperty;
    }

    /**
     * @deprecated
     */
    public function getRelatedMapper()
    {
        return $this->_related;
    }

    public function getRelatedEntity()
    {
        return $this->_related;
    }
}
