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

    //Ordena los campos a mostrar según lo indicado en el screen en fields->order
    public function sortCols($orderFields = array())
    {
        if (!$orderFields instanceof Zend_Config) {
            return true;
        }

        $cols = array();

        foreach ($orderFields as $order => $field) {

            if (isset($this->_cols[$order])) {

                $cols[$order] = $this->_cols[$order];
                unset($this->_cols[$order]);
            }
        }

        $this->_cols = array_merge($cols, $this->_cols);
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

    public function setLangs($langs)
    {
        //TODO: Sacar esto a una clase externa que se encarge de filtrar los idiomas del modelo, por lo que queramos (o lo del site como hace el siguiente fragmento).
        $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        $this->_klearBootstrap = $bootstrap->getResource('modules')->offsetGet('klear');

        $_currentLangs = $this->_klearBootstrap->getOption('siteConfig')->getLangs();

        foreach ($langs as $_langIden) {
            foreach ($_currentLangs as $kLanguage) {
                if ($kLanguage->getLanguage() == $_langIden) {
                    $this->_langs[] = $_langIden;
                    break;
                }
            }
        }

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