<?php
/**
 * De esta clase extienden Screen, Dialog y Command
 * @author jabi
 */

class KlearMatrix_Model_ResponseItem
{
    const module = 'klearMatrix';

    protected $_item;
    protected $_config;

    protected $_mapper;
    protected $_modelFile;
    protected $_routeDispatcher;

    protected $_plugin;

    protected $_title;
    protected $_description;
    protected $_message; // right only, only valid for DeleteController

    protected $_customTemplate;
    protected $_customScripts;

    // Para listados dependientes de listados anteriores
    protected $_filteredField;

    // Para pantallas New sobretodo que heredan en "2 saltos" el id de un campo.
    protected $_parentField;

    // Valores "forzados" desde configuración. condiciones "duras"
    protected $_forcedValues;

    // Especifica si un filedset tiene la clase fullWidth (width: auto);
    protected $_fullWidth;

    //Condición raw injectadas al where directamente
    protected $_rawCondition;

    protected $_forcedPk;

    protected $_calculatedPk;
    protected $_calculatedPkConfig;

    protected $_actionMessages;

    protected $_fixedPositions;

    protected $_modelSpec;

    protected $_visibleColumns;

    protected $_options;

    protected $_blacklist = array();

    protected $_disableSave = false;
    protected $_disableAddAnother = false;

    /*
     * Filtros preconfigurados para pantallas ListController
     */
    protected $_preconfiguredFilters = array();


    /*
     * Filtros presetteados para pantallas ListController
    */
    protected $_presettedFilters = array();


    /*
     * Definir subarrays si el tag depende de subfijos en los nombres de campo,
     * en caso contrario indicar el tag como string
     */
    protected $_metadataBlacklist = array(
        'video' => array(
            'Source',
            'Title',
            'Thumbnail'
        ),
        'map' => array(
            'Lat',
            'Lng',
        )
    );
    /**
     * @var bool
     */
    protected $_ignoreMetadataBlacklist = false;

    protected $_hasInfo = false;
    protected $_fieldInfo;

    protected $_useExplain = false;

    protected $_sectionsBlackList = array();

    //Configuraciones comunes para todos los tipos de ResponseItem
    protected $_configOptions = array(
        '_mapper' => array('mapper', false),
        '_modelFile' => array('modelFile', false),
        '_filteredField' => array('filterField', false),
        '_filterClass' => array('filterClass', false),
        '_forcedValues' => array('forcedValues', false),
        '_rawCondition' => array('rawCondition', false),
        '_forcedPk' => array('forcedPk', false),
        '_calculatedPkConfig' => array('calculatedPk', false),
        '_plugin' => array('plugin', false),
        '_title' => array('title', false),
        '_description' => array('description', false),
        '_message' => array('message', false),
        '_customTemplate' => array('template', false),
        '_customScripts' => array('scripts', false),
        '_actionMessages' => array('actionMessages', false),
        // disableSave >> en EditController evitamos "salvar"
        '_disableSave' => array('disableSave', false),
        '_fullWidth' => array('fullWidth', false),
        // disableAddAnother >> en NewController evitamos el botón de añadir otro.
        '_disableAddAnother' => array('disableAddAnother', false),
        '_useExplain' => array('useExplain', false),
        '_sectionsBlackList' => array('sectionsBlackList', false),
        '_preconfiguredFilters' => array('preconfiguredFilters', false),
        '_presettedFilters' => array('presettedFilters', false),
        '_fixedPositions' => array('fixedPositions', false),
    );

    //Guardamos en $this->_config un objeto Klear_Model_ConfigParser
    public function setConfig(Zend_Config $config)
    {
        $this->_config = new Klear_Model_ConfigParser;
        $this->_config->setConfig($config);

        $this->_initCustomConfig();

        //Guardamos la configuración de cada propiedad
        foreach ($this->_configOptions as $option => $optConfig) {
            if ($optConfig[1]) {
                $this->$option = $this->_config->getRequiredProperty($optConfig[0]);
            } else {
                $this->$option = $this->_config->getProperty($optConfig[0]);
            }
        }

        //Si hay modelFile, lo parseamos
        if ($this->_modelFile) {
            $this->_parseModelFile();
        }

        //Si hay mapper, lo checkeamos a ver si es válido
        if ($this->_mapper) {
            $this->_checkClassesExist(array("_mapper"));
        }
        return $this;
    }

    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * Este método permite añadir otras configuraciones al objeto
     */
    protected function _initCustomConfig()
    {
    }

