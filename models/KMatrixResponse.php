<?php
class KlearMatrix_Model_KMatrixResponse {
	
	protected $_columnWrapper;
	protected $_results;
	protected $_pk;
	
	public function setColumnWraper(KlearMatrix_Model_ColumnWrapper $columnWrapper) {
		$this->_columnWrapper = $columnWrapper;
	}
	
	public function setResults(array $results) {
		$this->_results = $results;
	}
	
	public function setPK($pk) {
		$this->_pk = $pk;
	}
	
	public function toJson() {
		return array(
					"columns" => $this->_columnWrapper->toArray(),
					"values" => $this->_results,
					"pk" => $this->_pk
				
			);
		
	}
	
}