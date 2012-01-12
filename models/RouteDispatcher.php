<?php

/**
 * Clase que devuelve la ruta al forward de _dispatch en base a la configuración a los parámetros de request
* @author jabi
*
*/
class KlearMatrix_Model_RouteDispatcher {
	
	const module = 'klearMatrix';
	
	protected $_screen;
	protected $_selectedConfig;
	
	protected $_controller;
	protected $_action = 'index';
	protected $_mapper;
	
	/**
	 * @var KlearMatrix_Model_MainConfig
	 */
	protected $_config;
	
	
	static public function getModuleName() {
		return self::module;
	}
	
	public function setConfig(KlearMatrix_Model_MainConfig $config) {
		$this->_config = $config;
	}
	
	
	public function setParams(array $params) {
		foreach ($params as $param=>$value) {
			
			switch($param) {
				case 'alguno':
				break;
			}
			
		}
		
	}
	
	
	public function getActionName(){
		return $this->_action;
	}
	
	
	public function getControllerName() {
		return $this->_controller;
	}
	
	public function getMapperName() {
		return $this->_mapper;
	}
	
	
	protected function _resolveCurrentScreen() {
		
		if ($this->_screen == null) {
			$this->_screen = $this->_config->getDefaultScreen();
		}
		return $this;
	}
	
	public function _resolveCurrentConfig() {
		if ($this->_selectedConfig == null) {
			$this->_selectedConfig = $this->_config->getScreenConfig($this->_screen);
		}
		return $this;
	}
	
	public function _resolveCurrentproperty($name, $required) {

		if (!isset($this->_selectedConfig->{$name})) {
			if ($required) {
				Throw new Zend_Exception("Controller not in selected config");
			} else {
				
				return $this;
			}
		}
		
		$propName = '_' . $name;
		
		$this->{$propName} = $this->_selectedConfig->{$name};
		return $this;		
	}
	
	public function resolveDispatch() {
		
		$this
			->_resolveCurrentScreen()
			->_resolveCurrentConfig()
			->_resolveCurrentProperty("controller", true)
			->_resolveCurrentProperty("mapper", true)
			->_resolveCurrentProperty("action", false);
		
	
	}
	
	
}