    //Setea la info de ayuda si existe
    public function setInfo()
    {
        //Cogemos la info del ResponseItem
        $info = $this->_config->getProperty("info");

        if ($info) {
            $this->_fieldInfo = new KlearMatrix_Model_Info;
            $this->_fieldInfo->setConfig($info);
            $this->_hasInfo = true;
        }
        return $this;
    }

    protected function _parseModelFile()
    {
        $filePath = 'klear.yaml:///model/' . $this->_modelFile;

        $cache = $this->_getCache($filePath);
        $modelConfig = $cache->load(md5($filePath));

        if (!$modelConfig) {
            $modelConfig = new Zend_Config_Yaml(
                $filePath,
                APPLICATION_ENV,
                array(
                    "yamldecoder"=>"yaml_parse"
                )
            );
            $cache->save($modelConfig);
        }

        $this->_modelSpec = new KlearMatrix_Model_ModelSpecification($modelConfig);
    }

    public function getModelSpec()
    {
        return $this->_modelSpec;

    }

    protected function _getCache($filePath)
    {
        $cacheManager = Zend_Controller_Front::getInstance()
        ->getParam('bootstrap')
        ->getResource('cachemanager');

        $cache = $cacheManager->getCache('klearconfig');
        $cache->setMasterFile($filePath);
        return $cache;
    }

    public function hasModelFile()
    {
        return (bool)$this->_modelFile;
    }


    protected function _checkClassesExist(array $properties)
    {
        foreach ($properties as $property) {

            if (!class_exists($this->{$property})) {

                throw new \Zend_Exception($this->{$property} . " no es una entidad instanciable.");
            }
        }
    }

    public function setName($name)
    {
        $this->_itemName = $name;

        return $this;
    }

    public function getItemName()
    {
        return $this->_itemName;
    }

    public function setRouteDispatcher(KlearMatrix_Model_RouteDispatcher $routeDispatcher)
    {
        $this->_routeDispatcher = $routeDispatcher;
        return $this;
    }

    public function getRouteDispatcher()
    {
        return $this->_routeDispatcher;
    }

    public function getMapperName()
    {
        return $this->_mapper;
    }

    public function getModelName()
    {
        $className = $this->_modelSpec->getClassName();
        if ($className{0} == '\\') {
            $className = substr($className, 1);
        }
        return $className;
    }

    public function getPlugin($defaultValue = '')
    {
        if (empty($this->_plugin)) {

            return $defaultValue;
        }

        return $this->_plugin;
    }

    public function getTitle()
    {
        return Klear_Model_Gettext::gettextCheck($this->_title);
    }

    public function getDescription()
    {
        return Klear_Model_Gettext::gettextCheck($this->_description);
    }

    public function getMessage()
    {
        return Klear_Model_Gettext::gettextCheck($this->_message);
    }

    public function getSectionsBlackList()
    {
        return $this->_sectionsBlackList;
    }

    public function getUseExplain()
    {
        return $this->_useExplain;
    }

    public function getCustomTemplate()
    {
        return $this->_customTemplate;
    }

    public function getCustomScripts()
    {
        return $this->_customScripts;
    }

    public function getPreconfiguredFilters()
    {
        if (is_null($this->_preconfiguredFilters)) {
            return $this->_preconfiguredFilters;
        }
        $aConf = $this->_preconfiguredFilters->toArray();
        foreach ($aConf as $filterKey => $filterData) {
            if ($filterData['title']) {
                $title = Klear_Model_Gettext::gettextCheck($filterData['title']);
                $aConf[$filterKey]['title'] = $title;
            }
        }
        $this->_preconfiguredFilters = $aConf;
        return $this->_preconfiguredFilters;
    }

