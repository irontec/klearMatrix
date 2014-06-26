<?php
/**
* Clase respuesta MatrixResponse para peticiones desde klear.request.js
* @author jabi
*/

class KlearMatrix_Model_MatrixResponse
{

    protected $_columns;
    protected $_results;
    protected $_fieldOptions = false;
    protected $_generalOptions = false;

    protected $_actionMessages;
    protected $_fixedPositions;

    protected $_paginator = false;
    protected $_csv = false;

    protected $_parentIden = false;
    protected $_parentId = false;
    protected $_parentPk = false;
    protected $_parentScreen = false;
    protected $_parentItem = false;
    protected $_parentData = false;

    protected $_disableSave = false;
    protected $_disableAddAnother = false;
    protected $_autoClose = false;
    protected $_fullWidth = false;

    /**
     * These fields will be returned in the "toArray" method
     * when !== false
     * @var $_simpleFields
     */
    protected $_simpleFields = array(
        'pk',
        'title',
        'description',
        'total',
        'parentIden',
        'parentId',
        'parentScreen',
        'parentItem',
        'parentPk',
        'disableSave',
        'disableAddAnother',
        'autoClose',
        'fullWidth'
    );

    protected $_arrayFields = array(
        'columns',
        'actionMessages',
        'preconfiguredFilters',
        'generalOptions',
        'fieldOptions',
        'info',
        'fixedPositions'
    );

    protected $_title;
    protected $_description;

    protected $_total = false;
    protected $_searchFields = array();
    protected $_searchPresetted = false;

    protected $_searchAddModifier = false;
    protected $_applySearchFilters = true;

    protected $_preconfiguredFilters = array();

    protected $_info = false;

    //@var KlearMatrix_Model_ResponseItem;
    protected $_item;

    protected $_pk;

    public function __construct()
    {
        // Buscaremos éstas propiedades en klear.yaml > main > defaultCustomConfiguration
        // de cara a establecer su valor por defecto.
        $defaultValues = array('disableSave','disableAddAnother','autoClose');

        $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        $siteConfig = $bootstrap->getResource('modules')->offsetGet('klear')->getOption('siteConfig');
        foreach ($defaultValues as $key) {
            $value = $siteConfig->getDefaultCustomConfiguration($key);
            if (is_null($value)) {
                continue;
            }
            $property = '_' . $key;
            $this->{$property} = $value;

        }
    }

    public function setColumnCollection(KlearMatrix_Model_ColumnCollection $columnCollection)
    {
        $this->_columns = $columnCollection;
        return $this;
    }

    public function setResults($results)
    {
        $this->_results = $results;
        return $this;
    }

    public function setTotal($total)
    {
        $this->_total = $total;
        return $this;
    }


    public function setPK($pk)
    {
        $this->_pk = $pk;
        return $this;
    }

    public function setTitle($title)
    {
        $this->_title = $title;
        return $this;
    }

    public function setDescription($description)
    {
        $this->_description= $description;
        return $this;
    }

    /**
     * Opciones "generales" de pantalla
     * @param KlearMatrix_Model_Option_Collection $screenOptsWrapper
     */
    public function setGeneralOptions(KlearMatrix_Model_Option_Collection $generalOptsWrapper)
    {
        $this->_generalOptions = $generalOptsWrapper;
        return $this;
    }


    /**
     * Opciones por fila
     * @param KlearMatrix_Model_Option_Collection $fieldOptsWrapper
     */
    public function setFieldOptions(KlearMatrix_Model_Option_Collection $fieldOptsWrapper)
    {
        $this->_fieldOptions = $fieldOptsWrapper;
        return $this;
    }
    public function setPreconfiguredFilters($preConfFilters)
    {
        $this->_preconfiguredFilters = $preConfFilters;
        return $this;
    }


    public function setResponseItem(KlearMatrix_Model_ResponseItem $item)
    {
        $this->_item = $item;
        return $this;
    }

    public function setCsv($value)
    {

        $this->_csv = (bool)$value;
    }

    public function setPaginator(Zend_Paginator $paginator)
    {
        $this->_paginator = $paginator;
    }

