<?php

/**
 * Clase que devuelve la ruta al forward de _dispatch en base a la configuración a los parámetros de request
* @author jabi
*
*/
class KlearMatrix_Model_DialogFieldOption {
	
	protected $_config;
	protected $_dialog;
	protected $_class;
	protected $_title;
	protected $_title_i18n = array();
	
	protected $_noLabel = true;
	
	public function setDialogName($dialog) {
		$this->_dialog = $dialog;
	}
	
	public function setConfig(Zend_Config $config) {
		
		$this->_config = new Klear_Model_KConfigParser;
		$this->_config->setConfig($config);

		
		list($attrName,$value) = $this->_config->getPropertyML("title","title",false);
		$this->$attrName = $value;
		
		$this->_class = $this->_config->getProperty("class",false);
		$this->_label = (bool)$this->_config->getProperty("labelOption",false);
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
			'class'=>$this->_class,
			'type'=>'dialog',
			'dialog'=>$this->_dialog,
			'title'=>$this->getTitle(),
			'label'=>$this->_label
		);
	}
	
	
	
}