    public function getPresettedFilters()
    {
        if (!$this->_presettedFilters instanceof \Zend_Config) {
            return null;
        }

        $presettedFilters = new \Zend_Config(array(), true);


        foreach ($this->_presettedFilters as $key => $presettedFilter) {

            $keys = array_keys($presettedFilter->toArray());

            if (in_array('active', $keys)) {
                $isActive = (bool)$presettedFilter->active;
                if (false === $isActive) {
                    continue;
                }
            }

            $presettedFilters->$key = $presettedFilter;

        }


        if (sizeof($presettedFilters) == 0) {
            $presettedFilters = NULL;
        }

        return $presettedFilters;
    }

    public function getConfigAttribute($attribute)
    {
        return $this->_config->getProperty($attribute);
    }


    /**
     * Devuelve un "cacho" de configuración de sección especificada con nomenclatura de clases
     * Ej. "fields->blacklist"
     * @param string $path
     * @return Zend_config|boolean
     */
    public function getRawConfigAttribute($path)
    {

        if ($this->_config->exists($path)) {
            $items = explode("->", $path);
            $ret = $this->_config->getRaw();
            foreach ($items as $item) {
                $ret = $ret->{$item};
            }
            return $ret;
        }

        return false;

    }


    public function getDisableSave()
    {
        return $this->_disableSave;
    }

    public function getFullWidth()
    {
        return $this->_fullWidth;
    }


    public function getDisableAddAnother()
    {
        return $this->_disableAddAnother;
    }


    /**
     * @param unknown_type $name
     * @param unknown_type $config
     * @return KlearMatrix_Model_Column
     */
    protected function _createColumn($name, $config)
    {
        $column = new KlearMatrix_Model_Column;
        $column->setDbFieldName($name);
        $column->setRouteDispatcher($this->_routeDispatcher);
        $column->setModel($this->getObjectInstance());

        if ($config) {

            $column->setConfig($config);
        }

        return $column;
    }

    protected function _createFileColumn($config, $fileColumn)
    {
        $column = $this->_createColumn($fileColumn, $config);
        $column->markAsFile();

        return $column;
    }

    protected function _createDependantColumn($columnConfig, $dependantConfig)
    {
        $column = $this->_createColumn($dependantConfig['property'], $columnConfig);
        $column->markAsDependant();

        return $column;
    }

    public function resetVisibleColumns()
    {
        unset($this->_visibleColumns);
        unset($this->_blacklist);
        return $this;
    }

    /**
     * El método filtrará las columnas del modelo con el fichero de configuración de modelo
     * y la whitelist/blacklist de la configuración
     *
     * TODO: Implementar lista blanca que solo muestre las columnas especificadas en la misma (showColumns o algo así)
     *
     * @return KlearMatrix_Model_ColumnCollection listado de columnas que devuelve el modelo
     */
    public function getVisibleColumns($ignoreBlackList = false)
    {
        if (isset($this->_visibleColumns)) {

            return $this->_visibleColumns;
        }

        $this->_visibleColumns =  new KlearMatrix_Model_ColumnCollection();
        $model = $this->getObjectInstance();

        if (!$ignoreBlackList) {
            $this->_createBlackList($model);
        }

        /*
         * Si estamos en una vista multi-lenguaje, instanciamos en el columnWrapper
        * que idiomas tienen los modelos disponibles
        */
        $availableLangsPerModel = $model->getAvailableLangs();
        if (count($availableLangsPerModel) > 0) {
            $this->_visibleColumns->setLangs($availableLangsPerModel);
        }

        // Campos de tipo "file"
        //TODO: Revisar este método, porque también se encarga de generar parte de la lista negra
        $fileColumns = $this->_getVisibleFileColumns($model);
        $this->_visibleColumns->addCols($fileColumns);

        // Campos Ghost
        $ghostColumns = $this->_getVisibleGhostColumns();
        $this->_visibleColumns->addCols($ghostColumns);

        // Campos de la BBDD
        $columns = $this->_getVisibleColumns($model, $ignoreBlackList);
        $this->_visibleColumns->addCols($columns);

        // Tablas dependientes
        $dependantColumns = $this->_getVisibleDependantColumns($model, $ignoreBlackList);
        $this->_visibleColumns->addCols($dependantColumns);

        if ($this->hasFieldOptions()) {
            $column = $this->_createColumn("_fieldOptions", $this->_config->getRaw()->fields->options);
            $column->markAsOption();
            $this->_visibleColumns->addCol($column);
        }

        //Ordenamos los campos si existe la configuración
        if ($this->_config->exists("fields->order")) {
            $this->_visibleColumns->sortCols($this->_config->getRaw()->fields->order);
        }

        if ($this->_config->exists("fields->readOnly")) {
            $this->_visibleColumns->setReadOnly($this->_config->getRaw()->fields->readOnly);
        }


        return $this->_visibleColumns;
    }

