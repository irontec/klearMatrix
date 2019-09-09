<?php
class KlearMatrix_Model_Field_Select_Mapper extends KlearMatrix_Model_Field_Select_Abstract
{
    protected $_showOnSelect = array();
    protected $_hideOnSelect = array();

    /**
     * Estructura inventada para exponer en cada <option> un atributo data con el valor de un campo.
     * Útil para javascripts que extiendan funcionalidades (por ejemplo Timezones por país seleccionado).
     * modo de empleo:
     *
     * config:
     *   dynamicDataAttributes:
     *     etiqueta: campoEnBBDD
     *
     * esto generará en cada <option /> un data-etiqueta="Valor de campoEnBBDD para cada registro"
     * @var Array
     */
    protected $_extraDataAttributes = array();
    protected $_extraDataAttributesValues = array();

    protected $_js = array(
    );

    protected $initialized = false;
    protected $optionsCriteria = [];

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

    public function init()
    {
        if ($this->_dynamicDataLoading() === true) {

            //Nothing to do
            return;
        }

//        $mapperName = $this->_config->getProperty("config")->mapperName;
        $entity = $this->_config->getProperty("config")->entity;

        if (isset($this->_config->getProperty('config')->extraDataAttributes)) {

            $extraAttrs = $this->_config->getProperty('config')->extraDataAttributes;
            $this->_extraDataAttributes = $this->_parseExtraAttrs($extraAttrs);
        }

        $where = $this->_getFilterWhere();

        $order = $this->_config->getProperty('config')->order;
        if ($order) {
            $order = $order->toArray();
        }

        $this->optionsCriteria = [
            $entity,
            $where,
            $order
        ];
    }

    protected function initOptions()
    {
        if ($this->initialized) {
            return;
        }

        if (empty($this->optionsCriteria)) {
            return;
        }

        $dataGateway = \Zend_Registry::get('data_gateway');

        list($entity, $where, $order) = $this->optionsCriteria;

        if (isset($where[0])) {
            $where[0] = $this->_replaceSelfReferences($where[0]);
        }

        $results = $dataGateway->findBy($entity, $where, $order);
        $this->_setOptions($results);

        $this->initialized = true;
    }

    protected function _replaceSelfReferences($where)
    {
        return Klear_Model_QueryHelper::replaceSelfReferences(
            $where,
            $this->_getEntityName()
        );
    }

    protected function _getEntityName()
    {
        $className = $this->_config->getProperty("config")->entity;;
        $entitySegments = explode('\\', $className);

        return end($entitySegments);
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
                $jsDependencies[] = '/js/plugins/jquery.klearmatrix.selectautocomplete.js';
                break;
        }

