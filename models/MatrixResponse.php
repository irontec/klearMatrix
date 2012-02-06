<?php
class KlearMatrix_Model_MatrixResponse {

	protected $_columnWrapper;
	protected $_results;
	protected $_fieldOptionsWrapper = false;
	protected $_generalOptionsWrapper = false;

	protected $_paginator = false;

	protected $_parentIden = false;

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

	public function setParentIden($parentIden) {
	    $this->_parentIden = $parentIden;
	}

	/**
	 * Si los resultados (de data) son objetos, los pasa a array (para JSON)
	 * Se eliminan los campos no presentes en el column-wrapper
	 * Se pasa el controlador llamante(edit|new|delete|list), por si implicara cambio en funcionalidad por columna
	 * Gestionamos el multi-idioma de los campos multiidioma (getters con $lang seleccionado)
	 */
	public function fixResults(KlearMatrix_Model_ResponseItem $screen) {


		$primaryKeyName = $screen->getPK();

		if (!is_array($this->_results)) $this->_results = array($this->_results);

		$_newResults = array();


		foreach($this->_results as $result) {

			$_newResult = array();

			if ( (is_object($result)) && (get_class($result) == $screen->getModelName()) ) {


			    foreach($this->_columnWrapper as $column) {

			        if (!$getter = $column->getGetterName($result)) continue;

			        if ($column->isMultilang()) {

			            $rValue = array();
			            foreach($this->_columnWrapper->getLangs() as $_lang) {
			                $rValue[$_lang] = $result->{$getter}($_lang);
			            }

			        } else {
			            $rValue = $result->{$getter}();
			        }
			        $_newResult[$column->getDbName()] = $column->prepareValue($rValue);

			    }

			    // Recuperamos también la clave primaria
			    $_newResult[$primaryKeyName] = $result->getPrimaryKey();
				$_newResults[] = $_newResult;

			}

		}

		$this->_results = $_newResults;

	}

	public function toArray() {

		$ret = array();
		$ret['title'] = $this->_title;
		$ret['columns'] = $this->_columnWrapper->toArray();

		// Probablemente no es la mejor forma de devolver los idiomas disponibles en los campos...
		$ret['langs'] = $this->_columnWrapper->getLangs();
        $ret['defaultLang'] = array_shift($this->_columnWrapper->getLangs());

		$ret['values'] = $this->_results;
		$ret['pk'] = $this->_pk;

		if (false !== $this->_fieldOptionsWrapper) {
			$ret['fieldOptions'] = $this->_fieldOptionsWrapper->toArray();
		}

		if (false !== $this->_generalOptionsWrapper) {
			$ret['generalOptions'] = $this->_generalOptionsWrapper->toArray();
		}

		if (false !== $this->_paginator) {
		    $ret['paginator'] = (array)$this->_paginator->getPages();
		}

		if (false !== $this->_parentIden) {
		    $ret['parentIden'] = $this->_parentIden;
		}

		$ret[$this->_item->getType()] = $this->_item->getItemName();

		return $ret;

	}

}