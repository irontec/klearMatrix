<?php

class KlearMatrix_Model_ColumnWrapper implements Iterator {
	
	public $_cols = array();
	protected $_position;
	protected $_columnsListKeys = array();

	protected $_optionColumnIdx = false;
	protected $_defaultColumnIdx = false;
	protected $_types = array();
	
	// Indexamos los nombres de db de columnas por rendimiento
	protected $_dbNameIndex = array();
	
	public function addCol(KlearMatrix_Model_Column $col) {
		$this->_cols[] = $col;
		$currentIdx = sizeof($this->_cols) - 1;
		
		// Estamos dando por hecho, que hay sólo una columna de opciones por listado.
		if ($col->isOption()) {
			$this->_optionColumnsIdx = $currentIdx;			
		} else {
			$this->_types[$col->getType()] = $currentIdx;
			$this->_dbNameIndex[$col->getDbName()] = $currentIdx;
		}
		
		
		if ($col->isDefault()) {
			$this->_defaultColumnIdx = sizeof($this->_cols) - 1;
		}
	}
	
	public function toArray() {
		$retArray = array();
		foreach ($this->_cols as $col) {
			$retArray[] = $col->toArray();
		}
		
		return $retArray;
	}
	
	public function getColFromDbName($field) {
	    if (!isset($this->_dbNameIndex[$field])) {
	        return false;
	    }
	    
	    return $this->_cols[$this->_dbNameIndex[$field]];
	    
	}
	
	public function getTypesTemplateArray($path ,$prefix) {
	    
		
	    $tmpls = array();
	    foreach($this->_types as $type => $foo) {
	    	if ($type == '') continue; // FIX ME! por que hay types vacíos?
	        $tmpls[ $prefix . $type] = $path . $type;
	    }
	    
	    return $tmpls;   
	    
	}
	
	public function getDefaultCol() {
		if (false === $this->_defaultColumnIdx) {
			return $this->_cols[0];
		}
		
		return $this->_cols[$this->_defaultColumnIdx];
	}
	
	public function resetWrapper() {
		$this->_cols = array();
		$this->_types = array();
		$this->_defaultColumnIdx = false;
		$this->_optionColumnIdx = false;
		return $this;
	}
	
	public function getOptionColumn() {
		
		if (false === $this->_optionColumnsIdx) {
			return false;
		}
		
		return $this->_cols[$this->_optionColumnsIdx];

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