<?php
class KlearMatrix_Model_Field_Ghost_Concat extends KlearMatrix_Model_Field_Ghost_Abstract
{
    protected $_config;
    protected $_parentField;

    protected $_templateFields = array();

    protected $_searchedValues;

    public function setConfig(Zend_Config $config)
    {
        $kconfig = new Klear_Model_ConfigParser;
        $kconfig->setConfig($config);

        $this->_config = $kconfig;
        return $this;
    }

    public function configureHostFieldConfig(KlearMatrix_Model_Field_Abstract $field)
    {
        $this->_parentField = $field;
        $this->_parentField->setSearchMethod('getSearch');
        $this->_parentField->setOrderMethod('getOrder');
        $this->_parentField->setGetterMethod('getValue');

        // por definición toda las columnas de Concat serán dirty (nos ahorramos ponerlo, y habrá HTML casi siempre)
        $this->_parentField->getColumn()->markAsDirty();

        return $this;
    }

    public function init()
    {
        if (!$this->_parentField) {
            throw new Klear_Exception_MissingConfiguration('Missing parent host for Ghost_Concat');
        }

        $mainModel = $this->_parentField->getColumn()->getModel();

        foreach ($this->_config->getRaw()->source->template as $field => $fConfig) {
            $curField = array();
            $curField['getter'] = 'get' . $mainModel->columnNameToVar($field);
            if (is_string($fConfig)) {
                $curField['literal'] =  Klear_Model_Gettext::gettextCheck($fConfig);
            } else {
                if (isset($fConfig->literal)) {
                    $curField['literal'] =  Klear_Model_Gettext::gettextCheck($fConfig->literal);
                } else {
                    $curField['literal'] = '%' . $field . '%';
                }

                if (isset($fConfig->checkEmpty)) {
                    $curField['checkEmpty'] = (bool)$fConfig->checkEmpty;
                }

                if (isset($fConfig->noSearch)) {
                    $curField['noSearch'] = (bool)$fConfig->noSearch;
                }
            }

            $this->_templateFields[$field] = $curField;

        }

    }

    protected function _highlightFoundString($returnStr)
    {
        if (sizeof($this->_searchedValues) == 0) {
            return $returnStr;
        }

        foreach ($this->_searchedValues as $value) {
            $returnStr = preg_replace(
                '/('.preg_quote(trim($value)).')(?=[^><]*<|.$)/i',
                '<span class="ui-state-highlight">\1</span>',
                $returnStr
            );
        }
        return $returnStr;



    }

    public function getValue($model)
    {
        $returnStr = '';
        foreach ($this->_templateFields as $field => $fConfig) {

            $value = $model->{$fConfig['getter']}();

            if (isset($fConfig['checkEmpty'])) {
                if (empty($value)) {
                    continue;
                }
            }

            $returnStr .= str_replace('%' . $field . '%', $value, $fConfig['literal']);

        }

        return $this->_highlightFoundString($returnStr);
    }


    public function getSearch($values, $searchOps, $model)
    {
        $searchOps; // Avoid PMD UnusedLocalVariable warning
        $model; // Avoid PMD UnusedLocalVariable warning

        $this->_searchedValues = $values;
        $masterConditions = array();
        $fieldValues = array();
        $namedParams = $this->_parentField->getColumn()->namedParamsAreSupported();
        $cont = 0;

        foreach ($this->_templateFields as $field => $fConfig) {
            $auxCondition = array();
            if (isset($fConfig['noSearch']) &&
                $fConfig['noSearch']) {
                continue;
            }

            foreach ($values as $value) {
                $template = $field . $cont++;
                if ($namedParams) {
                    $auxCondition[] =  $field . ' like :' . $template;
                    $fieldValues[$template] = '%' . $value . '%';
                } else {
                    $auxCondition[] = $field . ' like ?';
                    $fieldValues[] = '%' . $value . '%';
                }
            }
            $masterConditions[] = '(' . implode(' or ', $auxCondition) . ')';
        }


        return array(
                '(' . implode(' or ', $masterConditions). ')',
                $fieldValues
        );

    }

    public function getOrder($model)
    {
        $model; // Avoid PMD UnusedLocalVariable warning
        return array_keys($this->_templateFields);
    }


}