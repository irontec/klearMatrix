<?php
class KlearMatrix_Model_Column
{
    protected $_dbFieldName;
    protected $_publicName;
    protected $_isDefault = false;
    protected $_isReadonly = false;

    protected $_hasInfo = false;
    protected $_fieldInfo = false;

    protected $_hasLink = false;
    protected $_link = false;

    protected $_ordered = false;
    protected $_orderedType = 'asc';

    protected $_fieldConfig;

    protected $_config;

    protected $_isOption;
    protected $_defaultOption;
    protected $_type = 'text';
    protected $_dirty = false;

    protected $_hasFieldOptions = false;
    protected $_options = false;

    protected $_isMultilang = false;

    protected $_isDependant = false;

    protected $_isFile = false;

    protected $_searchSpecs = false;

    protected $_model;

    /**
     * Opciones para campos deshabilitados (condicionantes, etc)
     * @var mixed
     */
    protected $_disabledOptions = false;

    protected $_routeDispatcher;

    public function setDbFieldName($name)
    {
        $this->_dbFieldName = $name;
    }

    public function setPublicName($name)
    {
        $this->_publicName = $name;
    }

    public function setRouteDispatcher(KlearMatrix_Model_RouteDispatcher $routeDispatcher)
    {
        $this->_routeDispatcher = $routeDispatcher;
        return $this;
    }

    public function setModel($model)
    {
        $this->_model = $model;
        return $this;
    }

    public function getModel()
    {
        return $this->_model;
    }

    public function markAsOption()
    {
        $this->_isOption = true;
    }

    public function markAsDependant()
    {
        $this->_isDependant = true;
    }

    public function markAsMultilang()
    {
        $this->_isMultilang = true;
    }

    public function markAsFile()
    {
        $this->_isFile = true;
    }

    public function markAsDirty()
    {
        $this->_dirty = true;
    }

    public function setReadOnly($readOnly = true)
    {
        $this->_isReadonly = (bool)$readOnly;
    }

    public function isOption()
    {
        return $this->_isOption;
    }

    public function isDependant()
    {
        return $this->_isDependant;
    }

    public function isMultilang()
    {
        return $this->_isMultilang;
    }

    public function isFile()
    {
        return $this->_isFile;
    }

    public function isDirty()
    {
        return $this->_dirty;
    }

    public function setConfig(Zend_Config $config)
    {

        $this->_config = new Klear_Model_ConfigParser;
        $this->_config->setConfig($config);

        $this->_publicName = $this->_config->getProperty("title");

        if ($this->_dbFieldName == '_fieldOptions') {
            $this->markAsOption();
            $this->_parseOption();
        } else {
            $this->_parseField();
        }

        // Es importante que el loadconficlass esté aquí para que el readonly funcione en los campos Ghost.
        // TODO: Habría que mejorar la comprobación si queremos mantener el lazyload
        $this->_loadConfigClass();
    }

    protected function _parseOption()
    {

        $this->_type = '_option';

        if ($default = $this->_config->getProperty("default")) {
            $this->_defaultOption = $default;
        }
    }

    protected function _parseField()
    {
        $this->_isDefault = (bool)$this->_config->getProperty("default");
        $this->_isReadonly = (bool)$this->_config->getProperty("readonly");

        $this->_hasInfo = (bool)$this->_config->getProperty("info");
        if ($this->_hasInfo) {
            $this->_fieldInfo = new KlearMatrix_Model_Info;
            $this->_fieldInfo->setConfig($this->_config->getProperty("info"));
        }

        $this->_hasLink = (bool)$this->_config->getProperty("link");
        if ($this->_hasLink) {
            $this->_link = new KlearMatrix_Model_Link;
            $this->_link->setConfig($this->_config->getProperty("link"))
            ->setSetField($this->getFieldConfig());
        }

        $this->_disabledOptions = $this->_config->getProperty("disabled");

        if ($this->_disabledOptions) {

            $this->_parseDisabledOptions();
        }


        if ($this->_config->getProperty("search")) {
            $this->_parseSearchSpecsOptions();
        }


        $this->_type = $this->_config->getProperty("type");
        if (empty($this->_type)) {
            $this->_type = 'text';
        }

        $this->_dirty = $this->_config->getProperty("dirty");

        if ($this->_config->getProperty("options")) {
            $this->_hasFieldOptions = true;
            $this->_parseColumnOptions();
        }
    }

    /**
     * Lazy loader for getFieldConfig
     * @return KlearMatrix_Model_Field_Abstract
     */
    public function getFieldConfig()
    {
        if (!is_object($this->_fieldConfig)) {
            $this->_loadConfigClass();
        }

        return $this->_fieldConfig;
    }

