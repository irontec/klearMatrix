<?php

/**
 * Clase que devuelve la ruta al forward de _dispatch en base a la configuración a los parámetros de request
* @author jabi
*
*/
class KlearMatrix_Model_ModelSpecification {
	
	protected $_config;
	protected $_class;
	
	public function setConfig(Zend_Config $config) {
		$this->_config = new Klear_Model_KConfigParser;
		$this->_config->setConfig($config);
		
		$this->_class = $this->_config->getProperty("class",true);
		$this->_instance = new $this->_class;
	}
	
	public function getInstance() {
		return $this->_instance;
	}
	
	public function getField($fName) {
		if ($this->_config->exists("fields->" . $fName)) {
			return $this->_config->getRaw()->fields->{$fName};			
		}
		return false;
		
	}
	
}
