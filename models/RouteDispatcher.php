<?php

/**
 * Clase que devuelve la ruta al forward de _dispatch en base a la configuración a los parámetros de request
* @author jabi
*
*/
class KlearMatrix_Model_RouteDispatcher {
	
	
	const module = 'klearMatrix';
	
	/**
	 * @var Klear_Matrix_Screen
	 */
	protected $_screen;
	
	/**
	 * @var string
	 */
	protected $_screenName;
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
	
	public function getConfig() {
		return $this->_config;
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
	
	
	public function getCurrentScreen() {
		if (null === $this->_screen) {

			$this->_screen = new KlearMatrix_Model_Screen();
			$this->_screen->setRouteDispatcher($this);
			$this->_screen->setScreenName($this->_screenName);
			$this->_screen->setConfig($this->_selectedConfig);
			
		}
				
		return $this->_screen;
	}
	
	
	protected function _resolveCurrentScreen() {
		
		if ($this->_screenName == null) {
			$this->_screenName = $this->_config->getDefaultScreen();
		}
		return $this;
	}
	
	public function _resolveCurrentConfig() {
		if ($this->_selectedConfig == null) {
			$this->_selectedConfig = $this->_config->getScreenConfig($this->_screenName);
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
			->_resolveCurrentProperty("action", false);
		
	
	}
	
	
}