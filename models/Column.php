<?php

/**
 * Clase que devuelve la ruta al forward de _dispatch en base a la configuración a los parámetros de request
 * @author jabi
*
*/
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

        $this->_loadConfigClass();

    }

    protected function _loadConfigClass()
    {
        if ($this->isOption()) {
            return $this;
        }

        if (is_object($this->_fieldConfig)) {
            return $this;
        }

        $fieldConfigClassName = 'KlearMatrix_Model_Field_' . ucfirst($this->_type);

        $this->_fieldConfig = new $fieldConfigClassName;
        $this->_fieldConfig
                    ->setColumn($this)
                    ->init();

    }

    public function getFieldConfig()
    {
        return $this->_fieldConfig;
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

        $this->_loadConfigClass();
        return $this->_fieldConfig->getExtraJavascript();
    }

    public function getCssPaths()
    {

        return $this->_fieldConfig->getExtraCss();
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

    public function getOrderField($model)
    {
        if (method_exists($this->_fieldConfig, 'getCustomOrderField')) {
            return $this->_fieldConfig->getCustomOrderField($model);
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
    public function prepareValue($value, $model)
    {
        $this->_loadConfigClass();
        return $this->_fieldConfig->prepareValue($value, $model);
    }

    public function filterValue($value,$original)
    {
        $this->_loadConfigClass();
        return $this->_fieldConfig->filterValue($value, $original);
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

    public function setSearchSpec($name,$value)
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

    public function getSearchCondition(array $values, array $searchOps, $model, $langs)
    {


        if ( (method_exists($this->_fieldConfig, 'getCustomSearchCondition')) &&
                ($searchCondition = $this->_fieldConfig->getCustomSearchCondition($values, $searchOps, $model)) ) {

            return $searchCondition;
        }

        if (method_exists($this->_fieldConfig, 'getCustomSearchField')) {
            $searchField = $this->_fieldConfig->getCustomSearchField($model);
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

        $vals = $_fieldValues = array();

        foreach ($searchFields as $searchField) {
            $cont = 1;

            foreach ($values as $_val) {
                $template = ':' . $searchField . $cont;

                //Para los select tipo mapper no hacemos like, porque son Ids
                if ($this->_type == 'select'
                    and is_object($this->_config->getProperty("source"))
                    and $this->_config->getProperty("source")->data == 'mapper') {

                        $vals[] = $searchField .' = ' . $template;
                        $_fieldValues[$template] = intval($_val);

                } else {

                    $vals[] = 'concat('.$searchField .') like ' . $template;
                    $_fieldValues[$template] = '%'. $_val .'%';
                }
                $cont++;
            }
        }

        return array(
                '(' . implode(' or ', $vals). ')',
                $_fieldValues
        );
    }

    public function getGetterName($model, $default = false)
    {
        if ($this->isOption()) {
            return false;
        }

        if (method_exists($this->_fieldConfig, 'getCustomGetterName') && $default === false) {
            return $this->_fieldConfig->getCustomGetterName($model);
        }

        if ($this->isDependant()) {
            return 'get' . ucfirst($this->getDbFieldName());
        } else {
            return 'get' . ucfirst($model->columnNameToVar($this->getDbFieldName()));
        }

    }

    public function getSetterName($model, $default = false)
    {
        if ($this->isOption()) {
            return false;
        }

        if (method_exists($this->_fieldConfig, 'getCustomSetterName') && $default === false) {
            return $this->_fieldConfig->getCustomSetterName($model);
        }

        if ($this->isDependant()) {
            return 'set' . ucfirst($this->getDbFieldName());
        } else {
            return 'set' . ucfirst($model->columnNameToVar($this->getDbFieldName()));
        }

    }


    public function toArray()
    {

        $this->_loadConfigClass();

        $ret= array();

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
            $ret['fieldInfo'] = $this->_fieldInfo->getJSONArray();
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

        if ($this->_fieldConfig) {

            $ret['searchable'] = (bool)$this->_fieldConfig->canBeSearched();
            $ret['sortable'] = (bool)$this->_fieldConfig->canBeSorted();

            if ($config = $this->_fieldConfig->getConfig()) {
                $ret['config'] = $config;
            }

            if ($props = $this->_fieldConfig->getProperties()) {
                $ret['properties'] = $props;
            }

            if ($errors = $this->_fieldConfig->getCustomErrors()) {
                $ret['errors'] = $errors;
            }
        }

        if ($this->_searchSpecs) {
            $ret['search'] = $this->_searchSpecs;
        }

        return $ret;
    }

}
