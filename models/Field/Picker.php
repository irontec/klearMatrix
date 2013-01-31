<?php
class KlearMatrix_Model_Field_Picker extends KlearMatrix_Model_Field_Abstract
{
    protected $_adapter;
    protected $_language = 'es';

    protected function _init()
    {
        $sourceConfig = $this->_config->getRaw()->source;

        $controlClassName = "KlearMatrix_Model_Field_Picker_" . ucfirst($sourceConfig->control);

        $this->_adapter = new $controlClassName($sourceConfig);

        $this->_js = $this->_adapter->getExtraJavascript();
        $this->_css = $this->_adapter->getExtraCss();
    }

    public function getCustomSearchCondition($values, $searchOps)
    {
        $searchField = $this->_column->getDbFieldName();
        $_fieldValues = $vals = array();
        $cont = 0;
        foreach ($values as $idx => $_val) {
            $template = ':' . $searchField . $cont;

            $op = "=";
            if (isset($searchOps[$idx])) {
                switch($searchOps[$idx]) {
                    case 'lt':
                        $op = '<';
                        break;
                    case 'gt':
                        $op = '>';
                        break;
                }
            }

            $vals[] = $searchField .' '.$op.' '. $template;
            $_fieldValues[$template] = $this->filterValue($_val);

            $cont++;

        }

        // Campos datetime / date / time se "conjugan" con and >> Antes de las 12 y despues de las 10
        return array(
                '(' . implode(' and ', $vals). ')',
                $_fieldValues
        );
    }

    /*
     * Filtra (y adecua) el valor del campo antes del setter
     *
     */
    protected function _filterValue($value)
    {
        return $this->_adapter->filterValue($value);
    }

    /*
     * Prepara el valor de un campo, después del getter
     */
    /**
     * @param mixed $value Valor devuelto por el getter del model
     * @return unknown
     */
    public function prepareValue($value)
    {
        $model = $this->_column->getModel();
        if (method_exists($this->_adapter, 'prepareValue')) {
            return $this->_adapter->prepareValue($value, $model);
        }

        $getter = $this->_column->getGetterName();
        $zendDateValue = $model->$getter(true);

        if ($zendDateValue instanceof Zend_Date) {
            $zendDateValue->setTimezone(date_default_timezone_get());
            return $zendDateValue->toString($this->_adapter->getFormat());
        }

        return $value;
    }
}