<?php

/**
 * Clase que devuelve la ruta al forward de _dispatch en base a la configuración a los parámetros de request
* @author jabi
*
*/

class KlearMatrix_Model_Field_Ghost extends KlearMatrix_Model_Field_Abstract
{

    public function getCustomGetterName($model)
    {
        //Si existe el parámetro field, devolvemos el getter del campo
        if ($this->_config->getProperty('source')->field) {
            return $this->_column->getGetterName($model, true);
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