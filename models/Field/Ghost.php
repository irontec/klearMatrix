<?php
/**
 * Clase de campo tipo Ghost. Configuración en yaml para el campo.
    source:
      class: Application_Model_GhostTerminals -> Clase para hacer nuestras cosas con el model del registro
      method: getManufacturer -> Método de la clase a la que vamos a enviar el model del registro
      orderMethod: getOrderForDuration devuelve el campo a aplicar en el "order by" << (se puede lista
      searchMethod: getSearchConditionsForDuration
      field: modelId
                -> Si ponemos field, pasamos al ghost el valor del campo que pongamos en field.
                ->  Si no se pone, se pasa el primaryKey
      cache:
        campo: true -> Los valores de estos campos se añaden al valor del campo para comprobar la cache
      conditions:
        campo: valor
                -> Se hace el ghost si cumple las condiciones.
                -> Si no cumple las condiciones, se devuelve el valor del campo

* @author David Lores
*
*/

class KlearMatrix_Model_Field_Ghost extends KlearMatrix_Model_Field_Abstract
{
    protected $_cache = array();
    protected $_ghostClassName;
    protected $_ghostMethod;

    protected $_searchMethod;
    protected $_orderMethod;

    protected $_ghostObject;

    protected function _init()
    {

        $this->_column->setReadOnly(true);
        $this->_isSearchable = false;
        $this->_isSortable = false;

        if ($this->_config->getRaw()->source->predefined) {

            $predefinedClassName = 'KlearMatrix_Model_Field_Ghost_'
                . ucfirst($this->_config->getRaw()->source->predefined);

            $this->_ghostObject = new $predefinedClassName;
            $this->_ghostObject
                    ->setConfig($this->_config->getRaw())
                    ->configureHostFieldConfig($this)
                    ->init();
            return;

        }


        // Required configuration
        if (!$this->_config->getRaw()->source->class) {
            throw new Klear_Exception_MissingConfiguration('Missing "class" in Ghost field configuration');
        }

        if (!$this->_config->getRaw()->source->method) {
            throw new Klear_Exception_MissingConfiguration('Missing "method" in Ghost field configuration');
        }

        $this->setGetterMethod($this->_config->getRaw()->source->method);
        $this->_ghostClassName = $this->_config->getRaw()->source->class;



        // Optional configuration

        if ($this->_config->getRaw()->source->searchMethod) {
            $this->setSearchMethod($this->_config->getRaw()->source->searchMethod);
        }

        if ($this->_config->getRaw()->source->orderMethod) {
            $this->setOrderMethod($this->_config->getRaw()->source->orderMethod);
        }

    }

    public function setSearchMethod($method)
    {
        $this->_searchMethod = $method;
        $this->_isSearchable = true;
        return $this;
    }

    public function setOrderMethod($method)
    {
        $this->_orderMethod = $method;
        $this->_isSortable = true;
        return $this;
    }

    public function setGetterMethod($method)
    {
        $this->_ghostMethod = $method;
        return $this;
    }

    public function getCustomSearchCondition($values, $searchOps)
    {
        if (!$this->isSearchable()) {
            //FIXME: Should not get into this function... And false doesn't seem a nice return value
            return false;
        }

        $ghostModel = $this->_getGhostModel();
        $searchMethod = $this->_searchMethod;

        if (method_exists($ghostModel, $searchMethod)) {

            $searchCondition = $ghostModel->{$searchMethod}
                ($values, $searchOps, $this->_column->getModel());

            if ($searchCondition) {
                return $searchCondition;
            }
        }

        return false;
    }

    protected function _getGhostModel()
    {
        if (!isset($this->_ghostObject)) {
            $this->_ghostObject = new $this->_ghostClassName;
            if (method_exists($this->_ghostObject, 'setConfig')) {
                $this->_ghostObject->setConfig($this->_config->getRaw());
            }
        }

        return $this->_ghostObject;
    }

    public function getCustomGetterName()
    {
        if ($this->_config->getProperty('source')->field) {

            //Si existe el parámetro field, devolvemos el getter para ese campo
            $method = 'get' . $this->_column->getModel()->columnNameToVar($this->_config->getProperty('source')->field);
            return $method;
        }

        return 'getId';
    }


    public function getCustomOrderField()
    {
        if (!$this->_orderMethod) {
            return null;
        }

        return $this->_getGhostModel()->{$this->_orderMethod}($this->_column->getModel());
    }

    public function prepareValue($value)
    {
        $model = $this->_column->getModel();

        if (!$this->_conditionsAreMet($model)) {
            return $value;
        }

        // Class and Method options to get the result
        $md5method = md5($this->_ghostClassName . $this->_ghostMethod);

        $cache = $this->_getCacheData($model);
        $md5cache = md5($cache);

        if ($this->_dataIsCached($md5method, $md5cache)) {
            return $this->_cache[$md5method][$md5cache];
        }

        $returnValue = $this->_getGhostModel()->{$this->_ghostMethod}($model);
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
            $condition; // Avoid PMD UnusedLocalVariable warning
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

    protected function _cacheData($md5method, $md5cache, $value)
    {
        if ($this->_config->getProperty('source')->cache) {

            $this->_cache[$md5method][$md5cache] = $value;
        }
    }

    protected function _getConfig()
    {
        return array();
    }
}

//EOF