    protected function _createBlackList($model)
    {
        $this->_poblateBlackList($model);
        $this->_cleanBlackList();
    }

    protected function _poblateBlackList($model)
    {
        $pk = $model->getPrimaryKeyName();

        // Si la clave primaria no está en la lista blanca no la mostramos
        //TODO: comprobación de que no esté en el whitelist? sigue estando?
        $this->addFieldToBlackList($pk);

        /*
         * LLenamos el array blacklist en base al fichero de configuración
        */
        if ($this->_config->exists("fields->blacklist")) {

            $blackListConfig = $this->_config->getRaw()->fields->blacklist;

            if ($blackListConfig !== '') {

                foreach ($blackListConfig as $field => $value) {

                    $this->addFieldToBlackList($field, (bool)$value);
                }
            }
        }

        /*
         * Si es una pantalla con filtro de ventana padre no mostramos el campo de filtrado
        */
        if ($this->isFilteredScreen()) {

            $this->addFieldToBlackList($this->_filteredField);
        }

        /*
         * Si es una pantalla con valores forzados y estos no están en la lista blanca
        * no serán mostrados por defecto.
        */
        if ($this->hasForcedValues()) {

            foreach (array_keys($this->getForcedValues()) as $field) {

                $this->addFieldToBlackList($field);
            }
        }

        if ($this->_ignoreMetadataBlacklist == false) {

            $this->_blacklistFieldsByMeta($model);
        }

        /*
         * Metemos en la lista negra los campos multi-idioma.
        * Preguntaremos a sus getter genéricos con argumento de idioma.
        */
        $multiLangFields = $this->_getMultilangFields($model);
        foreach ($multiLangFields as $field) {
            $this->_blacklist[$field] = true;
        }
    }

    /**
     * Allow /Deny field blacklisting based on ddbb tags
     * @param $ignore bool
     */
    public function setIgnoreMetadataBlacklist($ignore)
    {
        $this->_ignoreMetadataBlacklist = (bool) $ignore;
    }

    /**
     * Hace una criba de columnas visibles a los COMMENT del campo en ddbb
     * @return bool
     */
    protected function _blacklistFieldsByMeta ($model)
    {
        //FIXME method_exists condition, just for backward compatibility reasons
        if (method_exists($model, 'getColumnsMeta')) {

            $fieldsMetadata = $model->getColumnsMeta();
            foreach ($fieldsMetadata as $field => $metatags) {

                foreach ($metatags as $tag) {

                    if (! $this->_blacklistFieldMetaMatch($tag)) {

                        continue;
                    }

                    if (is_array($this->_metadataBlacklist[$tag])) {

                        foreach ($this->_metadataBlacklist[$tag] as $suffix) {

                            $this->addFieldToBlackList($field . $suffix, true);
                        }

                    } else {

                        $this->addFieldToBlackList($field, true);
                        break;
                    }
                }
            }
        }
    }

    /**
     * @return bool
     */
    protected function _blacklistFieldMetaMatch($tag)
    {
        $tag = is_array($tag) ? key($tag) : $tag;

        if (in_array($tag, $this->_metadataBlacklist)) {

            return true;
        }

        if (in_array($tag, array_keys($this->_metadataBlacklist))) {

            return true;
        }

        return false;
    }

