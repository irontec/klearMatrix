<?php
/**
* Clase respuesta MatrixResponse para peticiones desde klear.request.js
* @author jabi
*/

class KlearMatrix_Model_MatrixResponse
{

    protected $_columnWrapper;
    protected $_results;
    protected $_fieldOptionsWrapper = false;
    protected $_generalOptionsWrapper = false;

    protected $_messages;

    protected $_paginator = false;
    protected $_csv = false;

    protected $_parentIden = false;
    protected $_parentId = false;
    protected $_parentPk = false;
    protected $_parentScreen = false;
    protected $_parentItem = false;

    protected $_disableSave = false;

    protected $_title;

    protected $_searchFields = array();
    protected $_searchAddModifier = false;

    protected $_info = false;

    //@var KlearMatrix_Model_ResponseItem;
    protected $_item;

    protected $_pk;

    public function setColumnWraper(KlearMatrix_Model_ColumnWrapper $columnWrapper)
    {
        $this->_columnWrapper = $columnWrapper;
        return $this;
    }

    public function setResults($results)
    {
        $this->_results = $results;
        return $this;
    }

    public function setPK($pk)
    {
        $this->_pk = $pk;
        return $this;
    }

    public function setTitle($title)
    {
        $this->_title = $title;
        return $this;
    }

    /**
     * Opciones "generales" de pantalla
     * @param KlearMatrix_Model_OptionsWrapper $screenOptsWrapper
     */
    public function setGeneralOptions(KlearMatrix_Model_OptionsWrapper $generalOptsWrapper)
    {
        $this->_generalOptionsWrapper = $generalOptsWrapper;
        return $this;
    }

    /**
     * Opciones por fila
     * @param KlearMatrix_Model_OptionsWrapper $fieldOptsWrapper
     */
    public function setFieldOptions(KlearMatrix_Model_OptionsWrapper $fieldOptsWrapper)
    {
        $this->_fieldOptionsWrapper = $fieldOptsWrapper;
        return $this;
    }


    public function setResponseItem(KlearMatrix_Model_ResponseItem $item)
    {
        $this->_item = $item;
        return $this;
    }

    public function setCsv($value)
    {

        $this->_csv = (bool)$value;
    }

    public function setPaginator(Zend_Paginator $paginator)
    {
        $this->_paginator = $paginator;
    }

    public function setParentIden($parentIden)
    {
        $this->_parentIden = $parentIden;
    }

    public function setParentId($parentId)
    {
        $this->_parentId = $parentId;
        return $this;
    }

    public function setParentPk($parentPk)
    {
        $this->_parentPk = $parentPk;
        return $this;
    }

    public function setParentScreen($parentScreen)
    {
        $this->_parentScreen = $parentScreen;
    }

    public function setParentItem($parentItem)
    {
        $this->_parentItem = $parentItem;
    }

    public function setDisableSave($disableSave)
    {
        $this->_disableSave = $disableSave;
    }

    /**
     * Ayuda contextual seteada
     * @param boolean|array $info
     */
    public function setInfo($info)
    {
        if (false === $info) {
            return;
        }

        $this->_info = $info;
    }

    public function addSearchField($field,$values)
    {
        $this->_searchFields[$field] = $values;
    }

    public function addSearchAddModifier($toggle)
    {
        $this->_searchAddModifier = $toggle;
    }


    /**
     * Setea para su procesamiento el array de mensajes de confirmación y error predefinidos
     * @param KlearMatrix_Model_ActionMessageWrapper $msgs
     */
    public function setActionMessages(KlearMatrix_Model_ActionMessageWrapper $msgs)
    {
        $this->_messages = $msgs;

    }


    /**
     * Si los resultados (de data) son objetos, los pasa a array (para JSON)
     * Se eliminan los campos no presentes en el column-wrapper
     * Se pasa el controlador llamante(edit|new|delete|list), por si implicara cambio en funcionalidad por columna
     * Gestionamos el multi-idioma de los campos multiidioma (getters con $lang seleccionado)
     */
    public function fixResults(KlearMatrix_Model_ResponseItem $screen)
    {


        $primaryKeyName = $screen->getPK();


        if (!is_array($this->_results)) $this->_results = array($this->_results);

        $_newResults = array();


        foreach ($this->_results as $result) {

            $_newResult = array();

            if ((is_object($result)) && (get_class($result) == $screen->getModelName())) {
                foreach ($this->_columnWrapper as $column) {

                    if (!$getter = $column->getGetterName($result)) {
                        continue;
                    }

                    if ($column->isMultilang()) {

                        $rValue = array();
                        foreach ($this->_columnWrapper->getLangs() as $_lang) {
                            $rValue[$_lang] = $result->{$getter}($_lang);
                        }

                    } else {
                        $rValue = $result->{$getter}();
                    }

                    $_newResult[$column->getDbName()] = $column->prepareValue($rValue, $result);

                }

                // Recuperamos también la clave primaria
                $_newResult[$primaryKeyName] = $result->getPrimaryKey();
                $_newResults[] = $_newResult;

            }

        }

        $this->_results = $_newResults;

    }

    public function toArray()
    {

        $ret = array();
        $ret['title'] = $this->_title;
        $ret['columns'] = $this->_columnWrapper->toArray();

        // Probablemente no es la mejor forma de devolver los idiomas disponibles en los campos...
        $ret['langs'] = $this->_columnWrapper->getLangs();
        $ret['defaultLang'] = $this->_columnWrapper->getDefaultLang();

        $ret['values'] = $this->_results;
        $ret['pk'] = $this->_pk;

        if (false !== $this->_fieldOptionsWrapper) {
            $ret['fieldOptions'] = $this->_fieldOptionsWrapper->toArray();
        }

        if (false !== $this->_generalOptionsWrapper) {
            $ret['generalOptions'] = $this->_generalOptionsWrapper->toArray();
        }

        if ($this->_csv !== false) {
            $ret['csv'] = true;
        }

        if (false !== $this->_paginator) {
            $ret['paginator'] = (array)$this->_paginator->getPages();
        }

        if (sizeof($this->_searchFields)>0) {
            $ret['searchFields'] = $this->_searchFields;
            $ret['searchAddModifier'] = $this->_searchAddModifier;
        }

        if (false !== $this->_info) {
            $ret['info'] = $this->_info;
        }

        $simpleFields = array('parentIden','parentId','parentScreen','parentItem','parentPk','disableSave');
        foreach ($simpleFields as $_fld) {
            if (false !== $this->{'_' . $_fld}) {
                $ret[$_fld] = $this->{'_'. $_fld};
            }
        }

        if (sizeof($this->_messages) > 0) {
            $ret['actionMessages'] = $this->_messages->toArray();
        }

        $ret[$this->_item->getType()] = $this->_item->getItemName();

        return $ret;

    }

}
