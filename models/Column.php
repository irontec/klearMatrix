<?php
class KlearMatrix_Model_Column
{
    protected $_dbFieldName;
    protected $_publicName;
    protected $_isDefault = false;
    protected $_isReadonly = false;

    protected $_hasInfo = false;
    protected $_fieldInfo = false;

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

    public function markAsReadOnly()
    {
        $this->_isReadonly = true;
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
        return $this->getFieldConfig()->getExtraCss();
    }

    public function isDefault()
    {
        return $this->_isDefault;
    }

    public function isReadonly()
    {

        if ($this->isOption()) return false;

        return $this->_isReadonly;

    }

    public function hasInfo()
    {

        if ($this->isOption()) return false;

        return $this->_hasInfo;
    }

    public function setAsOrdered()
    {
        $this->_ordered = true;
    }

    public function setOrderedType($_orderType)
    {
        $this->_orderedType = $_orderType;
    }

    public function getOrderField()
    {
        $orderField = $this->getFieldConfig()->getCustomOrderField();
        if ($orderField) {
            return $orderField;
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
            return $this->_publicName;
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
        $this->_searchSpecs[$name] = $value;

    }

    protected function _parseScreenOptions()
    {
        if (!$this->_config->getProperty("options")->screens) {
            return;
        }

        foreach ($this->_config->getProperty("options")->screens  as $_screen => $enabled) {
            if (!(bool)$enabled) {
                continue;
            }

            $screenOption = new KlearMatrix_Model_ScreenOption;
            $screenOption->setName($_screen);
            $screenOption->setConfig($this->_routeDispatcher->getConfig()->getScreenConfig($_screen));

            if ($this->_optionMustBeAdded($screenOption)) {
                $this->_options->addOption($screenOption);
            }
        }
    }

    // TODO: Sacar esta comprobación de aquí, parece que el propio $screenOption podría saber si debe añadirse o no
    protected function _optionMustBeAdded(KlearMatrix_Model_ScreenOption $screenOption)
    {
        return !$this->_routeDispatcher->getCurrentItem()->isFilteredScreen()
                || !$screenOption->getFilterField()
                || ($screenOption->getFilterField() == $this->_routeDispatcher->getCurrentItem()->getFilterField());
    }

    protected function _parseDialogOptions()
    {
        if (!$this->_config->getProperty("options")->dialogs) {
            return;
        }

        foreach ($this->_config->getProperty("options")->dialogs  as $_dialog => $enabled) {

            if (!(bool)$enabled) {
                continue;
            }

            $dialogOption = new KlearMatrix_Model_DialogOption;
            $dialogOption->setName($_dialog);
            $dialogOption->setConfig($this->_routeDispatcher->getConfig()->getDialogConfig($_dialog));

            $this->_options->addOption($dialogOption);
        }
    }


    public function _parseColumnOptions()
    {

        if ($this->_config->getProperty("options")) {
            $this->_options  = new KlearMatrix_Model_OptionCollection();

            $this->_parseScreenOptions();
            $this->_parseDialogOptions();
            //TO-DO : Opciones de dialogo para campos??? El no va a más!!! LOCURA!!
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
            $disabledOptions['label'] = $disabledConfig->getProperty('label');
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
        foreach ($searchFields as $searchField) {
            $cont = 1;

            foreach ($values as $_val) {
                $template = ':' . $searchField . $cont;

                //Para los select tipo mapper no hacemos like, porque son Ids
                if ($this->_isMapperSelect()) {

                    if ($this->_namedParamsAreSupported()) {
                        $comparisons[] = $searchField . ' = ' . $template;
                        $fieldValues[$template] = intval($_val);
                    } else {
                        $comparisons[] = $searchField . ' = ?';
                        $fieldValues[] = intval($_val);
                    }

                } else {

                    if ($this->_namedParamsAreSupported()) {
                        $comparisons[] = 'concat(' . $searchField . ') like ' . $template;
                        $fieldValues[$template] = '%' . $_val . '%';
                    } else {
                        $comparisons[] = 'concat(' . $searchField . ') like ?';
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

    protected function _namedParamsAreSupported()
    {
        /*
         * Si no tiene $dbAdapter damos por hecho que es una petición SOAP
         * y usamos un namedParameter porque MasterLogic lo espera así
         * TODO: Molaría sacar esto de aquí porque es específico de Euskaltel
         */
        $dbAdapter = Zend_Db_Table::getDefaultAdapter();
        return !$dbAdapter || $dbAdapter->supportsParameters('named');
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

    public function toArray()
    {
        $ret = array();

        $ret["id"] = $this->_dbFieldName;
        $ret["name"] = $this->getPublicName();
        $ret["type"] = $this->_type;

        if ($this->_dirty) {
            $ret["dirty"] = true; //Para mostrar el valor con html si está a true
        }

        if ($this->isDefault()) {
            $ret['default'] = true;
        }

        if ($this->isMultilang()) {
            $ret['multilang'] = true;
        }

        if ($this->isReadonly()) {
            $ret['readonly'] = true;
        }

        if ($this->hasInfo()) {
            $ret['fieldInfo'] = $this->_fieldInfo->getJsonArray();
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

        $fieldConfig = $this->getFieldConfig();
        if ($fieldConfig) {

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
        }

        if ($this->_searchSpecs) {
            $ret['search'] = $this->_searchSpecs;
        }

        return $ret;
    }

}
