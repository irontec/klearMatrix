<?php

/**
 * Clase que devuelve la ruta al forward de _dispatch en base a la configuración a los parámetros de request
* @author jabi
*
*/
class KlearMatrix_Model_ScreenFieldOption {

	protected $_config;
	protected $_screen;
	protected $_class;
	protected $_title;

	protected $_default = false;

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
	}


	public function getTitle() {
		if (null !== $this->_title) {
			return $this->_title;
		}

		// o_O pues eso.... MAL!
		return 'unnamed screen';

	}

	public function setAsDefault() {
	    $this->_default = true;
	}

    public function isDefault() {
	    return true === $this->_default;
	}

	public function toArray() {
		$ret = array(
			'icon'=>$this->_class,
			'type'=>'screen',
			'screen'=>$this->_screen,
			'title'=>$this->getTitle(),
			'label'=>$this->_label
		);

		if ($this->isDefault()) {
		    $ret['defaultOption'] = true;
		}

		return $ret;
	}



}