    public function addFieldToBlackList($field, $toBlacklist = true)
    {
        $this->_blacklist[$field] = (bool)$toBlacklist;
    }

    public function addFieldsToBlackList($fields)
    {
        foreach ($fields as $field => $value) {
            $this->addFieldToBlackList($field, $value);
        }
    }

    protected function _getBlacklistedFields()
    {
        return array_keys($this->_blacklist);
    }

    protected function _getMultilangFields($model)
    {
        $returnMultiLangFields = array();

        $availableLangsPerModel = $model->getAvailableLangs();
        $multiLangFields = $model->getMultiLangColumnsList();

        foreach ($multiLangFields as $dbFieldName => $columnName) {
            $columnName; //Avoid PMD UnusedLocalVariable warning
            foreach ($availableLangsPerModel as $langIden) {

                $returnMultiLangFields[] = $dbFieldName . '_'. $langIden;
            }
        }
        return $returnMultiLangFields;
    }

    /**
     * Removes whitelisted and false valued blacklist elements
     */
    protected function _cleanBlackList()
    {
        foreach ($this->_blacklist as $key => $value) {
            if ($this->_config->exists("fields->whitelist->" . $key) || $value === false) {
                unset($this->_blacklist[$key]);
            }
        }
    }

    /**
     * Devuelve las columnas visibles de tipo "file"
     * @param object $model
     * @return array
     */
    protected function _getVisibleFileColumns($model)
    {
        $columns = array();
        $blacklistSubfields = array(
            'sizeName',
            'mimeName',
            'baseNameName',
            'md5SumName');

        if (method_exists($model, 'getFileObjects')) {

            $fileObjects = $model->getFileObjects();

            foreach ($fileObjects as $_fileCol) {

                $columnConfig = $this->_modelSpec->getField($_fileCol);

                if ($columnConfig) {

                    $fieldSpecsGetter = "get" . $_fileCol . "Specs";
                    $involvedFields = $model->{$fieldSpecsGetter}();

                    foreach ($blacklistSubfields as $blSubfield) {
                        if (isset($involvedFields[$blSubfield])) {

                            $columnName = $model->varNameToColumn($involvedFields[$blSubfield]);
                            $this->_blacklist[$columnName] = true;
                        }
                    }

                    // FIXME: Aquí nos estamos saltando un posible ignoreBlackList...
                    if (isset($this->_blacklist[$_fileCol])) {

                        continue;
                    }

                    $column = $this->_createFileColumn($columnConfig, $_fileCol);
                    $columns[] = $column;
                }
            }
        }
        return $columns;
    }

    /*
     * Devuelve array con la lista de columnas de tipo ghost que no se encuentran en la blacklist
    */
    protected function _getVisibleGhostColumns()
    {
        $columns = array();
        foreach ($this->_modelSpec->getFields() as $key => $field) {

            if ($field->type == 'ghost' && !isset($this->_blacklist[$key])) {

                $columns[] = $this->_createColumn($key, $field);
            }
        }
        return $columns;
    }

    protected function _getVisibleColumns($model, $ignoreBlackList = false)
    {
        $columns = array();
        /*
         * Iteramos sobre todos los campos
        */
        $dbFieldNames = array_keys($model->getColumnsList());
        foreach ($dbFieldNames as $dbFieldName) {
            /*
             * TODO: Revisar esto, en principio ya no debería hacer falta comprobar el $ignoreBlackList,
            *       la lista no se genera si no es necesaria, pero hay que repasar el método _getVisibleFileColumns.
            */
            if (!$ignoreBlackList && isset($this->_blacklist[$dbFieldName])) {

                continue;
            }

            $config = $this->_modelSpec->getField($dbFieldName);

            //Si es un campo ghost, pasamos de él. Ya estaba metido antes
            if (isset($config->type) && $config->type == 'ghost') {

                continue;
            }

            $column = $this->_createColumn($dbFieldName, $config);

            $multiLangFields = $model->getMultiLangColumnsList();
            if (isset($multiLangFields[$dbFieldName])) {

                $column->markAsMultilang();
            }

            $columns[] = $column;
        }

        return $columns;
    }

