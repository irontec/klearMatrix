<?php
/**
 * Clase que devuelve la ruta al forward de _dispatch en base a la configuración a los parámetros de request
* @author jabi
*
*/
class KlearMatrix_Model_Field_Picker extends KlearMatrix_Model_Field_Abstract
{
    protected $_control;
    protected $_language = 'es';

    public function init()
    {
        parent::init();

        $sourceConfig = $this->_config->getRaw()->source;

        $controlClassName = "KlearMatrix_Model_Field_Picker_" . ucfirst($sourceConfig->control);

        $this->_control = new $controlClassName;

        $this->_control
            ->setConfig($sourceConfig)
            ->init();
    }

    public function getCustomSearchCondition($values, $searchOps, $model) {

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
            $_fieldValues[$template] = $this->filterValue($_val, $model);
                
            $cont++;

        }
        
        // Campos datetime / date / time se "conjugan" con and >> Antes de las 12 y despues de las 10
        return array(
                '(' . implode(' and ', $vals). ')',
                $_fieldValues
        );
            
            
            
    }
    
    public function getConfig()
    {
        return $this->_control->getConfig();
    }

    /*
     * Filtra (y adecua) el valor del campo antes del setter
     *
     */
    public function filterValue($value, $original)
    {

        return $this->_control->filterValue($value, $original);
    
    }

    /*
     * Prepara el valor de un campo, después del getter
     */
    /**
     * @param mixed $value Valor devuelto por el getter del model
     * @param object $model Modelo cargado
     * @return unknown
     */
    public function prepareValue($value, $model)
    {
        
        if (method_exists($this->_control, 'prepareValue')) {
            return $this->_control->prepareValue($value, $model);
        }
        
        $getter = $this->_column->getGetterName($model);
        $zendDateValue = $model->$getter(true);

        if ($zendDateValue instanceof Zend_Date) {
            $zendDateValue->setTimezone('Europe/Madrid');
            return $zendDateValue->toString($this->_control->getFormat());
        }

        return $value;
    }

    public function getExtraJavascript() 
    {
        if ($this->_control) {
            return $this->_control->getExtraJavascript();
        } else {
            return parent::getExtraJavascript();
        }
    }


    public function getExtraCss() 
    {
        if ($this->_control) {
            return $this->_control->getExtraCss();
        } else {
            return parent::getExtraCss();
        }
    }
}