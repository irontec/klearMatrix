<?php

/**
 * Clase que devuelve la ruta al forward de _dispatch en base a la configuración a los parámetros de request
* @author jabi
*
*/
class KlearMatrix_Model_ResponseItem {

	const module = 'klearMatrix';

	protected $_item;
	protected $_config;

	protected $_mapper;
	protected $_modelFile;
	protected $_routeDispatcher;

	protected $_title;

	// Para listados dependientes de listados anteriores
	protected $_filteredField;

	// Para pantallas New sobretodo que heredan en "2 saltos" el id de un campo.
	protected $_parentField;

	protected $_modelSpec;

	protected $_visibleColumnWrapper;

	protected $_options;

	protected $_blacklist = array();

	public function setConfig(Zend_Config $config) {

		$this->_config = new Klear_Model_KConfigParser;
		$this->_config->setConfig($config);

		$this->_mapper = $this->_config->getProperty("mapper",true);
		$this->_modelFile = $this->_config->getProperty("modelFile",true);

		$this->_filteredField = $this->_config->getProperty("filterField",false);

		$this->_parentField = $this->_config->getProperty("parentField",false);


		$this->_title = $this->_config->getProperty("title",false);


	    $this->_parseModelFile();
	    $this->_checkClasses(array("_mapper"));


	}

	protected function _parseModelFile() {
		$filePath = $this->_routeDispatcher->getConfig()->getConfigPath() . 'model/' . $this->_modelFile;

		$modelConfig = new Zend_Config_Yaml(
				$filePath,
				APPLICATION_ENV
		);

		$this->_modelSpec = new KlearMatrix_Model_ModelSpecification;
		$this->_modelSpec->setConfig($modelConfig);

	}

	protected function _checkClasses(array $properties) {


		foreach ($properties as $property) {
			if (!class_exists($this->{$property})) {
				Throw new Zend_Exception( $this->{$property} . " no es una entidad instanciable.");
			}

		}

	}

    public function setItemName($name) {
		$this->_itemName = $name;
		return $this;
	}

    public function getItemName() {
		return $this->_itemName;
	}

	public function setRouteDispatcher(KlearMatrix_Model_RouteDispatcher $routeDispatcher) {
		$this->_routeDispatcher = $routeDispatcher;
	}


	public function getMapperName() {
		return $this->_mapper;
	}


	public function getModelName() {
		return $this->_modelSpec->getClassName();
	}

	public function getTitle() {
	    return $this->_title;
	}

	public function getConfigAttribute($attribute) {

	    return $this->_config->getProperty($attribute,false);
	}


	protected function _createCol($name,$config) {
        $col = new KlearMatrix_Model_Column;
        $col->setDbName($name);
        if ($config) {
		    $col->setConfig($config);
        }
        $col->setRouteDispatcher($this->_routeDispatcher);
        return $col;

	}

	protected function _createFileColumn($config, $fileColumn) {
        $col = $this->_createCol($fileColumn, $config);
        $col->markAsFile();
		return $col;
	}

	/**
	 * Instancia en self::_visibleColumnWrapper las columnas tipo file del modelo
	 * @param unknown_type $model
	 */
	protected function _loadFileColumns($model) {

	    if (method_exists($model, 'getFileObjects')) {

	        $fileObjects = $model->getFileObjects();

	        foreach($fileObjects as $_fileCol) {

		        if ($colConfig = $this->_modelSpec->getField($_fileCol)) {

                    $col = $this->_createFileColumn($colConfig, $_fileCol);
		            $this->_visibleColumnWrapper->addCol($col);

		            $fieldSpecsGetter = "get" . $_fileCol . "Specs";
		            $involvedFields = $model->{$fieldSpecsGetter}();

		            if (isset($involvedFields['sizeName'])) {
		                $this->_blacklist[$model->varNameToColumn($involvedFields['sizeName'])] = true;
		            }
		            if (isset($involvedFields['mimeName'])) {
		                $this->_blacklist[$model->varNameToColumn($involvedFields['mimeName'])] = true;
		            }
		            if (isset($involvedFields['baseNameName'])) {
		                $this->_blacklist[$model->varNameToColumn($involvedFields['baseNameName'])] = true;
		            }

		        }

		    }

		}

	}


