<?php 


abstract class KlearMatrix_Model_Field_Select_Abstract implements Iterator {
    
    protected $_config;
    protected $_items;
    protected $_keys;
    protected $_position;

    protected $_column;
    
    public function __construct() {
        $this->rewind();
    }
    
    
    public function setConfig(Zend_Config $config) {
        $this->_config = $config;
        return $this;
    }
    
    public function setColumn($column) {
        $this->_column = $column;
        return $this;        
    }
    
    public function rewind() {
        $this->_position = 0;
    }
    
    public function current() {
        return $this->_items[$this->_position];
    }
    
    public function key() {
        return $this->_keys[$this->_position];
    }
    
    public function next() {
        ++$this->_position;
    }
    
    public function valid() {
        return isset($this->_items[$this->_position]);
    
    }
    
    
}