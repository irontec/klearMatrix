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

	protected $_ordered = false;
	protected $_orderedType = 'asc';

	protected $_fieldConfig;

	protected $_config;

	protected $_isOption;
	protected $_defaultOption;
	protected $_type ='text';

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



		if ($this->isOption()) {
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


		$this->_type = $this->_config->getProperty("type",false);
		if (empty($this->_type)) {
	    	$this->_type = 'text';
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

	    switch ($this->_routeDispatcher->getControllerName()) {
	        case "new":
	            return false;
	        default:
	            return $this->_isReadonly;
	    }

	}

	public function setAsOrdered() {
	    $this->_ordered = true;
	}

	public function setOrderedType($_orderType) {
	    $this->_orderedType = $_orderType;
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





    public function getGetterName($object)
    {
        if ($this->isOption()) return false;

        if (method_exists($this->_fieldConfig, 'getCustomGetterName')) {
            return $this->_fieldConfig->getCustomGetterName();
        }
        
        if ($this->isDependant()) {
            return 'get' . $this->getDbName();
        } else {
            return 'get' . $object->columnNameToVar($this->getDbName());
        }

    }

    public function getSetterName($object)
    {
        if ($this->isOption()) return false;

        if (method_exists($this->_fieldConfig, 'getCustomSetterName')) {
            return $this->_fieldConfig->getCustomSetterName();
        }

        if ($this->isDependant()) {
            return 'set' . $this->getDbName();
        } else {
            return 'set' . $object->columnNameToVar($this->getDbName());
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

		if ($this->_ordered) {
		    $ret['order'] = $this->_orderedType;
		}


		if ($this->_fieldConfig) {
		   
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
