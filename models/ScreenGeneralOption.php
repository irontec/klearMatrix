<?php

/**
* 
* @author jabi
*
*/
class KlearMatrix_Model_ScreenGeneralOption {
	
	protected $_config;
	protected $_screen;
	protected $_class;
	protected $_title;
	protected $_title_i18n = array();
	
	// La opción es abrible en multi-instancia? 
	protected $_multiInstance = false;
	
	// Define si es necesario seleccionar campos para ejecutar esta opción general
	protected $_fieldRelated = false;
	
	protected $_noLabel = true;
	
	public function setScreenName($screen) {
		$this->_screen = $screen;
	}
	
	public function setConfig(Zend_Config $config) {
		
		$this->_config = new Klear_Model_KConfigParser;
		$this->_config->setConfig($config);

		
		list($attrName,$value) = $this->_config->getPropertyML("title","title",false);
		$this->$attrName = $value;
		
		$this->_class = $this->_config->getProperty("class",false);
		$this->_label = (bool)$this->_config->getProperty("label",false);
		$this->_multiInstance = (bool)$this->_config->getProperty("multiInstance",false);
	}
	
	
	protected function _getProperty($attribute) {
		// TO-DO: recoger el idioma? ZendRegistry?
		$lang = 'es';
		$attributeName = '_' . $attribute . '_i18n';
	
		if (isset($this->{$attributeName}[$lang])) {
	
			return $this->{$attributeName}[$lang];
		}
		$attributeName = '_' . $attribute;
		return $this->{$attributeName};
	}
	
	
	public function getTitle() {
		if ($title = $this->_getProperty("title")) {
			return $title;
		}
		
		// o_O pues eso.... MAL!
		return 'error';
	
	}
		
	public function toArray() {
		return array(
			'icon'=>$this->_class,
			'type'=>'screen',
			'screen'=>$this->_screen,
			'title'=>$this->getTitle(),
			'label'=>$this->_label,
			'multiInstance'=>$this->_multiInstance
		);
	}
	
	
	
}
