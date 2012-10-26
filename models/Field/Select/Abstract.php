<?php

abstract class KlearMatrix_Model_Field_Select_Abstract implements IteratorAggregate
{

    protected $_config;
    protected $_column;

    protected $_items;
    protected $_keys;


    public function __construct(Zend_Config $config, KlearMatrix_Model_Column $column)
    {
        $this->setConfig($config)->setColumn($column);
        $this->init();
    }

    public function setConfig(Zend_Config $config)
    {
        $this->_config = new Klear_Model_ConfigParser;
        $this->_config->setConfig($config);

        if ($this->_config->getProperty("null")) {

            $this->_keys[] = '__null__';
            $this->_items[] = $this->_config->getProperty("null");
        }

        return $this;
    }

    public function setColumn(KlearMatrix_Model_Column $column)
    {
        $this->_column = $column;
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

    public function getExtraConfigArray()
    {
        $ret = array();

        if (sizeof($this->_showOnSelect)>0 || sizeof($this->_hideOnSelect)>0) {

            $ret['visualFilter']['show'] = (array)$this->_showOnSelect;
            $ret['visualFilter']['hide'] = (array)$this->_hideOnSelect;
        }

        return $ret;
    }

    public function getIterator()
    {
        return new ArrayIterator(array_combine($this->_keys, $this->_items));
    }
}
