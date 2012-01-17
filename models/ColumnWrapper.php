<?php

class KlearMatrix_Model_ColumnWrapper implements Iterator {
	
	public $_cols = array();
	protected $_position;
	protected $_columnsListKeys = array();

	protected $_types = array();
	
	public function addCol($col) {
		$this->_cols[] = $col;
		$this->_types[$col->getType()] = true;
	}
	
	public function toArray() {
		$retArray = array();
		foreach ($this->_cols as $col) {
			$retArray[] = $col->toArray();
		}
		
		return $retArray;
	}
	
	public function getTypesTemplateArray($path ,$prefix) {
	    
	    $tmpls = array();
	    foreach($this->_types as $type => $foo) {
	        $tmpls[ $prefix . $type] = $path . $type;
	    }
	    
	    return $tmpls;   
	    
	}
	
	public function __construct() {
		$this->_position = 0;
	}
	
	public function rewind() {
		$this->_position = 0;
	}
	
	public function current() {
		return $this->_cols[$this->_position];
	}
	
	public function key() {
		return $this->_position;
	}
	
	public function next() {
		++$this->_position;
	}
	
	public function valid() {
		return isset($this->_cols[$this->_position]);
	
	}
	
}