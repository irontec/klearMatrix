<?php
class KlearMatrix_Model_KMatrixResponse {
	
	protected $_columnWrapper;
	protected $_results;
	protected $_fieldOptions;
	protected $_pk;
	
	public function setColumnWraper(KlearMatrix_Model_ColumnWrapper $columnWrapper) {
		$this->_columnWrapper = $columnWrapper;
	}
	
	public function setResults($results) {
		
		$this->_results = $results;
	}
	
	public function setPK($pk) {
		$this->_pk = $pk;
	}
	
	public function setFieldOptions(array $options) {
		$this->_fieldOptions = $options;
	}
	
	
	/**
	 * Si los resultados son objetos, los pasa a array (para JSON)
	 * Se eliminan los campos no presentes en el column-wrapper 
	 */
	public function fixResults(KlearMatrix_Model_Screen $screen) {
		
		$colIndexes = array();
		foreach($screen->getVisibleColumnWrapper() as $column) {
			if ($column->isOption()) continue;
			$colIndexes[] = $column->getDbName();
		}
		
		$colIndexes[] = $screen->getPK();
		
		
		if (!is_array($this->_results)) $this->_results = array($this->_results);

		$_newResults = array();
		
		foreach($this->_results as $result) {

			$_newResult = array();
			
			if ( (is_object($result)) && (get_class($result) == $screen->getModelName()) ) {
				
				foreach($colIndexes as $dbName) {
					$getterFieldName = "get" . $result->columnNameToVar($dbName);
					$_newResult[$dbName] = $result->{$getterFieldName}(); 
				}
				
				$_newResults[] = $_newResult;
			}
			
		}
		
		$this->_results = $_newResults;		
		
	}
	
	public function toJson() {
		
		return array(
					"columns" => $this->_columnWrapper->toArray(),
					"values" => $this->_results,
					"pk" => $this->_pk,
					"fieldOptions" => $this->_fieldOptions				
			);
		
	}
	
}