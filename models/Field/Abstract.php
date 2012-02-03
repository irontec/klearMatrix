<?php


abstract class KlearMatrix_Model_Field_Abstract {

	protected $_column;
	
	public function setColumn($column) {
		$this->_column = $column;
		return $this;
	}
	
	/**
	 * Dejar este método vacio, se invocara siempre que se genera desde Column 
	 */
	public function init() {
	    
	    return $this;
	}
	
	public function toArray() {
	  return false;	    
	}
	
	/*
	 * Filtra (y adecua) el valor del campo antes del setter
	 *  
	 */
	public function filterValue($value,$original) {
	    return $value;
	}
	
	/*
	 * Prepara el valor de un campo, después del getter
	 */
	public function prepareValue($value) {
	    return $value;
	}
	
	public function getExtraJavascript() {
	    return false;
	}
	
	public function getExtraCss() {
	    return false;
	}
	
	
}