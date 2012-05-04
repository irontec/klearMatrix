<?php

/**
 * Clase de campo tipo Ghost. Configuración en yaml para el campo.
    source:
      class: Application_Model_GhostTerminals -> Clase para hacer nuestras cosas con el valor del campo
      method: getManufacturer -> Método de la clase a la que vamos a enviar el valor del campo
      default: true -> Si ponemos este parámetro, pasamos al ghost el valor del campo en el que estamos
      field: modelId -> Si ponemos field, pasamos al ghost el valor del campo que pongamos en field
      cache: true -> Poniendo cache, si el valor que se vaya a pasar al ghost ya se había pasado, se devuelve el resultado cacheado

    Si no ponemos ni default ni field, pasamos al ghost el primaryKey del registro en el que estemos.

* @author David Lores
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

        if ($this->_config->getProperty('source')->cache) {
            if (isset($this->_cache[md5($class . '-' . $method)][$rValue])) {
                return $this->_cache[md5($class . '-' . $method)][$rValue];
            }
        }

        $ghost = new $class;
        $value = $ghost->{$method}($rValue);

        if ($this->_config->getProperty('source')->cache) {
            $this->_cache[md5($class . '-' . $method)][$rValue] = $value;
        }

        return $value;
    }
}