<?php

/**
 * Clase que devuelve la ruta al forward de _dispatch en base a la configuración a los parámetros de request
* @author jabi
*
*/
class KlearMatrix_Model_Column {

	protected $_attributeName;
	protected $_publicName;
	protected $_publicName_i18n = array();

	protected $_config;
	
	public function setAttributeName($name) {
		$this->_attributeName = $name;
	}
	
	public function setPublicName($name) {
		$this->_publicName = $name;
	}

	public function setConfig(Zend_Config $config) {

		$this->_config = new Klear_Model_KConfigParser;
		$this->_config->setConfig($config);

		list($attrName,$value) = $this->_config->getPropertyML("title","publicName",false);
		$this->$attrName = $value;
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
		
		return $this->_attributeName;
		
	}
	
	public function toArray() {
		return array(
					"id" => $this->_attributeName,
					"name" => $this->getPublicName()
				);		
	}
	
}