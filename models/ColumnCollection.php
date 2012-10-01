<?php

class KlearMatrix_Model_ColumnCollection implements IteratorAggregate
{
    protected $_cols = array();
    protected $_position;

    protected $_optionColumnIdx = false;
    protected $_defaultColumnIdx = false;
    protected $_dependantColumnIdx = array();
    protected $_multilangColumnIdx = array();
    protected $_fileColumnIdx = array();

    protected $_langs = array();

    protected $_types = array();

    protected $_langDefinitions = array();

    public function addCol(KlearMatrix_Model_Column $col)
    {
        $this->_cols[$col->getDbFieldName()] = $col;

        // Estamos dando por hecho, que hay sólo una columna de opciones por listado.
        if ($col->isOption()) {

            $this->_optionColumnsIdx = $col->getDbFieldName();
        } else {

            $this->_types[$col->getType()] = $col->getDbFieldName();
        }

        if ($col->isDefault()) {
            $this->_defaultColumnIdx = $col->getDbFieldName();
        }

        if ($col->isDependant()) {
            $this->_dependantColumnIdx[] = $col->getDbFieldName();
        }

        if ($col->isMultilang()) {
            $this->_multilangColumnIdx[] = $col->getDbFieldName();
        }

        if ($col->isFile()) {
            $this->_fileColumnIdx[] = $col->getDbFieldName();
        }
    }

    /**
     * @param KlearMatrix_Model_Column[] $columns
     */
    public function addCols(array $columns)
    {
        foreach ($columns as $col) {
            $this->addCol($col);
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

            if (isset($this->_cols[$field])) {

                $cols[$field] = $this->_cols[$field];
                unset($this->_cols[$field]);
            }
        }

        $this->_cols = array_merge($cols, $this->_cols);
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
        foreach ($this->_cols as $col) {

            $retArray[$col->getDbFieldName()] = $col->toArray();
        }

        return $retArray;
    }

    /**
     * @param string $field
     * @return KlearMatrix_Model_Column|NULL
     */
    public function getColFromDbName($field)
    {
        if (isset($this->_cols[$field])) {

            return $this->_cols[$field];
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

        foreach ($this->_cols as $col) {
            if ($aJs = $col->getJsPaths()) {
                foreach ($aJs as $script) {
                    $retJs['jsFile_' . crc32($script)] = $script;
                }
            }
        }
        return $retJs;
    }

    public function getColsCssArray()
    {
        $retCss = array();

        foreach ($this->_cols as $col) {
            if ($aCss = $col->getCssPaths()) {
                foreach ($aCss as $css) {
                    $retCss['cssFile_' . crc32($css)] = $css;
                }
            }
        }
        return $retCss;
    }

    public function getDefaultCol()
    {
        if (false === $this->_defaultColumnIdx) {
            return array_shift($this->_cols);
        }

        return $this->_cols[$this->_defaultColumnIdx];
    }

    /**
     * @param array $langs Languages specified in the model
     */
    public function setLangs($langs)
    {
        //TODO: Sacar esto a una clase externa que se encarge de filtrar los idiomas del modelo, por lo que queramos (o lo del site como hace el siguiente fragmento).
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
        $this->_cols = array();
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

        return $this->_cols[$this->_optionColumnsIdx];
    }

    public function getIterator()
    {
        return new ArrayIterator($this->_cols);
    }

}

//EOF