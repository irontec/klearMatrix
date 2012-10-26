<?php
class KlearMatrix_Model_Field_Multiselect extends KlearMatrix_Model_Field_Abstract
{

    protected $_adapter;

    protected $_js = array(
       "/js/plugins/jquery.multiselect.filter.js",
       "/js/plugins/jquery.multiselect.js"
    );

    protected $_css = array(
       "/css/jquery.multiselect.css",
       "/css/jquery.multiselect.filter.css"
    );

    public function init()
    {
        parent::init();
        $sourceConfig = $this->_config->getRaw()->source;

        $adapterClassName = "KlearMatrix_Model_Field_Multiselect_" . ucfirst($sourceConfig->data);

        $this->_adapter = new $adapterClassName($sourceConfig, $this->_column);
    }

    public function getConfig()
    {
        $ret = array(
            'values' => $this->_adapter->toArray(),
            'editableFields' => $this->_adapter->getEditableFieldsConfig()
        );

        return $ret;
    }

    /*
     * Multiselect, recibe un array con modelos de relación
     * Es necesario cruzarlos con los posibles modelos a relacionar
     * Gateway hacia el adapter.
     * @see KlearMatrix_Model_Field_Abstract::filterValue()
     */
    public function prepareValue($value, $model)
    {
        return $this->_adapter->prepareValue($value, $model);
    }

    public function filterValue($value, $original)
    {
        return $this->_adapter->filterValue($value, $original);
    }
}

//EOF