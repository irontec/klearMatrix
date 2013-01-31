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

    protected function _init()
    {
        $sourceConfig = $this->_config->getRaw()->source;

        $adapterClassName = "KlearMatrix_Model_Field_Multiselect_" . ucfirst($sourceConfig->data);

        $this->_adapter = new $adapterClassName($sourceConfig, $this->_column);
    }

    /*
     * Multiselect, recibe un array con modelos de relación
     * Es necesario cruzarlos con los posibles modelos a relacionar
     * Gateway hacia el adapter.
     * @see KlearMatrix_Model_Field_Abstract::filterValue()
     */
    public function prepareValue($value)
    {
        return $this->_adapter->prepareValue($value);
    }

    protected function _filterValue($value)
    {
        $model = $this->_column->getModel();
        $getter = $this->_column->getGetterName();

/*
 * TODO: No elimino estas líneas para recordar que hay que comprobar que el multilang funciona en los multiselect...
 *
 */
//         if ($this->_column->isMultilang()) {
//             return $this->_adapter->filterValue($value, $model->$getter($lang));
//         }

        return $this->_adapter->filterValue($value, $model->$getter());
    }
}

//EOF