<?php

/**
 * Clase de campo tipo Ghost. Configuración en yaml para el campo.
    source:
      class: Application_Model_GhostTerminals -> Clase para hacer nuestras cosas con el valor del campo
      method: getManufacturer -> Método de la clase a la que vamos a enviar el valor del campo
      default: true -> Si ponemos este parámetro, pasamos al ghost el valor del campo en el que estamos
      field: modelId -> Si ponemos field, pasamos al ghost el valor del campo que pongamos en field
      cache: true -> Poniendo cache, si el valor que se vaya a pasar al ghost ya se había pasado, se devuelve el resultado cacheado
      cacheConditions:
        campo: true -> Los valores de estos campos se añaden al valor del campo para comprobar la cache
      conditions:
        campo: valor -> Se hace el ghost si cumple las condiciones. Si no cumple las condiciones, se devuelve el valor del campo

    Si no ponemos ni default ni field, pasamos al ghost el primaryKey del registro en el que estemos.

* @author David Lores
*
*/

class KlearMatrix_Model_Field_Ghost extends KlearMatrix_Model_Field_Abstract
{

    protected $_cache = array();
    protected $_cacheConditions = '';
    protected $_condition = true;

    public function getCustomGetterName($model)
    {
        $this->_condition = true;
        $this->_conditionCache = '';

        //Comprobamos si se cumplean las condiciones para hacer el ghost
        if ($this->_config->getProperty('source')->conditions) {

            foreach ($this->_config->getProperty('source')->conditions as $key => $condition) {

                $get = 'get' . $model->columnNameToVar($key);
                $res = $model->$get();
                if ($res != $condition) {

                    $this->_condition = false;
                    break;
                }
            }
        }

        //Añadimos a las condiciones de cache si existen
        if ($this->_config->getProperty('source')->cacheConditions) {

            foreach ($this->_config->getProperty('source')->cacheConditions as $key => $condition) {

                $get = 'get' . $model->columnNameToVar($key);
                $res = $model->$get();
                $this->_cacheConditions .= '-' . $res;
            }
        }

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
        if ($this->_condition === false) {

            return $rValue;
        }

        //Cogemos class y method para enviar el resultado
        $class = $this->_config->getProperty('source')->class;
        $method = $this->_config->getProperty('source')->method;

        $keyCache = md5($class . '-' . $method . $this->_cacheConditions);

        if ($this->_config->getProperty('source')->cache) {
            if (isset($this->_cache[$keyCache][$rValue])) {
                return $this->_cache[$keyCache][$rValue];
            }
        }

        $ghost = new $class;
        $value = $ghost->{$method}($rValue);

        if ($this->_config->getProperty('source')->cache) {
            $this->_cache[$keyCache][$rValue] = $value;
        }

        return $value;
    }
}

//EOF
