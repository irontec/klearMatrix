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
	
}