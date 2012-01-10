<?php

/**
 * Clase que lee la configuración de fichero para este módulo y resuelve la ruta
* @author jabi
*
*/
class KlearMatrix_Model_MainConfig {
	
	const module = 'klearMatrix';
	
	protected $_defaultScreen;
	
	protected $_controller;
    protected $_action = 'index';
    	
	static public function getModuleName() {
	    return self::module;
	}
	
	
	public function setConfig(Zend_Config $config) {
		
		// TO-DO COntrol de errores, configuración mal seteada

		if (isset($config->main->defaultScreen)) {
			$this->_defaultScreen = $config->main->defaultScreen;
		} else {
			//TO-DO ¿Qué hacer cuando no hay una screen definida por defecto?
		}

		
		$this->_selectedConfig = $config->screens->{$this->_defaultScreen};
		$this->_parseSelectedConfig();
		return $this;
	}
	
	
	protected function _parseSelectedConfig() {
	    
	    $this->_controller = $this->_selectedConfig->controller;
	    
	    if (isset($this->_selectedConfig->action)) {
	        $this->_action = $this->_selectedConfig->action;
	    }
	}
	
	
	public function getActionName(){
	    return $this->_action;
	}
	
	
	public function getControllerName() {
		return $this->_controller;
	}
	
	
	
}