    /**
     * Devuelve las columans de tipo dependant
     * @param unknown_type $model
     */
    protected function _getVisibleDependantColumns($model, $ignoreBlacklist = false)
    {
        $columns = array();
        foreach ($model->getDependentList() as $dependantConfig) {

            if (!$ignoreBlacklist && isset($this->_blacklist[$dependantConfig['property']])) {

                continue;
            }

            $columnConfig = $this->_modelSpec->getField($dependantConfig['property']);
            if ($columnConfig) {

                $column = $this->_createDependantColumn($columnConfig, $dependantConfig);
                $columns[] = $column;
            }
        }
        return $columns;
    }


    /**
     * Recuperar y crear una objeto tipo Column
     * @param unknown_type $columnName
     */
    public function getColumn($columnName)
    {
        $model = $this->getObjectInstance();

        $columnList = $model->getColumnsList();
        if (isset($columnList[$columnName])) {

            $column = $this->_createColumn($columnName, $this->_modelSpec->getField($columnName));

            return $column;
        }

        foreach ($model->getDependentList() as $dependantConfig) {

            if ($columnName == $dependantConfig['table_name']) {

                $columnConfig = $this->_modelSpec->getField($dependantConfig['table_name']);
                if (!$columnConfig) {

                    return false;
                }

                $column = $this->_createDependantColumn($columnConfig, $dependantConfig);
                return $column;
            }
        }

        // TODO: Cambiar esto, deberíamos estar seguros de que getFileObjects existe.
        if (!method_exists($model, 'getFileObjects')) {

            return false;
        }

        $fileObjects = $model->getFileObjects();
        if (in_array($columnName, $fileObjects)) {

            $columnConfig = $this->_modelSpec->getField($columnName);
            if ($columnConfig) {

                return $this->_createFileColumn($columnConfig, $columnName);
            }
        }

        return false;
    }

    public function isParentDependantScreen()
    {
        return (!empty($this->_parentField));
    }

    public function isFilteredScreen()
    {
        return (!empty($this->_filteredField));
    }

    public function hasFilterClass()
    {
        return (!empty($this->_filterClass));
    }

    public function getFilterClassCondition()
    {
        $filterClass = new $this->_filterClass;
        if ( !$filterClass instanceof KlearMatrix_Model_Interfaces_FilterList) {
            throw new Exception('List filters must implement KlearMatrix_Model_Interfaces_FilterList');
        }
        $filterClass->setRouteDispatcher($this->_routeDispatcher);

        return array($filterClass->getCondition(),array());
    }

    public function getFilterField()
    {
        return $this->_filteredField;
    }

    public function getFilteredCondition($value)
    {
        return $this->_getCondArray($this->_filteredField, $value, 'filtered');
    }

    public function hasForcedValues()
    {
        return sizeof($this->_forcedValues) > 0;
    }

    public function getForcedValuesConditions()
    {
        $forcedValueConds = array();

        foreach ($this->_forcedValues as $field => $value) {
            //FIXME: Aquí se confía demasiado en el rand, podrían repetirse valores...
            $valConstant = 'v' . rand(1000, 9999);
            $forcedValueConds[] = $this->_getCondArray($field, $value, $valConstant);
        }

        return $forcedValueConds;
    }


    public function hasRawCondition()
    {

        return isset($this->_rawCondition);
    }

    public function getRawCondition()
    {
        if ($this->hasRawCondition()) {
            return $this->_rawCondition;
        }

        return false;
    }