    public function calculateParentData(KlearMatrix_Model_RouteDispatcher $router, $parentScreenName, $curScreenPK)
    {
        $item = $router->getCurrentItem();
        $this->_parentScreen = $parentScreenName;

        if (false === $this->_parentScreen) {
            return;
        }

        // Informamos a la respuesta de que campo es el "padre"
        $this->_parentItem = $item->getFilterField();
        if (is_null($curScreenPK)) { // List|New
            $this->_parentPk = $router->getParam('pk', false);
            if (false != $this->_parentPk) {
                $this->_parentId = $this->_parentPk;
            } else {
                $this->_parentId = $router->getParam('parentId', false);
            }

            if (false === $this->_parentId) {
                throw new Exception("No Parent id / pk found");
            }


        } else {
            // Pantallas de elemento único instancia por $curScreenPK
            $this->_parentPk = $curScreenPK;
            $this->_parentId = $router->getParam('parentId', false);

        }

        // Instanciamos pantalla
        $parentScreen = new KlearMatrix_Model_Screen;
        $parentScreen->setRouteDispatcher($router);
        $parentScreen->setConfig($router->getConfig()->getScreenConfig($parentScreenName));
        $parentMapperName = $parentScreen->getMapperName();

        $parentColumns = $parentScreen->getVisibleColumns();
        $defaultParentCol = $parentColumns->getDefaultCol();

        // Recuperamos mapper, para recuperar datos principales (default value)
        $parentMapper = \KlearMatrix_Model_Mapper_Factory::create($parentMapperName);

        $pk = $this->_parentId;
        if (false == $this->_parentId) {
            $pk = $this->_parentPk;
        }
        $this->_parentData = $parentMapper->find($pk);

        if ($this->_parentData) {

            if (in_array($defaultParentCol->getDbFieldName(), $this->_parentData->getFileObjects())) {
                $specsGetter = 'get' . ucfirst($defaultParentCol->getDbFieldName()) . 'Specs';
                $specs = $this->_parentData->$specsGetter();
                $getter = 'get' . $this->_parentData->columnNameToVar($specs['baseNameName']);
            } else {
                try {
                    $getter = 'get' . $this->_parentData->columnNameToVar($defaultParentCol->getDbFieldName());
                } catch (\Exception $e) {
                    throw new \Exception($defaultParentCol->getDbFieldName() . " is not a valid default column. Chech your modelName.yaml");
                }
            }

            $this->_parentIden = $this->_parentData->$getter();
        }


    } // FIN!

    public function getParentData()
    {
        return $this->_parentData;
    }


    public function setDisableSave($disableSave)
    {
        if ($disableSave === true) {

            foreach ($this->_columns->getIterator() as $column) {

                $column->setReadOnly(true);
            }
        }

        $this->_disableSave = $disableSave;
        return $this;
    }

    public function setDisableAddAnother($disableAddAnother)
    {
        $this->_disableAddAnother = $disableAddAnother;
        return $this;
    }

    /**
     * Ayuda contextual seteada
     * @param boolean|array $info
     */
    public function setInfo($info)
    {
        if (false !== $info) {
            $this->_info = $info;
        }
        return $this;
    }

    public function addSearchField($field, $values, $ops)
    {
        $this->_searchFields[$field] = $values;
        $this->_searchOps[$field] = $ops;
    }

    public function setAsSearchPresetted()
    {
        $this->_searchPresetted = true;
    }

    public function addSearchAddModifier($toggle)
    {
        $this->_searchAddModifier = $toggle;
    }

    public function toggleApplySearchFilters($toggle)
    {
        $this->_applySearchFilters = $toggle;
    }

    /**
     * Setea para su procesamiento el array de mensajes de confirmación y error predefinidos
     * @param KlearMatrix_Model_ActionMessageCollection $msgs
     */
    public function setActionMessages(KlearMatrix_Model_ActionMessageCollection $msgs)
    {
        $this->_actionMessages = $msgs;
        return $this;
    }


    public function setFixedPositions($fixedPositions)
    {
        $this->_fixedPositions = $fixedPositions;
        return $this;
    }

    public function setFullWidth($fullWidth)
    {
        $this->_fullWidth = (bool)$fullWidth;
        return $this;
    }

    protected function _getValueFromColumn($column, $result)
    {
        if ($column->isMultilang()) {
            $rValue = array();
            foreach ($this->_columns->getLangs() as $_lang) {
                $rValue[$_lang] = $result->{$column->getGetterName()}($_lang);
            }

        } else {
            $rValue = $result->{$column->getGetterName()}();
        }
        return $rValue;
    }

    protected function _getCustomOptionsForResult($result)
    {
        $customOptions = array();

        if (!empty($this->_fieldOptions)) {

            foreach ($this->_fieldOptions as $option) {
                if ($option->mustCustomize() === true) {

                    $customization = $option->customizeParentOption($result);
                    if (! is_null($customization)
                        && !isset($customOptions[key($customization)])
                    ) {

                        $customOptions += $customization;
                    }
                }
            }
        }

        return $customOptions;
    }


