<?php

abstract class KlearMatrix_Model_Field_Multiselect_Abstract implements IteratorAggregate
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
        $this->_config = $config;

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

            $ret[] = array(
                'key' => $key,
                'item' => $value
            );
        }

        return $ret;
    }

    /**
     * Devuelve un listado de campos a editar para cada relación
     *     Tipos Soportados:
     *         - radio
     *         -
     * @return array:
     */
    abstract public function getEditableFieldsConfig();
    abstract public function filterValue($value, $original);
    abstract public function prepareValue($value, $model);


    public function getIterator()
    {
        return new ArrayIterator(array_combine($this->_keys, $this->_items));
    }

}

//EOF