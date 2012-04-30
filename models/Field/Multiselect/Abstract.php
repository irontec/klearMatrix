<?php


abstract class KlearMatrix_Model_Field_Multiselect_Abstract implements Iterator {

    protected $_config;
    protected $_items;
    protected $_keys;
    protected $_position;

    protected $_column;

    public function filterValue($value,$original) {
        return $value;
    }

    public function prepareValue($value, $model) {
        return $value;
    }

    public function toArray()
    {
        $ret = array();
        foreach ($this as $key => $value) {
            $ret[] = array('key' => $key, 'item' => $value);
        }
        return $ret;
    }

    public function __construct() {
        $this->rewind();
    }

    public function setColumn($column) {
        $this->_column = $column;
        return $this;
    }

    public function setConfig(Zend_Config $config) {
        $this->_config = $config;
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

    /**
     * Devuelve un listado de campos a editar para cada relación
     *     Tipos Soportados:
     *         - radio
     *         -
     * @return array:
     */
    public function getEditableFieldsConfig() {
        return array();

    }
}