<?php 


abstract class KlearMatrix_Model_Field_Select_Interface implements Iterator {

    public function __construct() {
        $this->rewind();
    }
    
    public function rewind() {
        $this->_position = 0;
    }
    
    public function current() {
        return $this->_items[$this->_position];
    }
    
    public function key() {
        return $this->_position;
    }
    
    public function next() {
        ++$this->_position;
    }
    
    public function valid() {
        return isset($this->_items[$this->_position]);
    
    }
    
    public function setConfig($config) {
        
        
    }
    
    
}