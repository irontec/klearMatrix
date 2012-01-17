<?php

/**
 * Clase que devuelve la ruta al forward de _dispatch en base a la configuración a los parámetros de request
* @author jabi
*
*/
class KlearMatrix_Model_RouteDispatcher {
	
	
	const module = 'klearMatrix';
	
	/**
	 * @var KlearMatrix_Model_Screen
	 */
	protected $_screen;
	
	/**
	 * @var KlearMatrix_Model_Dialog
	 */
	protected $_dialog;
	
	/**
	 * @var string
	 */
	protected $_screenName;
	protected $_dialogName;
	protected $_selectedConfig;
	
	
	/**
	 * @var string
	 * Que tipo de request = dialog | *screen
	 */
	protected $_typeName = 'screen';
	
	
	protected $_controller;
	protected $_action = 'index';
	protected $_mapper;
	
	
	protected $_params = array();
	
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
				case 'screen':
				case 'dialog':
				case 'type':
					$attrName = "_" . $param . "Name";
					$this->{$attrName} = $value;
				break;
				default:
					$this->_params[$param] = $value;
				break;
				
			}
			
		}
		
	}
	
	public function getParam($param) {
		if (isset($this->_params[$param])) {
			return $this->_params[$param];
		}
		
		throw new Zend_Exception('Parámetro ['+ $param+'] no encontrado.');
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
	
	
	public function getCurrentDialog() {
	
		if (null === $this->_dialog) {
	
			$this->_dialog = new KlearMatrix_Model_Dialog();
			$this->_dialog->setRouteDispatcher($this);
			$this->_dialog->setDialogName($this->_dialogName);
			$this->_dialog->setConfig($this->_selectedConfig);
		}
		return $this->_dialog;
	}
	
	
	
	/**
	 * @return Klear_Matrix_Screen
	 */
	public function getCurrentItem() {
		switch($this->_typeName) {
			case "dialog":
				return $this->getCurrentDialog();
			default:
				return $this->getCurrentScreen();
		}
	}
	
	
	protected function _resolveCurrentItem() {
		
		switch($this->_typeName) {
			case "dialog":
				return $this->_resolveCurrentItemDialog();
			default:
				return $this->_resolveCurrentItemScreen();
		}
			
	}
	
	protected function _resolveCurrentItemScreen() {
		if ($this->_screenName == null) {
			$this->_screenName = $this->_config->getDefaultScreen();
		}
		return $this;		
	}
	
	protected function _resolveCurrentItemDialog() {
		
		if ($this->_dialogName == null) {
			$this->_dialogName = $this->_config->getDefaultDialog();
		}
		return $this;
	}
	
	public function _resolveCurrentConfig() {
		
		// Aquí resolvemos a que métodos de MainConfig llamar:
		// getScreenConfig | getDialogConfig
		// a partir al atributo de entidad que corresponda según el type
		// _screenName | _dialogName 
		
		$configGetter = "get" . ucfirst($this->_typeName) . "Config";
		$attrName = "_" . $this->_typeName . "Name";
		
		if ($this->_selectedConfig == null) {
			$this->_selectedConfig = $this->_config->{$configGetter}($this->{$attrName});
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
			->_resolveCurrentItem()
			->_resolveCurrentConfig()
			->_resolveCurrentProperty("controller", true)
			->_resolveCurrentProperty("action", false);
	
	}
	
	
}