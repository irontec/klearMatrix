<?php

class KlearMatrix_Model_ColumnCollection implements IteratorAggregate
{
    protected $_columns = array();
    protected $_position;

    protected $_optionColumnIdx = false;
    protected $_defaultColumnIdx = false;
    protected $_dependantColumnIdx = array();
    protected $_multilangColumnIdx = array();
    protected $_fileColumnIdx = array();

    protected $_langs = array();

    protected $_types = array();

    protected $_langDefinitions = array();

    public function addCol(KlearMatrix_Model_Column $column)
    {
        $this->_columns[$column->getDbFieldName()] = $column;

        // Estamos dando por hecho, que hay sólo una columna de opciones por listado.
        if ($column->isOption()) {

            $this->_optionColumnsIdx = $column->getDbFieldName();
        } else {

            $this->_types[$column->getType()] = $column->getDbFieldName();
        }

        if ($column->isDefault()) {
            $this->_defaultColumnIdx = $column->getDbFieldName();
        }

        if ($column->isDependant()) {
            $this->_dependantColumnIdx[] = $column->getDbFieldName();
        }

        if ($column->isMultilang()) {
            $this->_multilangColumnIdx[] = $column->getDbFieldName();
        }

        if ($column->isFile()) {
            $this->_fileColumnIdx[] = $column->getDbFieldName();
        }
    }

    /**
     * @param KlearMatrix_Model_Column[] $columns
     */
    public function addCols(array $columns)
    {
        foreach ($columns as $column) {
            $this->addCol($column);
        }
    }

    /**
     * Ordena los campos a mostrar según lo indicado en el screen en fields -> order
     * @param $orderFields Lista de campos en orden
     *
     */
    public function sortCols($orderFields = array())
    {
        // TODO: Parece que estaría mejor en la declaración del método (Zend_Config $orderFields)
        // de verdad queremos recibir algo aquí que no sea un Zend_Config?
        if (!$orderFields instanceof Zend_Config) {
            return true;
        }

        $orderFieldsList = $this->_getOrderFieldsArray($orderFields);

        $cols = array();
        foreach ($orderFieldsList as $field) {

            if (isset($this->_columns[$field])) {

                $cols[$field] = $this->_columns[$field];
                unset($this->_columns[$field]);
            }
        }

        $this->_columns = array_merge($cols, $this->_columns);
    }

    public function setReadOnly($readOnlyFields = array())
    {
        foreach ($readOnlyFields as $fieldName => $value) {
            if (isset($this->_columns[$fieldName])) {
                $this->_columns[$fieldName]->setReadOnly((bool)$value);
            }
        }
    }

    protected function _getOrderFieldsArray(Zend_Config $orderFields)
    {
        $newOrderFields = array();
        foreach ($orderFields as $fieldName => $value) {
            if (is_numeric($fieldName)) {
                $newOrderFields[] = $value;
            } else {
                $newOrderFields[] = $fieldName;
            }
        }
        return $newOrderFields;
    }

    public function toArray()
    {
        $retArray = array();
        foreach ($this->_columns as $column) {

            $retArray[$column->getDbFieldName()] = $column->toArray();
        }

        return $retArray;
    }

    /**
     * @param string $field
     * @return KlearMatrix_Model_Column|NULL
     */
    public function getColFromDbName($field)
    {
        if (isset($this->_columns[$field])) {

            return $this->_columns[$field];
        }

        return null;
    }


    public function getTypesTemplateArray($path ,$prefix)
    {
        $tmpls = array();
        foreach (array_keys($this->_types) as $type) {
            if ($type == '') {
                continue; // FIXME: por qué hay types vacíos?
            }
            $tmpls[$prefix . $type] = $path . $type;
        }

        return $tmpls;
    }

    public function getMultiLangTemplateArray($path,$type)
    {
        if (false === $this->_multilangColumnIdx ) return false;

        $path .= 'multilang/item/';
        switch($type) {
            case 'list':
            case 'field':
                return array($path . $type);
                break;
        }
    }

    public function getColsJsArray()
    {
        $retJs = array();

        foreach ($this->_columns as $column) {
            $jsPaths = $column->getJsPaths();
            foreach ($jsPaths as $script) {
                $retJs['jsFile_' . crc32($script)] = $script;
            }
        }
        return $retJs;
    }

    public function getColsCssArray()
    {
        $retCss = array();

        foreach ($this->_columns as $column) {
            $aCss = $column->getCssPaths();
            foreach ($aCss as $css) {
                $retCss['cssFile_' . crc32($css)] = $css;
            }
        }
        return $retCss;
    }

    public function getDefaultCol()
    {
        if (false === $this->_defaultColumnIdx) {
            return array_shift($this->_columns);
        }

        return $this->_columns[$this->_defaultColumnIdx];
    }

    /**
     * @param array $langs Languages specified in the model
     */
    public function setLangs($langs)
    {
        // TODO: Sacar esto a una clase externa que se encarge de filtrar los
        // idiomas del modelo, por lo que queramos (o lo del site como hace
        // el siguiente fragmento).
        $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        $this->_klearBootstrap = $bootstrap->getResource('modules')->offsetGet('klear');

        $_currentLangs = $this->_klearBootstrap->getOption('siteConfig')->getLangs();
        foreach ($_currentLangs as $kLanguage) {
            foreach ($langs as $_langIden) {
                if ($kLanguage->getLanguage() == $_langIden) {
                    $this->_langs[] = $_langIden;
                    break;
                }
            }
        }

        $this->_setLangDefinitions($_currentLangs);
    }

    /**
     * @param Klear_Model_Language[] $langs Languages specified in klear.yaml
     */
    protected function _setLangDefinitions($langs)
    {
        foreach ($langs as $lang) {
            $this->_langDefinitions[$lang->getLanguage()] = $lang->toArray();
        }
    }

    public function getLangDefinitions()
    {
        return $this->_langDefinitions;
    }

    public function getLangs()
    {
        return $this->_langs;
    }

    public function getDefaultLang()
    {

        $allLangs = $this->getLangs();

        return array_shift($allLangs);
    }


    public function clear()
    {
        $this->_columns = array();
        $this->_types = array();
        $this->_defaultColumnIdx = false;
        $this->_optionColumnIdx = false;
        return $this;
    }

    public function getOptionColumn()
    {
        if (false === $this->_optionColumnsIdx) {
            return false;
        }

        return $this->_columns[$this->_optionColumnsIdx];
    }

    public function getIterator()
    {
        return new ArrayIterator($this->_columns);
    }

}

//EOF