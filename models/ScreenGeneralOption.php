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

		$this->_title = $this->_config->getProperty("title",false);
		
		$this->_class = $this->_config->getProperty("class",false);
		$this->_label = (bool)$this->_config->getProperty("label",false);
		$this->_multiInstance = (bool)$this->_config->getProperty("multiInstance",false);
	}
	
	
	
	public function getTitle() {
		if (null !== $this->_title) {
			return $this->_title;
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
