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

	protected $_fieldConfig;

	protected $_config;

	protected $_isOption;
	protected $_defaultOption;
	protected $_type ='text';

	protected $_routeDispatcher;

	public function setDbName($name) {
		$this->_dbName = $name;
	}

	public function setPublicName($name) {
		$this->_publicName = $name;
	}

	public function setRouteDispatcher(KlearMatrix_Model_RouteDispatcher $routeDispatcher) {
	    $this->_routeDispatcher = $routeDispatcher;
	}

	public function markAsOption() {
		$this->_isOption = true;
	}

	public function isOption() {
		return $this->_isOption;
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

	}

	public function _loadConfigClass() {
        if ($this->isOption()) return $this;
        if (is_object($this->_fieldConfig)) return $this;

	    $fieldConfigClassName = 'KlearMatrix_Model_Field_' . ucfirst($this->_type);

		$this->_fieldConfig = new $fieldConfigClassName;
		$this->_fieldConfig
		            ->setColumn($this)
		            ->init();
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

    public function getDefaultOption() {
        if (!$this->isOption()) return false;
        return $this->_defaultOption;
    }

	public function toArray() {

	    $this->_loadConfigClass();

		$ret= array();

		$ret["id"] = $this->_dbName;
		$ret["name"] = $this->getPublicName();
		$ret["type"] = $this->_type;


		if ($this->isDefault()) {
			$ret['default'] = true;
		}

		if ($this->isReadonly()) {
			$ret['readonly'] = true;
		}

		if ( ($this->_fieldConfig) && ($config = $this->_fieldConfig->toArray()) ) {
		    $ret['config'] = $config;
		}

		return $ret;
	}

}
