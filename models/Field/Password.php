<?php

/**
 * Clase que devuelve la ruta al forward de _dispatch en base a la configuración a los parámetros de request
* @author jabi
*
*/

class KlearMatrix_Model_Field_Password extends KlearMatrix_Model_Field_Abstract
{

	/**
	 * @var KlearMatrix_Model_Field_Password_Abstract
	 */
	protected $_adapter;

	public function filterValue($value,$original)
	{
	    $this->_adapter->setClearValue($value);

	    return $this->_adapter->cryptValue();
	}

	public function init()
	{
	    parent::init();
	    $adapterClassName = "KlearMatrix_Model_Field_Password_" . ucfirst($this->_config->getProperty("adapter"));

	    $this->_adapter = new $adapterClassName;
	}

	/*
	 * Prepara el valor de un campo, después del getter
	 */
	public function prepareValue($value, $model)
	{
	    return "********";
	}

	public function getExtraJavascript()
	{
	    return false;
	}

	public function getExtraCss()
	{
	    return false;
	}

}

//EOF