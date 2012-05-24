<?php

/**
 *
* @author jabi
*
*/

class KlearMatrix_Model_Field_File extends KlearMatrix_Model_Field_Abstract
{

	/**
	 * @var KlearMatrix_Model_Field_Password_Abstract
	 */
	protected $_adapter;

	public function init()
	{
	    parent::init();
	    $sourceConfig = $this->_config->getRaw()->source;
	    $adapterClassName = "KlearMatrix_Model_Field_File_" . ucfirst($sourceConfig->data);

	    $this->_adapter = new $adapterClassName;
	    $this->_adapter
	        ->setConfig($sourceConfig)
	        ->init();

	}

	/*
	 * Prepara el valor de un campo, después del getter
	 */
	public function prepareValue($value, $model)
	{
	    // Debemos devolver un array con size / mime / name del fichero
	    // value nos ha devuelto las specs del campo file

	    return array(
	            'size' => $model->{'get' . $value['sizeName']}(),
	            'mime' => $model->{'get' . $value['mimeName']}(),
	            'name' => $model->{'get' . $value['baseNameName']}()
	    );

	}

	public function getCustomOrderField($model) {

	    $fields = $model->{$this->getCustomGetterName()}();
	    return $model->varNameToColumn($fields['baseNameName']);
	}

	public function getCustomSearchField($model) {
	    
	    $fields = $model->{$this->getCustomGetterName($model)}();
	    
	    return $model->varNameToColumn($fields['baseNameName']);

	}

	public function getConfig() {
	  return $this->_adapter->getConfig();
	}

	public function getCustomGetterName($model)
	{
	    return 'get' . $this->_column->getDbName() . 'Specs';
	}

	public function getCustomSetterName($model)
	{
	    return 'put' . $this->_column->getDbName();
	}

	public function getFetchMethod($dbName)
	{
	    return $this->_adapter->getFetchMethod($dbName);
	}


	public function filterValue($value,$original)
	{
	    if (empty($value)) return false;


	    $tempFSystemNS = new Zend_Session_Namespace('File_Controller');
	    if (isset($tempFSystemNS->{$value})) {
	        $tempFile = $tempFSystemNS->{$value};
	        // Invocamos put[FILEIDEN] (realpath y basename)
	        return $tempFile;
	    }

	    return false;
	}


	public function getExtraJavascript()
	{
	    return $this->_adapter->getExtraJavascript();
	}

	public function getExtraCss() {
	    return $this->_adapter->getExtraCss();
	}


}