<?php

abstract class KlearMatrix_Model_Field_Multiselect_Abstract implements IteratorAggregate
{
    protected $_config;
    protected $_column;

    protected $_items;
    protected $_keys;

    protected $_js = array();

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

    public function getConfig()
    {
        $config = array(
            'values' => $this->_toArray(),
            'editableFields' => $this->_getEditableFieldsConfig()
        );

        return $config;
    }

    public function _toArray()
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
    abstract protected function _getEditableFieldsConfig();
    abstract public function filterValue($value, $original);
    abstract public function prepareValue($value);


    /**
     * Un multiselect deberá implementar su método de Filtrado propio
     * (ya que es un campo fantasma, no presente en al tabla).
     * @param unknown_type $values
     * @param unknown_type $searchOps
     */
    abstract public function getCustomSearchCondition($values, $searchOps);

    public function getIterator(): Traversable
    {
        $parentArray = array();
        if (is_array($this->_keys) && is_array($this->_items)) {
            $parentArray = array_combine($this->_keys, $this->_items);
        }

        return new ArrayIterator($parentArray);
    }

    public function getExtraJavascript()
    {
        return $this->_js;
    }
}
//EOF