	protected function _createDependantColumn($colConfig, $dependantConfig) {
	    $col = $this->_createCol($dependantConfig['table_name'], $colConfig);
        $col->markAsDependant();
        return $col;
	}


	/**
	 * Instancia en self::_visibleColumnWrapper las columnas tipo dependant
	 * @param unknown_type $model
	 */
	protected function _loadDependantColumns($model) {

		foreach ($model->getDependentList() as $dependatConfig) {

		    if (isset($this->_blacklist[$dependatConfig['table_name']])) continue;

		    if ($colConfig = $this->_modelSpec->getField($dependatConfig['table_name'])) {
                $col = $this->_createDependantColumn($colConfig, $dependatConfig);
                $this->_visibleColumnWrapper->addCol($col);
		    }

		}

	}

	/**
	 * El método filtrará las columnas del modelo con el fichero de configuración de modelo y la whitelist/blacklist de la configuración
	 *
	 * return KlearMatrix_Model_ColumnWrapper $_visibleColumnWrapper listado de columnas que devuelve el modelo
	 */
	public function getVisibleColumnWrapper($ignoreBlackList = false, $lazyload = false) {
		if (isset($this->_visibleColumnWrapper)) return $this->_visibleColumnWrapper;

		$model = $this->_modelSpec->getInstance();

		$this->_visibleColumnWrapper =  new KlearMatrix_Model_ColumnWrapper;

		$pk = $model->getPrimaryKeyName();

		// La primary Key estará por defecto en la blackList, a excepción de encontrarse en la whitelist
		if  (!$this->_config->exists("fields->whitelist->" . $pk)) {
			$this->_blacklist[$pk] = true;
		}


		/*
		 * Si el modelo tiene el método getFileObjects, y éstos están definidos en la configuración
		*/
        $this->_loadFileColumns($model);


	   /*
	    * LLenamos el array blacklist en base al fichero de configuración
	    */
		if ($this->_config->exists("fields->blacklist")) {
			if (($_blacklistConfig = $this->_config->getRaw()->fields->blacklist) !== '') {

				foreach($_blacklistConfig as $field => $value) {
					if ((bool)$value) {
						$this->_blacklist[$field] = true;
					}
				}
			}
		}

		/*
		 * Si es una pantalla con filtro de ventana padre, no mostraremos por defecto el campo de filtrado
		 */
		if ($this->isFilteredScreen()) {
		    $this->_blacklist[$this->_filteredField] = true;
		}


		/*
 		* Si estamos en una vista multi-lenguaje, instanciamos en el columnWrapper que idiomas tienen los modelos disponibles
 		*/
		$multiLangFields = $model->getMultiLangColumnsList();

		if ( (is_array($availableLangsPerModel = $model->getAvailableLangs())) && (sizeof($availableLangsPerModel)>0) ) {
		    $this->_visibleColumnWrapper->setLangs($availableLangsPerModel);
		}

		/*
		 * Metemos en la lista negra los campos multi-idioma. Preguntaremos a sus getter genéricos con argumento de idioma.
		 */
		foreach($multiLangFields as $dbName=>$columnName) {
		    foreach($availableLangsPerModel as $langIden) {

		        $this->_blacklist[$dbName . '_'. $langIden] = true;

		    }
		}


        /*
         * Iteramos sobre todos los campos
         */
		foreach($model->getColumnsList() as $dbName => $attribute) {
		    if ( (!$ignoreBlackList) && (isset($this->_blacklist[$dbName])) ) continue;

		    $config = $this->_modelSpec->getField($dbName);
			$col = $this->_createCol($dbName, $config);

			if (isset($multiLangFields[$dbName])) {
			    $col->markAsMultilang();
			}

			$this->_visibleColumnWrapper->addCol($col);
		}



	   /**
	    *  Buscamos las tablas dependientes, por si estuvieran *Explicitamente* declaradas en el fichero de modelo
	    */

		$this->_loadDependantColumns($model);


		if ($this->hasFieldOptions()) {

			$col = $this->_createCol("_fieldOptions", $this->_config->getRaw()->fields->options);
		    $col->markAsOption();
		    $this->_visibleColumnWrapper->addCol($col);
		}



		return $this->_visibleColumnWrapper;

	}


