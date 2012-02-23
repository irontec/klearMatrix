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

	protected $_filteredField;

	protected $_modelSpec;

	protected $_visibleColumnWrapper;

	protected $_options;

	public function setConfig(Zend_Config $config) {

		$this->_config = new Klear_Model_KConfigParser;
		$this->_config->setConfig($config);

		$this->_mapper = $this->_config->getProperty("mapper",true);
		$this->_modelFile = $this->_config->getProperty("modelFile",true);

		$this->_filteredField = $this->_config->getProperty("filterField",false);
		
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


	/**
	 * El método filtrará las columnas del modelo con el fichero de configuración de modelo y la whitelist/blacklist de la configuración
	 *
	 * return KlearMatrix_Model_ColumnWrapper $_visibleColumnWrapper listado de columnas que devuelve el modelo
	 */
	public function getVisibleColumnWrapper($ignoreBlackList = false) {
		if (isset($this->_visibleColumnWrapper)) return $this->_visibleColumnWrapper;

		$obj = $this->_modelSpec->getInstance();

		$this->_visibleColumnWrapper =  new KlearMatrix_Model_ColumnWrapper;

		//Inicializamos blackList (los campos que no se mostrarán)
		$blacklist = array();

		$pk = $obj->getPrimaryKeyName();
		
		
		/*
		 * Si el modelo tiene el método getFileObjects, y éstos están definidos en la configuración
		*/
		if (method_exists($obj, 'getFileObjects')) {
		    
		    $fileObjects = $obj->getFileObjects();
		    foreach($fileObjects as $_fileCol) {
		
		        if ($colConfig = $this->_modelSpec->getField($_fileCol)) {
		
		            $col = new KlearMatrix_Model_Column;
		            $col->setDbName($_fileCol);
		            $col->setConfig($colConfig);
		            $col->markAsFile();
		            $col->setRouteDispatcher($this->_routeDispatcher);
		            $this->_visibleColumnWrapper->addCol($col);
		            
		            $involvedFields = $col->getFieldConfig()->getInvolvedFields();
		            
		            foreach ($involvedFields as $_fld) {
		                $blacklist[$_fld] = true;
		            }
		        }
		
		    }
		}
		
		

		// La primary Key estará por defecto en la blackList, a excepción de encontrarse en la whitelist
		if  ($this->_config->exists("fields->whitelist->" . $pk)) {
			// La Pk se mostrará si está en la whitelist
			// Something to do?

		} else {
			$blacklist[$pk] = true;
		}

		// Le pregunto al fichero de configuración por los campos en la blackList - no deben salir -
		if ($this->_config->exists("fields->blacklist")) {
			if (($_blacklistConfig = $this->_config->getRaw()->fields->blacklist) !== '') {

				foreach($_blacklistConfig as $field => $value) {
					if ((bool)$value) {
						$blacklist[$field] = true;
					}
				}
			}
		}

		if ($this->isFilteredScreen()) {
		    $blacklist[$this->_filteredField] = true;
		}


		$multiLangFields = $obj->getMultiLangColumnsList();

		
		if ( (is_array($availableLangsPerModel = $obj->getAvailableLangs())) && (sizeof($availableLangsPerModel)>0) ) {
		    $this->_visibleColumnWrapper->setLangs($availableLangsPerModel);
		}

		foreach($multiLangFields as $dbName=>$columnName) {
		    foreach($availableLangsPerModel as $langIden) {

		        $blacklist[$dbName . '_'. $langIden] = true;

		    }



		}

		foreach($obj->getColumnsList() as $dbName => $attribute) {
		    if ( (!$ignoreBlackList) && (isset($blacklist[$dbName])) ) continue;

			$col = new KlearMatrix_Model_Column;
			$col->setDbName($dbName);

			if ($colConfig = $this->_modelSpec->getField($dbName)) {
				$col->setConfig($colConfig);
			}

			if (isset($multiLangFields[$dbName])) {
			    $col->markAsMultilang();
			}

			// A cada columna, le pasamos el "dispatcherador de ruta, ya que puede hacer variar la funcionalidad
			$col->setRouteDispatcher($this->_routeDispatcher);
			$this->_visibleColumnWrapper->addCol($col);
		}


	   /**
	    *  Buscamos las tablas dependientes, por si estuvieran *Explicitamente* declaradas en el fichero de modelo
	    */
		foreach ($obj->getDependentList() as $dependatConfig) {
		    if (isset($blacklist[$dependatConfig['table_name']])) continue;
		    
		    if ($colConfig = $this->_modelSpec->getField($dependatConfig['table_name'])) {
		        
		        $col = new KlearMatrix_Model_Column;
		        $col->setDbName($dependatConfig['table_name']);
		        $col->setConfig($colConfig);
		        $col->markAsDependant();
		        $col->setRouteDispatcher($this->_routeDispatcher);
		        $this->_visibleColumnWrapper->addCol($col);
		    }
		}
		
	

		if ($this->hasFieldOptions()) {

		    $col = new KlearMatrix_Model_Column;
			$col->markAsOption();
			$col->setdbName("_fieldOptions");
			$col->setConfig($this->_config->getRaw()->fields->options);
			$this->_visibleColumnWrapper->addCol($col);
		}

		return $this->_visibleColumnWrapper;

	}


	public function isFilteredScreen() {
	    return (!empty($this->_filteredField));
	}

	public function getFilteredCondition($pkValue) {
	    return $this->_filteredField . "='" . $pkValue . "'";
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