        $this->_js += $jsDependencies;
    }

    protected function _getFilterWhere()
    {

        $manualCondition = $this->_config->getProperty('config')->rawCondition;
        if (!empty($manualCondition)) {
            return $manualCondition;
        }

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

    protected function _getFilterCondition(KlearMatrix_Model_Field_Select_Filter_Interface $filter)
    {
        $filter->setRouteDispatcher($this->_column->getRouteDispatcher());
        return $filter->getCondition();
    }

    protected function _setOptions($results)
    {
        if ($results) {

            $keyGetter = 'getId';
            if ($keyProperty = $this->_config->getProperty("config")->get("keyProperty")) {
                $keyGetter = 'get' . ucfirst($keyProperty);
            }

            foreach ($results as $dto) {
                $this->_keys[] = $dto->{$keyGetter}();
                $this->_items[] = $this->_getItemValue($dto);

                $this->_setValuesForExtraAttributes($dto, $dto->{$keyGetter}());
                $this->_initVisualFilter($dto);
            }
        }
    }

    protected function _getItemValue($dto)
    {
        $customValueMethod = $this->_config->getProperty('config')->customValueMethod;
        if ($customValueMethod) {
            return $dto->$customValueMethod();
        }

        $fields = $this->_getFields();
        $fieldsTemplate = Klear_Model_Gettext::gettextCheck($this->_getFieldsTemplate());
        $replace = array();
        if (is_array($this->_config->getProperty("config")->fieldName)) {
            $fieldConfig = $this->_config->getProperty("config")->fieldName->toArray();
        } else {
            $fieldConfig = false;
        }

        foreach ($fields as $fieldName) {
            $getter = 'get' . ucfirst($fieldName);
            $fieldValue = $dto->$getter();
            if (isset($fieldConfig["mapValues"]) && isset($fieldConfig["mapValues"][$fieldName])) {
                if (isset($fieldConfig["mapValues"][$fieldName][$fieldValue])) {
                    $fieldValue = Klear_Model_Gettext::gettextCheck($fieldConfig["mapValues"][$fieldName][$fieldValue]);
                }
            }
            $replace['%' . $fieldName . '%'] = $fieldValue;
        }

        return str_replace(array_keys($replace), $replace, $fieldsTemplate);
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

    public function _initVisualFilter($dataModel)
    {
        $visualFilter = $this->_config->getProperty('config')->visualFilter;

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

                $getter = 'get' . ucfirst($key);
                $value = $dataModel->$getter();

                if ($config->$value) {

                    $this->_showOnSelect[$dataModel->getId()] = $config->$value->show;
                    $this->_hideOnSelect[$dataModel->getId()] = $config->$value->hide;

                } else if ($config->__default__) {

                    $this->_showOnSelect[$dataModel->getId()] = $config->__default__->show;
                    $this->_hideOnSelect[$dataModel->getId()] = $config->__default__->hide;
                }
            }
        }
    }


    /* (non-PHPdoc)
     * Sobreescrito para "llevar" extraDataAttributtes (si los hubiere)
     * @see KlearMatrix_Model_Field_Select_Abstract::_toArray()
     */
    protected function _toArray()
    {
        $this->initOptions();
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

    public function getCustomOrderField()
    {
        $values = array();

        $config = $this->_config->getProperty('config');
        $fieldName = [];

        if ($config->order) {
            $order = $config->order->toArray();

            foreach ($order as $key => $value) {
                $fieldName[] = substr($key, strpos($key, '.') + 1);
            }

        } else if (is_object($config->fieldName)) {

            $fieldConfig = new Klear_Model_ConfigParser();
            $fieldConfig->setConfig($config->fieldName);
            $template = $fieldConfig->getProperty("template");
            preg_match_all('/%(.*)%/U', $template, $matches);

            if (!count($matches[1])) {
                return $this->_quoteIdentifier($this->_column->getDbFieldName());
            }

            $fieldName = $matches[1];
        }

        $dataGateway = \Zend_Registry::get('data_gateway');
        $entityClass = $this->_config->getProperty("config")->entity;
        $entityClassSegments = explode('\\', $entityClass);
        $entity = end($entityClassSegments);
        $order = [];
        foreach ($fieldName as $field) {
            $key = $entity . '.' . $field;
            $orderType = $this->_column->getRouteDispatcher()->getParam('orderType') ?? 'ASC';
            $order[$key] = strtoupper($orderType);
        }

        $candidateNum = intval(
            $dataGateway->countBy($entityClass)
        );

        if ($candidateNum > 100) {
            return function (\Doctrine\ORM\QueryBuilder $qb) use ($entity, $order) {

                $self = $this->_column->getRouteDispatcher()->getCurrentScreen()->getEntityName();

                $qb->leftJoin(
                    $self . '.' . $this->_column->getDbFieldName(),
                    $entity
                );

                $qb->orderBy(key($order), current($order));
            };
        }

        /**
         * @todo optimize this query, we just need the ids here
         */
        $results = $dataGateway->findBy($entityClass, null, $order);

        foreach ($results as $result) {
            $values[] = $result->getId();
        }

        if (! count($values)) {
            return $this->_column->getModelAttributeName();
        }

        $priority = 1;
        $identifier =  $this->_quoteIdentifier($this->_column->getModelAttributeName());

        $response =  'CASE ';
        foreach ($values as $possibleResult) {
            $response .= " WHEN $identifier = '" . $possibleResult . "' THEN " . $priority++;
        }
        $response .= ' ELSE '. $priority .' END AS HIDDEN ORD';

        return $response;
    }
}

//EOF
