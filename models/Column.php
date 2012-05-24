<?php

/**
 * Clase que devuelve la ruta al forward de _dispatch en base a la configuración a los parámetros de request
* @author jabi
*
*/
class KlearMatrix_Model_Column {

    protected $_dbName;
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
    protected $_type ='text';

    protected $_hasFieldOptions = false;
    protected $_options = false;

    protected $_isMultilang = false;

    protected $_isDependant = false;

    protected $_isFile = false;

    protected $_routeDispatcher;

    public function setDbName($name)
    {
        $this->_dbName = $name;
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

    public function isMultilang() {
        return $this->_isMultilang;
    }

    public function isFile()
    {
        return $this->_isFile;
    }

    public function setConfig(Zend_Config $config) {

        $this->_config = new Klear_Model_KConfigParser;
        $this->_config->setConfig($config);

        $this->_publicName = $this->_config->getProperty("title",false);

        if ($this->_dbName == '_fieldOptions') {
            $this->markAsOption();
            $this->_parseOption();
        } else {
            $this->_parseField();
        }
    }

    protected function _parseOption() {

        $this->_type = '_option';

        if ($default = $this->_config->getProperty("default",false)) {
            $this->_defaultOption = $default;
        }
    }

    protected function _parseField() {

        $this->_isDefault = (bool)$this->_config->getProperty("default",false);
        $this->_isReadonly = (bool)$this->_config->getProperty("readonly",false);

        $this->_hasInfo = (bool)$this->_config->getProperty("info",false);


        $this->_type = $this->_config->getProperty("type",false);
        if (empty($this->_type)) {
            $this->_type = 'text';
        }

        if ($this->_config->getProperty("options",false)) {
            $this->_hasFieldOptions = true;
            $this->_parseColumnOptions();
        }

        $this->_loadConfigClass();

    }

    protected function _loadConfigClass() {
        if ($this->isOption()) return $this;
        if (is_object($this->_fieldConfig)) return $this;

        $fieldConfigClassName = 'KlearMatrix_Model_Field_' . ucfirst($this->_type);

        $this->_fieldConfig = new $fieldConfigClassName;
        $this->_fieldConfig
                    ->setColumn($this)
                    ->init();

    }

    public function getFieldConfig() {
        return $this->_fieldConfig;
    }

    /**
     * @return KlearMatrix_Model_RouteDispatcher
     */
    public function getRouteDispatcher()
    {
        return $this->_routeDispatcher;
    }

    public function getJsPaths() {
        $this->_loadConfigClass();
        return $this->_fieldConfig->getExtraJavascript();
    }

    public function getCssPaths() {

        return $this->_fieldConfig->getExtraCss();
    }

    public function isDefault() {
        return $this->_isDefault;
    }

    public function isReadonly() {

        if ($this->isOption()) return false;

        return $this->_isReadonly;

    }

    public function hasInfo() {

        if ($this->isOption()) return false;
        if ($this->_hasInfo === false) {
            return false;
        }
        $info = $this->_config->getProperty("info",false);
        $infoConfig = new Klear_Model_KConfigParser();
        $infoConfig->setConfig($info);
        $this->_fieldInfo = array();
        $this->_fieldInfo['type'] = $infoConfig->getProperty('type')? $infoConfig->getProperty('type'):'tooltip';
        $this->_fieldInfo['text'] = $infoConfig->getProperty('text');
        $this->_fieldInfo['position'] = $infoConfig->getProperty('position')? $infoConfig->getProperty('position'):'left';
        $this->_fieldInfo['icon'] = $infoConfig->getProperty('icon')? $infoConfig->getProperty('icon'):'help';
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

        return $this->_dbName;
    }

    /**
     * @return Klear_Model_KConfigParser
     */
    public function getKlearConfig() {
        return $this->_config;

    }

    public function getPublicName() {
        if (null !== $this->_publicName) {
            return $this->_publicName;
        }

        return $this->_dbName;
    }

    public function getDbName() {
        return $this->_dbName;
    }

    public function getType() {
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

    public function _parseColumnOptions() {

        if ($this->_config->getProperty("options",false)) {
            $this->_options  = new KlearMatrix_Model_OptionsWrapper;

            foreach ($this->_config->getProperty("options")->screens  as $_screen => $enabled) {
                if (!(bool)$enabled) continue;

                $screenOption = new KlearMatrix_Model_ScreenOption;
                $screenOption->setScreenName($_screen);
                $screenOption->setConfig($this->_routeDispatcher->getConfig()->getScreenConfig($_screen));

                if ( ($this->_routeDispatcher->getCurrentItem()->isFilteredScreen()) &&
                        ( $screenOption->getFilterField() !=
                        $this->_routeDispatcher->getCurrentItem()->getFilterField()) ) {
                    continue;
                }

                $this->_options->addOption($screenOption);
            }

            //TO-DO : Opciones de dialogo para campos??? El no va a más!!! LOCURA!!
        }
    }

    public function getSearchCondition(array $values,$model, $langs) {

        if (method_exists($this->_fieldConfig, 'getCustomSearchField')) {
            $searchField = $this->_fieldConfig->getCustomSearchField($model);
        } else {
            $searchField = $this->_dbName;
        }

        if ($this->isMultilang()) {
            $searchFields = array();
            foreach($langs as $_lang) {
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

                //Para los select no hacemos like, porque son Ids
                if ($this->_type == 'select') {

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
                '(' . implode(' or ',$vals). ')',
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
            return 'get' . $this->getDbName();
        } else {
            return 'get' . $model->columnNameToVar($this->getDbName());
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
            return 'set' . $this->getDbName();
        } else {
            return 'set' . $model->columnNameToVar($this->getDbName());
        }

    }


    public function toArray()
    {

        $this->_loadConfigClass();

        $ret= array();

        $ret["id"] = $this->_dbName;
        $ret["name"] = $this->getPublicName();
        $ret["type"] = $this->_type;

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
            $ret['fieldInfo'] = $this->_fieldInfo;
        }

        if ($this->_ordered) {
            $ret['order'] = $this->_orderedType;
        }

        if ($this->_hasFieldOptions) {
            $ret['options'] = $this->_options->toArray();
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
        }

        return $ret;
    }

}
