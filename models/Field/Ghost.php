<?php

/**
 * Clase de campo tipo Ghost. Configuración en yaml para el campo.
    source:
      class: Application_Model_GhostTerminals -> Clase para hacer nuestras cosas con el model del registro
      method: getManufacturer -> Método de la clase a la que vamos a enviar el model del registro
      orderMethod: getOrderForDuration devuelve el campo a aplicar en el "order by" << (se puede lista
      searchMethod: getSearchConditionsForDuration
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

        if ($this->_config->getRaw()->source->searchMethod) {
            $this->_canBeSearched = true;
        } else {
            $this->_canBeSearched = false;
        }

        return $ret;
    }

    public function getCustomSearchCondition($values, $searchOps, $model)
    {
        if (!$this->_config->getRaw()->source->searchMethod) {
            return false;
        }

        $class = $this->_config->getRaw()->source->class;
        $searchMethod = $this->_config->getRaw()->source->searchMethod;

        $ghostObject = new $class;

        if ( method_exists($ghostObject, 'setConfig') ) {

            $ghostObject->setConfig($this->_config->getRaw());
        }

        if ($searchCondition = $ghostObject->{$searchMethod}($values, $searchOps, $model)) {
            $this->_canBeSearched = true;
            return $searchCondition;
        }

        return false;
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


    public function getCustomOrderField($model)
    {
        // El modelo fantasma tiene un método que devuelve el campo por el que hay que ordenar??
        if (!$this->_config->getRaw()->source->orderMethod) {
            return $this->_column->getDbFieldName();
        }

        $class = $this->_config->getRaw()->source->class;
        $orderMethod = $this->_config->getRaw()->source->orderMethod;

        $ghostObject = new $class;

        if ( method_exists($ghostObject, 'setConfig') ) {

            $ghostObject->setConfig($this->_config->getRaw());
        }

        if ($orderField = $ghostObject->{$orderMethod}($model)) {

            return $orderField;
        }

        return false;

    }

    public function prepareValue($value, $model)
    {
        if (!$this->_conditionsAreMet($model)) {
            return $value;
        }

        // Class and Method options to get the result
        $className = $this->_config->getProperty('source')->class;
        $method = $this->_config->getProperty('source')->method;
        $md5method = md5($className . $method);

        $cache = $this->_getCacheData();
        $md5cache = md5($cache);

        if ($this->_dataIsCached($md5method, $md5cache)) {
            return $this->_cache[$md5method][$md5cache];
        }

        $ghostModel = new $className;
        $this->_configureGhostModel($ghostModel);
        $returnValue = $ghostModel->{$method}($model);
        $this->_cacheData($md5method, $md5cache, $returnValue);

        return $returnValue;
    }

    /**
     * Comprobar si se cumplen las condiciones para hacer el ghost (source->conditions)
     * @return boolean
     */
    protected function _conditionsAreMet($model)
    {
        $conditions = $this->_config->getProperty('source')->conditions;
        if ($conditions) {

            foreach ($conditions as $fieldName => $condition) {

                $getter = 'get' . $model->columnNameToVar($fieldName);
                $fieldValue = $model->$getter();
                if ($fieldValue != $condition) {

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Devuelve el string que identifica la tupla actual para generar el identificador de cache (source->cache)
     * @param object $model
     * @return string
     */
    protected function _getCacheData($model)
    {
        $cacheIdentifiers = $this->_config->getProperty('source')->cache;
        if (!$cacheIdentifiers) {
            return '';
        }

        $cache = '';
        foreach ($cacheIdentifiers as $fieldName => $condition) {

            $getter = 'get' . $model->columnNameToVar($fieldName);
            $fieldValue = $model->$getter();
            $cache .= $fieldName . $fieldValue;
        }

        return $cache;
    }

    protected function _dataIsCached($md5method, $md5cache)
    {
        return $this->_config->getProperty('source')->cache && isset($this->_cache[$md5method][$md5cache]);
    }

    protected function _configureGhostModel($ghostModel)
    {
        if (method_exists($ghostModel, 'setConfig')) {

            $ghostModel->setConfig($this->_config->getRaw());
        }
    }

    protected function _cacheData($md5method, $md5cache, $value)
    {
        if ($this->_config->getProperty('source')->cache) {

            $this->_cache[$md5method][$md5cache] = $value;
        }
    }
}

//EOF
