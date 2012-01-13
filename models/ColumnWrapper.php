<?php

class KlearMatrix_Model_ColumnWrapper {
	
	public $_cols = array();
	
	public function addCol($col) {
		$this->_cols[] = $col;
	}
	
	public function toArray() {
		$retArray = array();
		foreach ($this->_cols as $col) {
			$retArray[] = $col->toArray();
		}
		
		return $retArray;
		
	}
	
}