	/**
	 * Recuperar y crear una objeto tipo Column
	 * @param unknown_type $colName
	 */
	public function getColumn($colName) {

	    $model = $this->_modelSpec->getInstance();

	    foreach ($model->getColumnsList() as $dbName => $attribute) {
		    if ($colName == $dbName) {
                $col = $this->_createCol($dbName, $this->_modelSpec->getField($dbName));
                return $col;
		    }
	    }

        foreach ($model->getDependentList() as $dependatConfig) {
            if ($colName == $dependatConfig['table_name']) {
		        if ($colConfig = $this->_modelSpec->getField($dependatConfig['table_name'])) {
                    $col = $this->_createDependantColumn($colConfig, $dependantConfig);
                    return $col;
		        } else {
		            return false;
		        }
            }
		}


		if (!method_exists($model, 'getFileObjects')) {
		    return false;
		}

        foreach($model->getFileObjects() as $_fileCol) {
            if ($colName == $_fileCol) {
                if ($colConfig = $this->_modelSpec->getField($_fileCol)) {
                    return $this->_createFileColumn($colConfig, $_fileCol);
                } else {
                    return false;
                }
            }
        }

        return false;
	}


	public function isParentDependantScreen() {
	    return (!empty($this->_parentField));
	}

	public function isFilteredScreen() {
	    return (!empty($this->_filteredField));
	}


	public function getFilteredCondition($pkValue) {
	    return $this->_filteredField . "='" . $pkValue . "'";
	}

	public function getFilteredField() {
	    return $this->_filteredField;
	}

	public function getParentField() {
	    return $this->_parentField;
	}


	public function hasFieldOptions() {
		return ($this->_config->exists("fields->options"));
	}

	/**
	 * Devuelve un array de objetos FieldOption (opciones por campo), a partir de las columnas de tipo Option del ColWrapper
	 */
	public function getScreenFieldsOptionsConfig() {

		$parent = $this->_visibleColumnWrapper->getOptionColumn()->getKlearConfig();
		return $this->_getItemFieldsOptionsConfig('screen',$parent);
	}

	public function getDialogsFieldsOptionsConfig() {

		$parent = $this->_visibleColumnWrapper->getOptionColumn()->getKlearConfig();
		return $this->_getItemFieldsOptionsConfig('dialog',$parent);
	}


	public function getScreensGeneralOptionsConfig() {

	    if ( (!$this->_config->exists("options")) || ($this->_config->getRaw()->options == '') ) {
	        return array();
	    }

		$parent = new Klear_Model_KConfigParser();
		$parent->setConfig($this->_config->getRaw()->options);
		return $this->_getItemFieldsOptionsConfig('screen',$parent);
	}

	public function getDialogsGeneralOptionsConfig() {
	    if ( (!$this->_config->exists("options")) || ($this->_config->getRaw()->options == '') ) {
	        return array();
	    }

		$parent = new Klear_Model_KConfigParser();
		$parent->setConfig($this->_config->getRaw()->options);
		return $this->_getItemFieldsOptionsConfig('dialog',$parent);
	}

	public function getPaginationConfig() {
  		if (!$this->_config->exists("pagination")) {
  		    return false;
  		}

	    $pagination = new Klear_Model_KConfigParser();
		$pagination->setConfig($this->_config->getRaw()->pagination);
		return $pagination;
	}

	public function _getItemFieldsOptionsConfig($type,$parent) {

		$retArray = array();

		switch($type) {
			case 'dialog':
				$property = 'dialogs';
			break;
			case 'screen':
				$property = 'screens';
			break;
			default:
				Throw new Zend_Exception("Undefined Option Type");
			break;
		}

		$optionColumn = $this->_visibleColumnWrapper->getOptionColumn();

		$_items = $parent->getProperty($property,false);

		if (!$_items) {
		    return array();
		}

		foreach ($_items  as $_item=> $_enabled) {
			if (!(bool)$_enabled) continue;
			$retArray[] = $_item;

		}
		return $retArray;

	}


	/**
	 * gateway hacia modelo específico, para devolver el nombre de la PK
	 */
	public function getPK() {
		return $this->_modelSpec->getInstance()->getPrimaryKeyName();
	}

	/**
	 * gateway hacia modelo específico, para devolver la instancia del objeto "vacío"
	 */
	public function getObjectInstance() {
		return $this->_modelSpec->getInstance();
	}



	public function getType() {
		return $this->_type;
	}


}