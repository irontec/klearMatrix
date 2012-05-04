<?php

/**
 * Clase que devuelve la ruta al forward de _dispatch en base a la configuración a los parámetros de request
* @author jabi
*
*/

class KlearMatrix_Model_Field_Ghost extends KlearMatrix_Model_Field_Abstract
{

    protected $_cache = array();

    public function getCustomGetterName($model)
    {
        if ($this->_config->getProperty('source')->default) {
            //Si existe el parámetro default, devolvemos el getter default
            return $this->_column->getGetterName($model, true);

        } elseif ($this->_config->getProperty('source')->field) {
            //Si existe el parámetro field, devolvemos el getter para ese campo
            return 'get' . $model->columnNameToVar($this->_config->getProperty('source')->field);
        }

        //Si no existe field, devolvemos para coger el primaryKey
        return 'getPrimaryKey';
    }

    public function prepareValue($rValue, $result)
    {
        //Cogemos class y method para enviar el resultado
        $class = $this->_config->getProperty('source')->class;
        $method = $this->_config->getProperty('source')->method;
        $ghost = new $class;

        return $ghost->{$method}($rValue);
    }
}