<?php
/**
 * Clase que devuelve la ruta al forward de _dispatch en base a la configuración a los parámetros de request
* @author jabi
*
*/
class KlearMatrix_Model_Field_Datepicker extends KlearMatrix_Model_Field_Abstract
{
    protected $_control;
    protected $_language = 'es';

    public function init()
    {
        parent::init();

        $sourceConfig = $this->_config->getRaw()->source;

        $controlClassName = "KlearMatrix_Model_Field_Datepicker_" . ucfirst($sourceConfig->control);

        $this->_control = new $controlClassName;

        $this->_control
            ->setConfig($sourceConfig)
            ->init();
    }

    public function getConfig()
    {
        return $this->_control->getConfig();
    }

    private function getDateFormat()
    {
        return str_replace(array('mm', 'yy'), array('MM','yyyy'), $this->_control->getDateFormat());
    }

    /*
     * Filtra (y adecua) el valor del campo antes del setter
     *
     */
    public function filterValue($value,$original)
    {
        $value = new Zend_Date($value, $this->getDateFormat());
        return $value;
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
        $getter = $this->_column->getGetterName($model);
        $zendDateValue = $model->$getter(true);

        $format = $this->getDateFormat(); //str_replace(array('mm', 'yy'), array('MM','yyyy'), $this->_control->getDateFormat());

        return $zendDateValue->toString($format);
    }
}