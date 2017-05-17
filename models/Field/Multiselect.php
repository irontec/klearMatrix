<?php
class KlearMatrix_Model_Field_Multiselect extends KlearMatrix_Model_Field_Abstract
{
    protected $_adapter;

    protected $sourceConfig;

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
        $this->sourceConfig = $this->_config->getRaw()->source;

        $adapterClassName = "KlearMatrix_Model_Field_Multiselect_" . ucfirst($this->sourceConfig->data);

        $this->_adapter = new $adapterClassName($this->sourceConfig, $this->_column);
        $this->_isSortable = false;

        if ($this->_adapter->getExtraJavascript()) {
            $this->_js = $this->_adapter->getExtraJavascript();
        }
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
        $dataGateway = \Zend_Registry::get('data_gateway');
        $relationEntityClass = $this->sourceConfig->config->relation;
        $relationEntityName = substr($relationEntityClass, strrpos($relationEntityClass, '\\')+1);
        $relationProperty = $this->sourceConfig->config->relationProperty;

        $model = $this->_column->getModel();
        $where = [
            $relationEntityName . '.' . $relationProperty . ' = :pk',
            ['pk' => $model->getId()]
        ];
        $currentValues = $dataGateway->findBy($relationEntityClass, $where);

        return $this->_adapter->filterValue($value, $currentValues);
    }

    public function isMassUpdateable()
    {
        return true;
    }

    public function getCustomSearchCondition($values, $searchOps)
    {
        return $this->_adapter->getCustomSearchCondition($values, $searchOps);
    }

    public function getAdapter()
    {
        return $this->_adapter;
    }
}

//EOF