    protected function _loadConfigClass()
    {
        if ($this->isOption()) {
            $this->_fieldConfig = null;
        } else {
            $this->_fieldConfig = KlearMatrix_Model_Field_Abstract::create($this->_type, $this);
            $this->_fieldConfig;
        }
    }

    /**
     * @return KlearMatrix_Model_RouteDispatcher
     */
    public function getRouteDispatcher()
    {
        return $this->_routeDispatcher;
    }

    public function getJsPaths()
    {
        if ($this->isOption()) {
            return array();
        }

        return $this->getFieldConfig()->getExtraJavascript();
    }

    public function getCssPaths()
    {
        if ($this->isOption()) {
            return array();
        }

        return $this->getFieldConfig()->getExtraCss();
    }

    public function isDefault()
    {
        return $this->_isDefault;
    }

    public function isReadonly()
    {
        if ($this->isOption()) {
            return false;
        }

        return $this->_isReadonly;
    }

    public function hasInfo()
    {

        if ($this->isOption()) return false;

        return $this->_hasInfo;
    }

    public function hasLink()
    {

        if ($this->isOption()) return false;

        return $this->_hasLink;
    }

    public function setAsOrdered()
    {
        $this->_ordered = true;
    }

    public function setOrderedType($_orderType)
    {
        $this->_orderedType = $_orderType;
    }

    public function getOrderField($langs = null)
    {
        $orderField = $this->getFieldConfig()->getCustomOrderField();
        if ($orderField) {
            return $orderField;
        }

        if ($this->_isMultilang) {
            $klearBootstrap = Zend_Controller_Front::getInstance()
            ->getParam("bootstrap")->getResource('modules')->offsetGet('klear');
            $siteLanguage = $klearBootstrap->getOption('siteConfig')->getLang();
            $currentLanguage = $siteLanguage->getLanguage();

            if (in_array($currentLanguage, $langs)) {
                return $this->_dbFieldName."_".$currentLanguage;
            }

            $orderFields = array();
            foreach ($langs as $lang) {
                $orderFields[] = $this->_dbFieldName."_".$lang;
            }
            return $orderFields;
        }

        return $this->_dbFieldName;
    }

    /**
     * @return Klear_Model_ConfigParser
     */
    public function getKlearConfig()
    {
        return $this->_config;

    }

    public function getPublicName()
    {
        if (null !== $this->_publicName) {
            return Klear_Model_Gettext::gettextCheck($this->_publicName);
        }

        return $this->_dbFieldName;
    }

    public function getDbFieldName()
    {
        return $this->_dbFieldName;
    }

    public function getType()
    {
        return $this->_type;
    }

    public function getDefaultOption()
    {
        if (!$this->isOption()) return false;
        return $this->_defaultOption;
    }

    /**
     * gateway hacia la clase de cada campo
     * Preparar cada campo en base a su tipo, antes de devolverlo.
     * @param mixed $value
     * @return mixed
     */
    public function prepareValue($value)
    {
        return $this->getFieldConfig()->prepareValue($value);
    }

    public function filterValue($value)
    {
        return $this->getFieldConfig()->filterValue($value);
    }


    protected function _parseSearchSpecsOptions()
    {

        $this->_searchSpecs = array(
            'options' => null,
            'plugin' => null,
            'info' => null
        );

        $searchConfig = new Klear_Model_ConfigParser();
        $searchConfig->setConfig($this->_config->getProperty("search"));

        if ($searchConfig->getProperty("options")) {
            $this->setSearchSpec("options", true);
        }

        if ($searchConfig->getProperty("plugin")) {
            $this->setSearchSpec("plugin", $searchConfig->getProperty("plugin"));
        }

        if ($searchConfig->getProperty("info")) {
            $this->setSearchSpec("info", $searchConfig->getProperty("info"));
        }

    }

    public function setSearchSpec($name, $value)
    {
        $this->_searchSpecs[$name] = Klear_Model_Gettext::gettextCheck($value);

    }

    public function _parseColumnOptions()
    {

        if ($this->_config->getProperty("options")) {

            $KlearMatrixOptionLoader = new KlearMatrix_Model_Option_Loader();
            $parent = new Klear_Model_ConfigParser();
            $parent->setConfig($this->_config->getRaw()->options);
            $KlearMatrixOptionLoader->setMainConfig($this->_routeDispatcher->getConfig());
            $KlearMatrixOptionLoader->setParentConfig($parent);
            $KlearMatrixOptionLoader->registerConditionalFunction(
                'screen',
                function ($option)
                {
                    $mustBeAdded = !$this->_routeDispatcher->getCurrentItem()->isFilteredScreen()
                    || !$option->getFilterField()
                    || ($option->getFilterField() == $this->_routeDispatcher->getCurrentItem()->getFilterField());
                    $option->skip(!$mustBeAdded);
                }
            );
            $this->_options = $KlearMatrixOptionLoader->getFieldOptions();

        }
    }


