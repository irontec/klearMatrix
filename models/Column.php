<?php

/**
 * Clase que devuelve la ruta al forward de _dispatch en base a la configuración a los parámetros de request
* @author jabi
*
*/
class KlearMatrix_Model_Column {

	protected $_dbName;
	protected $_publicName;
	protected $_publicName_i18n = array();
	protected $_isDefault = false;
	
	protected $_config;
	
	protected $_isOption;
	
	public function setDbName($name) {
		$this->_dbName = $name;
	}
	
	public function setPublicName($name) {
		$this->_publicName = $name;
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

		list($attrName,$value) = $this->_config->getPropertyML("title","publicName",false);
		$this->$attrName = $value;
		
		$default = $this->_config->getProperty("default",false);
		$this->_isDefault = (bool)$default;
		
	}
	
	protected function _getProperty($attribute) {
		$lang = 'es';
		$attributeName = '_' . $attribute . '_i18n';
	
		if (isset($this->{$attributeName}[$lang])) {
	
			return $this->{$attributeName}[$lang];
		}
		$attributeName = '_' . $attribute;
		return $this->{$attributeName};
	}
	
	
	public function getPublicName() {
		if ($pubName = $this->_getProperty("publicName")) {
			return $pubName;
		}
		
		return $this->_dbName;
		
	}
	
	public function getDbName() {
		return $this->_dbName;
	}
	
	public function toArray() {
		$ret= array();
		
		$ret["id"] = $this->_dbName;
		$ret["name"] = $this->getPublicName();
		if ($this->_isDefault) {
			$ret['default'] = true;
		}
		
		return $ret;
	}
	
}