    /**
     * Si los resultados (de data) son objetos, los pasa a array (para JSON)
     * Se eliminan los campos no presentes en el column-wrapper
     * Se pasa el controlador llamante(edit|new|delete|list), por si implicara cambio en funcionalidad por columna
     * Gestionamos el multi-idioma de los campos multiidioma (getters con $lang seleccionado)
     */
    public function fixResults(KlearMatrix_Model_ResponseItem $screen)
    {
        $primaryKeyName = $screen->getPkName();

        if (!is_array($this->_results)) {
            $this->_results = array($this->_results);
        }

        $_newResults = array();

        foreach ($this->_results as $result) {

            $_newResult = array();
            if ((is_object($result)) && (get_class($result) == $screen->getModelName())) {

                foreach ($this->_columns as $column) {
                    $column->setModel($result);
                    if (!$column->getGetterName()) {
                        continue;
                    }

                    $rValue = $this->_getValueFromColumn($column, $result);
                    $_newResult[$column->getDbFieldName()] = $column->prepareValue($rValue);
                }

                // Recuperamos también la clave primaria
                $_newResult[$primaryKeyName] = $result->getPrimaryKey();

                $_customOptions = $this->_getCustomOptionsForResult($result);
                if (sizeof($_customOptions) > 0) {
                    $_newResult['_optionCustomization'] = $_customOptions;
                }

                $_newResults[] = $_newResult;
            }
        }

        $this->_results = $_newResults;
    }

    public function parseItemAttrs(KlearMatrix_Model_ResponseItem $item)
    {
        $this
            ->setTitle($item->getTitle())
            ->setDescription($item->getDescription())
            ->setInfo($item->getInfo())
            ->setGeneralOptions($item->getScreenOptions())
            ->setActionMessages($item->getActionMessages())
            ->setDisableAddAnother($item->getDisableAddAnother())
            ->setDisableSave($item->getDisableSave())
            ->setPreconfiguredFilters($item->getPreconfiguredFilters())
            ->setFixedPositions($item->getFixedPositions())
            ->setFullWidth($item->getFullWidth());

    }

    protected function _countArrayItemsForProperty($_fld)
    {
        $total = 0;

        if (empty($this->{'_' . $_fld})) {
            return 0;
        }

        // Si la propiedad instancia una clase que implementa IteratorAggregate
        // (no funciona count / sizeof en las implementaciones de IteratorAggregate)
        // debe tener count implementado

        if (method_exists($this->{'_' . $_fld}, 'count')) {
            $total = $this->{'_' . $_fld}->count();
        } else if (is_array($this->{'_' . $_fld})) {
            $total = count($this->{'_' . $_fld});
        }
        return $total;
    }

    protected function _getSearchDataToArray()
    {
        $ret = array();
        $ret['searchFields'] = $this->_searchFields;
        $ret['searchOps'] = $this->_searchOps;
        $ret['searchAddModifier'] = $this->_searchAddModifier;
        $ret['applySearchFilters'] = $this->_applySearchFilters;
        $ret['searchPresetted'] = $this->_searchPresetted;
        return $ret;
    }

    protected function _getLanguageDataToArray()
    {
        $ret = array();
        if (isset($this->_columns)){
	        $ret['langs'] = $this->_columns->getLangs();
	        $ret['defaultLang'] = $this->_columns->getDefaultLang();
	        $ret['langDefinitions'] = $this->_columns->getLangDefinitions();
        }
        return $ret;
    }

    protected function _loadSimpleFields()
    {
        $ret = array();
        foreach ($this->_simpleFields as $_fld) {
            if (false !== $this->{'_' . $_fld}) {
                $ret[$_fld] = $this->{'_'. $_fld};
            }
        }
        return $ret;
    }

    protected function _loadArrayFields()
    {
        $ret = array();
        foreach ($this->_arrayFields as $_fld) {
            if ($this->_countArrayItemsForProperty($_fld) == 0) {
                continue;
            }
            if (is_array($this->{'_' . $_fld})) {
                $ret[$_fld] = $this->{'_' . $_fld};
            } elseif (
                $this->{'_' . $_fld} instanceof Zend_Config
                || method_exists($this->{'_' . $_fld}, 'toArray')
            ) {
                $ret[$_fld] = $this->{'_' . $_fld}->toArray();
            }
        }
        return $ret;
    }

    protected function _loadOptionsPlacementValue()
    {
        $ret = array();
        $ret['optionsPlacement'] = 'bottom';

        if ($this->_generalOptions->count() > 0) {
            $currentModule = $this->_item->getRouteDispatcher()->getControllerName();
            $placement = $this->_generalOptions->getPlacement($currentModule);
            $ret['optionsPlacement'] = $placement;
        } else {
            $ret['generalOptions'] = array();
        }

        return $ret;
    }

    public function toArray()
    {

        $ret = array();
        $ret += $this->_getLanguageDataToArray();

        $ret['values'] = $this->_results;

        $ret += $this->_loadOptionsPlacementValue();

        if ($this->_csv !== false) {
            $ret['csv'] = true;
        }

        if (false !== $this->_paginator && count($this->_paginator) > 1) {
            $ret['paginator'] = (array)$this->_paginator->getPages();
        }

        if (sizeof($this->_searchFields)>0) {
            $ret += $this->_getSearchDataToArray();
        }

        $ret += $this->_loadSimpleFields();
        $ret += $this->_loadArrayFields();

        $ret[$this->_item->getType()] = $this->_item->getItemName();

        return $ret;
    }
}
