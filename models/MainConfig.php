<?php

/**
 * Clase que lee la configuración de fichero para este módulo y resuelve la ruta
* @author jabi
*
*/
class KlearMatrix_Model_MainConfig {
	
	const module = 'klearMatrix';
	
	protected $_config;
	protected $_configPath;
    
	static public function getModuleName() {
	    return self::module;
	}
	
	
	public function setConfig(Zend_Config $config) {
		
		$this->_config = new Klear_Model_KConfigParser;
		$this->_config->setConfig($config);
		return $this;
	}
	
	/**
	 * La configuración debe recibir la ruta de ficheros de configuración, para cargar configuraciones auxiliares de cada módulo
	 * @param string $path
	 */
	public function setConfigPath($path) {
		$this->_configPath = $path;
		return $this;
	}

	public function getConfigPath() {
		return $this->_configPath;
	}

	public function getDefaultScreen() {
		
		if ($this->_config->exists("main->defaultScreen") )  {

			$this->_defaultScreen = $this->_config->getRaw()->main->defaultScreen;
			
		} else {
			
			// Si no hay una defaultScreen, devolvemos la primera definida en el fichero de configuración.
			if ($this->_config->exists("screens")) {
				foreach ($this->_config->getRaw()->screens as $screenName => $_data) {
					$this->_defaultScreen = $screenName;
					break;
				}
				
			} else {
				Throw new Zend_Exception("Default screen not found");				
			}
		}

		return $this->_defaultScreen;
	}
	
	
	public function getScreenConfig($screen)
	{
		
		if (!$this->_config->exists("screens->" . $screen)) {
			Throw new Zend_Exception("Configuration for selected screen not found");
		}
		
		return $this->_config->getRaw()->screens->{$screen};
		
	}
	
	
	
	protected function _parseSelectedConfig() {
	    
	    $this->_controller = $this->_selectedConfig->controller;
	    
	    $propertiesToMap = array("action","mapper");

	    foreach($propertiesToMap as $prop) {
	    	if (isset($this->_selectedConfig->{$prop})) {
	    		$propName = '_' . $prop;
	    		$this->{$propName} = $this->_selectedConfig->{$prop};
	    		
	    	}
	    }
	}
	
	
	/**
	 * @return KlearMatrix_Model_RouteDispatcher
	 */
	public function buildRouterConfig() {
		$router = new KlearMatrix_Model_RouteDispatcher();
		$router->setConfig($this);
		return $router;		
	}

	
	
	
}