    public function _getCondArray($field, $value, $paramName = null)
    {
        $dbAdapter = Zend_Db_Table::getDefaultAdapter();

        if ($dbAdapter) {
            $field = $dbAdapter->quoteIdentifier($field);
        }

        if (is_null($value) || $value == 'NULL') {
            return $field . ' is NULL';
        }

        /*
         * Si no tiene $dbAdapter damos por hecho que es una petición SOAP
         * y usamos un namedParameter porque MasterLogic lo espera así
         * TODO: Molaría sacar esto de aquí porque es específico de Euskaltel
         */
        if (!$dbAdapter || ($paramName && $dbAdapter->supportsParameters('named'))) {
            return array(
                $field . ' = :' . $paramName,
                array(':' . $paramName => $value)
            );
        }

        return array(
            $field . " = ? ",
            array($value)
        );
    }

    public function getForcedValues()
    {
        $ret = array();

        foreach ($this->_forcedValues as $field=> $value) {

            $ret[$field] = $value;
        }

        return $ret;
    }

    /**
     * Devuelve el primary Key especifico,
     * Comprueba forced y calculated PK
     * o consulta con mainRouter
     * @return false|integer
     */
    public function getCurrentPk()
    {
        // Devuelve el PK para la pantalla de edit.
        $pk = $this->getForcedPk();
        if (!empty($pk)) {
            return $pk;
        }

        $pk = $this->getCalculatedPk();
        if (!empty($pk)) {
            return $pk;
        }

        return $this->_routeDispatcher->getParam("pk");
    }

    public function getForcedPk()
    {
        return $this->_forcedPk;
    }

    /**
     * Se deshabilita la configuración de calculatedPk, para evitar que se recalcule
     * el ID en el método de save (que ya viene resuelto)
     */
    public function unsetCalculatedPk()
    {
        $this->_calculatedPkConfig = NULL;
        $this->_calculatedPk = false;
    }

    public function getCalculatedPk()
    {
        if (is_null($this->_calculatedPkConfig)) {

            return false;
        }

        $class = $this->_calculatedPkConfig->class;
        $method = $this->_calculatedPkConfig->method;
        if (!$class || !$method) {

            return false;
        }

        $pkCalculator = new $class;
        $this->_calculatedPk = $pkCalculator->{$method}($this->_routeDispatcher);

        if (!$this->_calculatedPkConfig) {

            return false;
        }

        return $this->_calculatedPk;
    }

    public function getParentField()
    {
        return $this->_parentField;
    }

    public function hasFieldOptions()
    {
        return ($this->_config->exists("fields->options"));
    }

    public function hasEntityPostSaveOptions()
    {
        return ($this->_config->exists("entityPostSaveOptions"));
    }

    /**
     * Devuelve un array de objetos FieldOption (opciones por campo),
     * a partir de las columnas de tipo Option del ColWrapper
     */
    public function getScreenFieldsOptionsConfig()
    {
        $parent = $this->_visibleColumns->getOptionColumn()->getKlearConfig();

        return $this->_getItemFieldsOptionsConfig('screen', $parent);
    }

    public function getDialogsFieldsOptionsConfig()
    {
        $parent = $this->_visibleColumns->getOptionColumn()->getKlearConfig();

        return $this->_getItemFieldsOptionsConfig('dialog', $parent);
    }

    public function getCommandsFieldsOptionsConfig()
    {
        $parent = $this->_visibleColumns->getOptionColumn()->getKlearConfig();

        return $this->_getItemFieldsOptionsConfig('command', $parent);
    }

    public function getScreenEntityPostSaveOptionsConfig()
    {
        $config = $this->_config->getProperty('entityPostSaveOptions');
        $parent = new Klear_Model_ConfigParser;
        $parent->setConfig($config);

        return $this->_getItemFieldsOptionsConfig('screen', $parent);
    }