    /**
     * Lo se... re-utilizo la variable self::_disabledOptions, primero para Zend_Config, luego para el array a JSONear
     * Y lo se, el método carece de consistencia...
     * Establece que valor tiene que tener un campo para que éste aparezca con disabled con un label determinado...
     *
     * A ver si aparecen más casos y podemos articular algo mejor todo esto O:)
     */
    public function _parseDisabledOptions()
    {

        $disabledConfig = new Klear_Model_ConfigParser;
        $disabledConfig->setConfig($this->_disabledOptions);

        $disabledOptions = array();
        // Valor del campo para que éste sea disabled
        if ($disabledConfig->getProperty('valueCondition')) {
            $disabledOptions['valuesCondition'] = $disabledConfig->getProperty('valueCondition');
        }

        if ($disabledConfig->getProperty('label')) {
            $disabledOptions['label'] = Klear_Model_Gettext::gettextCheck($disabledConfig->getProperty('label'));

            ;
        }

        $this->_disabledOptions = $disabledOptions;
    }

    public function getSearchCondition(array $values, array $searchOps, $langs)
    {
        if (method_exists($this->getFieldConfig(), 'getCustomSearchCondition')) {
            $searchCondition = $this->getFieldConfig()->getCustomSearchCondition($values, $searchOps);
            if ($searchCondition) {
                return $searchCondition;
            }
            // Se devuelve un WHERE para que no machee nada, ya que si no se devuelve la condición
            // generada desde 'getCustomSearchCondition' es que no hay resultados
            // INFO: Lo que se returnea aquí se mete directamente en "WHERE (?)"
            return "1 <> 1";
        }

        $searchFields = $this->_getSearchFields($langs);

        return $this->_getConditions($searchFields, $values);
    }

    protected function _getSearchFields($langs)
    {
        if (method_exists($this->getFieldConfig(), 'getCustomSearchField')) {
            $searchField = $this->getFieldConfig()->getCustomSearchField();
        } else {
            $searchField = $this->_dbFieldName;
        }

        if ($this->isMultilang()) {

            $searchFields = array();
            foreach ($langs as $_lang) {
                $searchFields[] = $searchField .'_' . $_lang;
            }

        } else {
            $searchFields = array($searchField);
        }

        return $searchFields;
    }

    protected function _getConditions($searchFields, $values)
    {
      $fieldValues = array();
      foreach ($searchFields as $searchField) {
            $cont = 1;
            $quotedSearchField = Zend_Db_Table::getDefaultAdapter()->quoteIdentifier($searchField);
            foreach ($values as $_val) {

                $template = ":" . $searchField . $cont ;

                //Para los select tipo mapper no hacemos like, porque son Ids
                if ($this->_isMapperSelect()) {
                    if ($this->namedParamsAreSupported()) {
                        if ($_val == 'NULL') {
                            $comparisons[] = $quotedSearchField . ' is ' . 'NULL';
                        } else {
                            $comparisons[] = $quotedSearchField . ' = ' . $template;
                            $fieldValues[$template] = intval($_val);
                        }
                    } else {
                        $comparisons[] = $quotedSearchField . ' = ?';
                        $fieldValues[] = intval($_val);
                    }
                } else {
                    $searchOperator = $this->_getStringSearchOperatorByDbAdapter();
                    if ($this->namedParamsAreSupported()) {
                        if ($_val == 'NULL' && $searchOperator == ' LIKE') {
                            $searchOperator = ' IS';
                            $comparisons[] =  $quotedSearchField . $searchOperator . ' ' . 'NULL';
                        } else {
                            $comparisons[] =  $quotedSearchField. $searchOperator . ' ' . $template;
                            $fieldValues[$template] = '%' . $_val . '%';
                        }
                    } else {
                        $comparisons[] = $quotedSearchField . $searchOperator . ' ?';
                        $fieldValues[] = '%' . $_val . '%';
                    }
                }
                $cont++;
            }
        }
        return array(
                '(' . implode(' or ', $comparisons). ')',
                $fieldValues
        );
    }

    protected function _isMapperSelect()
    {
        return $this->_type == 'select'
               && is_object($this->_config->getProperty("source"))
               && $this->_config->getProperty("source")->data == 'mapper';
    }

    public function namedParamsAreSupported()
    {
        /*
         * Si no tiene $dbAdapter damos por hecho que es una petición SOAP
         * y usamos un namedParameter porque MasterLogic lo espera así
         * TODO: Molaría sacar esto de aquí porque es específico de Euskaltel
         * TODO: Seguro? no depende sólo ed PDO/MySQLi?
         */
        $dbAdapter = Zend_Db_Table::getDefaultAdapter();
        return !$dbAdapter || $dbAdapter->supportsParameters('named');
    }

