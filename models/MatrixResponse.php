<?php
class KlearMatrix_Model_MatrixResponse {

	protected $_columnWrapper;
	protected $_results;
	protected $_fieldOptionsWrapper = false;
	protected $_generalOptionsWrapper = false;

	protected $_paginator = false;

	protected $_title;

	/**
	 * Enter description here ...
	 * @var KlearMatrix_Model_ResponseItem;
	 */
	protected $_item;

	protected $_pk;

	public function setColumnWraper(KlearMatrix_Model_ColumnWrapper $columnWrapper) {
		$this->_columnWrapper = $columnWrapper;
		return $this;
	}

	public function setResults($results) {
		$this->_results = $results;
		return $this;
	}

	public function setPK($pk) {
		$this->_pk = $pk;
		return $this;
	}

	public function setTitle($title) {
	    $this->_title = $title;
	    return $this;
	}

	/**
	 * Opciones "generales" de pantalla
	 * @param KlearMatrix_Model_ScreenOptionsWrapper $screenOptsWrapper
	 */
	public function setGeneralOptions(KlearMatrix_Model_GeneralOptionsWrapper $generalOptsWrapper) {
		$this->_generalOptionsWrapper = $generalOptsWrapper;
		return $this;
	}

	/**
	 * Opciones por fila
	 * @param KlearMatrix_Model_FieldOptionsWrapper $fieldOptsWrapper
	 */
	public function setFieldOptions(KlearMatrix_Model_FieldOptionsWrapper $fieldOptsWrapper) {
		$this->_fieldOptionsWrapper = $fieldOptsWrapper;
		return $this;
	}


	public function setResponseItem(KlearMatrix_Model_ResponseItem $item) {
	    $this->_item = $item;
	    return $this;
	}

	public function setPaginator(Zend_Paginator $paginator) {
	    $this->_paginator = $paginator;
	}

	/**
	 * Si los resultados (de data) son objetos, los pasa a array (para JSON)
	 * Se eliminan los campos no presentes en el column-wrapper
	 * Se pasa el controlador llamante(edit|new|delete|list), por si implicara cambio en funcionalidad por columna
	 */
	public function fixResults(KlearMatrix_Model_ResponseItem $screen) {

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

	public function toArray() {

		$ret = array();
		$ret['title'] = $this->_title;
		$ret['columns'] = $this->_columnWrapper->toArray();
		$ret['values'] = $this->_results;
		$ret['pk'] = $this->_pk;

		if (false !== $this->_fieldOptionsWrapper) {
			$ret['fieldOptions'] = $this->_fieldOptionsWrapper->toArray();
		}

		if (false !== $this->_generalOptionsWrapper) {
			$ret['generalOptions'] = $this->_generalOptionsWrapper->toArray();
		}

		if (false !== $this->_paginator) {
		    $res['paginator'] = (array)$this->_paginator->getPages();
		}

		$ret[$this->_item->getType()] = $this->_item->getItemName();

		return $ret;

	}

}