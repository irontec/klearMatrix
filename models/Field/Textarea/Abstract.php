<?php


abstract class KlearMatrix_Model_Field_Textarea_Abstract implements Iterator {

    protected $_config;
    protected $_items;
    protected $_keys;
    protected $_position;

    protected $_column;

    public function __construct() {
        $this->rewind();
    }


    public function setConfig(Zend_Config $config) {

        $this->_config = new Klear_Model_KConfigParser;

        $this->_config->setConfig($config);
        if ($this->_config->getProperty("null")) {
            $this->_keys[] = '__null__';
            $this->_items[] = $this->_config->getProperty("null");
        }

        return $this;
    }

    public function toArray()
    {
        $ret = array();
        foreach ($this as $key => $value) {
            $ret[] = array('key' => $key, 'item' => $value);
        }
        return $ret;
    }

    public function getExtraConfigArray() {
        return array();
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