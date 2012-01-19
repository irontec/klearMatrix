<?php

class KlearMatrix_Model_GeneralOptionsWrapper implements Iterator {
	
	public $_opts = array();
	
	protected $_title;
	
	
	public function addOption($opt) {
		$this->_opts[] = $opt;
	
	}
	
	public function toArray() {
		$retArray = array();
		foreach ($this->_opts as $opt) {
			$retArray[] = $opt->toArray();
		}
	
		return $retArray;
	}
	
	
	protected $_position;
	
	public function __construct() {
		$this->_position = 0;
	}
	
	public function rewind() {
		$this->_position = 0;
	}
	
	public function current() {
		return $this->_opts[$this->_position];
	}
	
	public function key() {
		return $this->_position;
	}
	
	public function next() {
		++$this->_position;
	}
	
	public function valid() {
		return isset($this->_opts[$this->_position]);
	
	}
	
}
