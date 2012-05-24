<?php

/**
 * Clase de campo tipo Ghost. Configuración en yaml para el campo.
    source:
      class: Application_Model_GhostTerminals -> Clase para hacer nuestras cosas con el model del registro
      method: getManufacturer -> Método de la clase a la que vamos a enviar el model del registro
      field: modelId -> Si ponemos field, pasamos al ghost el valor del campo que pongamos en field. Si no se pone, se pasa el primaryKey
      cache:
        campo: true -> Los valores de estos campos se añaden al valor del campo para comprobar la cache
      conditions:
        campo: valor -> Se hace el ghost si cumple las condiciones. Si no cumple las condiciones, se devuelve el valor del campo

* @author David Lores
*
*/

class KlearMatrix_Model_Field_Ghost extends KlearMatrix_Model_Field_Abstract
{

    protected $_cache = array();

    public function init()
    {
        $ret = parent::init();
        $this->_column->markAsReadOnly();

        if ($this->_config->getProperty('order')) {
            $this->_customOrderField = $this->_config->getProperty('order');
        }

        $this->_canBeSearched = false;

        return $ret;
    }

    public function getCustomGetterName($model)
    {

        if ($this->_config->getProperty('source')->field) {

            //Si existe el parámetro field, devolvemos el getter para ese campo
            $method = 'get' . $model->columnNameToVar($this->_config->getProperty('source')->field);
            return $method;
        }

        return 'getPrimaryKey';
    }

    public function prepareValue($rValue, $model)
    {

        //Comprobamos si se cumplean las condiciones para hacer el ghost
        if ($this->_config->getProperty('source')->conditions) {

            foreach ($this->_config->getProperty('source')->conditions as $key => $condition) {

                $get = 'get' . $model->columnNameToVar($key);
                $res = $model->$get();
                if ($res != $condition) {

                    return $rValue;
                }
            }
        }

        //Añadimos a las condiciones de cache si existen
        $cache = '';
        if ($this->_config->getProperty('source')->cache) {

            foreach ($this->_config->getProperty('source')->cache as $key => $condition) {

                $get = 'get' . $model->columnNameToVar($key);
                $res = $model->$get();
                $cache .= $key . $res;
            }
        }

        //Cogemos class y method para enviar el resultado
        $class = $this->_config->getProperty('source')->class;
        $method = $this->_config->getProperty('source')->method;

        $md5cache = md5($cache);
        $md5method = md5($class . $method);

        if ($this->_config->getProperty('source')->cache
            && isset($this->_cache[$md5method][$md5cache])) {

                return $this->_cache[$md5method][$md5cache];
        }

        $ghost = new $class;
        $value = $ghost->{$method}($model);

        if ($this->_config->getProperty('source')->cache) {

            $this->_cache[$md5method][$md5cache] = $value;
        }

        return $value;
    }
}

//EOF