    protected function _getStringSearchOperatorByDbAdapter()
    {
        $dbAdapter = Zend_Db_Table::getDefaultAdapter();
        $dbAdapterClass = get_class($dbAdapter);

        if ($dbAdapterClass == 'Zend_Db_Adapter_Pdo_Pgsql') {
           return ' ILIKE';
        }
        return ' LIKE';
    }


    public function getGetterName($default = false)
    {
        if ($this->isOption()) {
            return false;
        }

        if (method_exists($this->getFieldConfig(), 'getCustomGetterName') && $default === false) {
            return $this->getFieldConfig()->getCustomGetterName();
        }

        if ($this->isDependant()) {
            return 'get' . ucfirst($this->getDbFieldName());
        } else {
            return 'get' . ucfirst($this->getModel()->columnNameToVar($this->getDbFieldName()));
        }
    }

    public function getSetterName($default = false)
    {
        if ($this->isOption()) {
            return false;
        }

        if (method_exists($this->getFieldConfig(), 'getCustomSetterName') && $default === false) {
            return $this->getFieldConfig()->getCustomSetterName();
        }

        if ($this->isDependant()) {
            return 'set' . ucfirst($this->getDbFieldName());
        } else {
            return 'set' . ucfirst($this->getModel()->columnNameToVar($this->getDbFieldName()));
        }

    }

    protected function _getDecoratorsConfig()
    {
        $fieldConfig = $this->getFieldConfig();

        if ($fieldConfig) {

            $ret = $fieldConfig->getDecorators();

            if (! empty($ret)) {
                /***
                 * Add decorator realm properties
                 */

                foreach ($ret as $key => $decorator) {
                    $decorator; //Avoid PMD UnusedLocalVariable warning
                    $fieldDecoratorClassName = 'KlearMatrix_Model_Field_' .
                                ucfirst($this->_type) . '_Decorator_' .
                                ucfirst($key);

                    if (class_exists($fieldDecoratorClassName)) {

                        $ret[$key] += array(
                            '_applyToForms' => $fieldDecoratorClassName::APPLY_TO_FORMS,
                            '_applyToLists' => $fieldDecoratorClassName::APPLY_TO_LISTS,
                            '_applyToListFiltering' => $fieldDecoratorClassName::APPLY_TO_LIST_FILTERING
                        );
                    }
                }

                return $ret;
            }
        }

        return array();
    }

    protected function _getFieldConfigToArray()
    {
        $ret = array();
        $fieldConfig = $this->getFieldConfig();
        if (!$fieldConfig) {
            return $ret;
        }

        $decoratorsConfig = $this->_getDecoratorsConfig();

        if ($decoratorsConfig) {

            $ret['decorators'] = $decoratorsConfig;
        }

        $ret['searchable'] = $fieldConfig->isSearchable();
        $ret['sortable'] = $fieldConfig->isSortable();

        if ($config = $fieldConfig->getConfig()) {
            $ret['config'] = $config;
        }

        if ($props = $fieldConfig->getProperties()) {
            $ret['properties'] = $props;
        }

        if ($errors = $fieldConfig->getCustomErrors()) {
            $ret['errors'] = $errors;
        }

        return $ret;
    }


    public function toArray()
    {
        $ret = array();

        $ret["id"] = $this->_dbFieldName;
        $ret["name"] = $this->getPublicName();
        $ret["type"] = $this->_type;

        /*
         * Propiedades de la clase booleanas.
         * Deben tener un método 'is' . ucFirst($propiedad)
         * En caso de no ser true, no "viajan"
         */
        $booleanProperties = array(
            'dirty',
            'default',
            'multilang',
            'readonly',
        );

        foreach ($booleanProperties as $prop) {
            if ($this->{'is' . ucfirst($prop)}()) {
                $ret[$prop] = true;
            }
        }

        if ($this->hasInfo()) {
            $ret['fieldInfo'] = $this->_fieldInfo->toArray();
        }

        if ($this->hasLink()) {
            $ret['link'] = $this->_link->toArray();
        }

        if ($this->_ordered) {
            $ret['order'] = $this->_orderedType;
        }

        if ($this->_hasFieldOptions) {
            $ret['options'] = $this->_options->toArray();
        }

        if ($this->_disabledOptions) {
            $ret['disabledOptions'] = $this->_disabledOptions;
        }

        if ($this->_searchSpecs) {
            $ret['search'] = $this->_searchSpecs;
        }

        $ret += $this->_getFieldConfigToArray();

        return $ret;
    }



}