    /**
     * @return KlearMatrix_Model_OptionCollection
     */
    public function getScreenOptions()
    {
        $generalOptions = new KlearMatrix_Model_OptionCollection();

        if ( (!$this->_config->exists("options")) || ($this->_config->getRaw()->options == '') ) {

            return $generalOptions;
        }

        $parent = new Klear_Model_ConfigParser();
        $parent->setConfig($this->_config->getRaw()->options);

        if ($parent->getProperty("placement")) {
            $generalOptions->setPlacement($parent->getProperty("placement"));
        }


        $options = $this->_getItemFieldsOptionsConfig('screen', $parent);

        foreach ($options as $_screen) {

            $screenOption = new KlearMatrix_Model_ScreenOption;
            $screenOption->setName($_screen);
            $screenOption->setConfig($this->_routeDispatcher->getConfig()->getScreenConfig($_screen));
            $generalOptions->addOption($screenOption);
        }

        $options = $this->_getItemFieldsOptionsConfig('dialog', $parent);

        foreach ($options as $_dialog) {

            $dialogOption = new KlearMatrix_Model_DialogOption;
            $dialogOption->setName($_dialog);
            $dialogOption->setConfig($this->_routeDispatcher->getConfig()->getDialogConfig($_dialog));
            $generalOptions->addOption($dialogOption);
        }

        return $generalOptions;
    }

    public function getActionMessages()
    {
        $msgs = new KlearMatrix_Model_ActionMessageCollection();

        if (!$this->_actionMessages) {
            return $msgs;
        }

        foreach ($this->_actionMessages as $_type => $msgConfig) {

            $msg = new KlearMatrix_Model_ActionMessage();
            $msg->setType($_type);
            $msg->setConfig($msgConfig);
            $msgs->addMessage($msg);
        }

        return $msgs;
    }


    public function getFixedPositions()
    {
        $positions = new KlearMatrix_Model_FieldPositionCollection();

        if (!$this->_fixedPositions) {
            return $positions;
        }

        foreach ($this->_fixedPositions as $positionData) {
            $pos = new \KlearMatrix_Model_FieldPosition;
            $pos->setConfig($positionData);
            $positions->addPosition($pos);
        }

        return $positions;
    }

    public function getDialogsGeneralOptionsConfig()
    {
        if ((!$this->_config->exists("options"))
            || ($this->_config->getRaw()->options == '')) {
            return array();
        }

        $parent = new Klear_Model_ConfigParser();
        $parent->setConfig($this->_config->getRaw()->options);

        return $this->_getItemFieldsOptionsConfig('dialog', $parent);
    }

    public function getPaginationConfig()
    {
        if (!$this->_config->exists("pagination")) {
            return null;
        }

        $pagination = new Klear_Model_ConfigParser();
        $pagination->setConfig($this->_config->getRaw()->pagination);
        return $pagination;
    }


    /**
     * Devuelve el Objeto info (ayuda inline), resuelto a array para JSON-ear.
     * Listo para ser enchufado a Matrixresponse.
     * @return boolean
     */
    public function getInfo()
    {
        if ($this->_hasInfo) {
            return $this->_fieldInfo;
        }

        return false;
    }

    public function getOrderConfig()
    {
        if (!$this->_config->exists("order")) {
            return false;
        }

        $orderConfig = new Klear_Model_ConfigParser();
        $orderConfig->setConfig($this->_config->getRaw()->order);

        return $orderConfig;
    }

    public function _getItemFieldsOptionsConfig($type, $parent)
    {
        $retArray = array();

        switch($type) {
            case 'dialog':
                $property = 'dialogs';
                break;

            case 'screen':
                $property = 'screens';
                break;

            case 'command':
                $property = 'commands';
                break;
            case 'entityPostSaveOptions':
                $property = 'entityPostSaveOptions';
                break;
            default:
                Throw new Zend_Exception("Undefined Option Type");
                break;
        }

        $_items = $parent->getProperty($property);

        if (!$_items) {
            return array();
        }

        foreach ($_items  as $_item=> $_enabled) {
            if (!(bool)$_enabled) {
                continue;
            }
            $retArray[] = $_item;
        }

        return $retArray;
    }

    /**
     * gateway hacia modelo específico, para devolver el nombre de la PK
     */
    public function getPkName()
    {
        return $this->_modelSpec->getInstance()->getPrimaryKeyName();
    }

    /**
     * gateway hacia modelo específico, para devolver la instancia del objeto "vacío"
     */
    public function getObjectInstance()
    {
        return $this->_modelSpec->getInstance();
    }

    public function getType()
    {
        return $this->_type;
    }
}
