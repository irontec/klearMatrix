<?php

abstract class KlearMatrix_Model_Field_Select_Abstract implements IteratorAggregate
{

    protected $_config;
    protected $_column;

    protected $_items = array();
    protected $_keys = array();

    protected $_js = array();


    public function __construct(Zend_Config $config, KlearMatrix_Model_Column $column)
    {
        $this->setConfig($config)->setColumn($column);
        $this->init();
    }

    protected function _quoteIdentifier($fieldName)
    {
        $dbAdapter = Zend_Db_Table::getDefaultAdapter();
        if ($dbAdapter) {
           return $dbAdapter->quoteIdentifier($fieldName);
        }

        return $fieldName;
    }

    public function setConfig(Zend_Config $config)
    {
        $this->_config = new Klear_Model_ConfigParser;
        $this->_config->setConfig($config);

        if ($this->_config->getProperty("null")) {

            $this->_keys[] = '__null__';
            $this->_items[] = Klear_Model_Gettext::gettextCheck($this->_config->getProperty("null"));
        }

        return $this;
    }

    public function setColumn(KlearMatrix_Model_Column $column)
    {
        $this->_column = $column;
        return $this;
    }

    public function getConfig()
    {
        $config = $this->_getExtraConfigArray();
        $config['values'] = $this->_toArray();

        return $config;
    }

    protected function _getExtraConfigArray()
    {
        $ret = array();

        if (sizeof($this->_showOnSelect) || sizeof($this->_hideOnSelect)) {

            $ret['visualFilter']['show'] = array();
            $ret['visualFilter']['hide'] = array();

            foreach ($this->_showOnSelect as $field => $fieldColection) {
                $ret['visualFilter']['show'][$field] = $fieldColection->toArray();
            }

            foreach ($this->_hideOnSelect as $field => $fieldColection) {
                $ret['visualFilter']['hide'][$field] = $fieldColection->toArray();
            }
        }

        return $ret;
    }

    protected function _toArray()
    {
        $ret = array();

        foreach ($this as $key => $value) {

            $ret[] = array('key' => $key, 'item' => $value);
        }

        return $ret;
    }

    public function getIterator()
    {
        if (!$this->_keys  || !$this->_items) {
            return new ArrayIterator(array());
        }
        return new ArrayIterator(array_combine($this->_keys, $this->_items));
    }

    public function getExtraJavascript()
    {
        return $this->_js;
    }
}
