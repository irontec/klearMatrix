<?php
class KlearMatrix_Controller_Helper_Column2Model extends Zend_Controller_Action_Helper_Abstract
{

    protected function _columnIsNotEditable(KlearMatrix_Model_Column $column)
    {
        return $column->isOption() || $column->isReadOnly();
    }

    protected function _retrieveValueForColumn($column, $langs)
    {
        $request = $this->getRequest();

        if (!$column->isMultilang()) {
            return $this->_autoTrim($column, $request->getPost($column->getDbFieldName()));
        }

        $value = array();
        foreach ($langs as $lang) {
            $value[$lang] = $this->_autoTrim($column, $request->getPost($column->getDbFieldName() . $lang));
        }

        return $value;
    }

    protected function _autoTrim($column, $value)
    {
        $klearConfig = $column->getKlearConfig();
        if (!is_null($klearConfig) && $klearConfig->hasProperty("trim")) {

            $trimMode = $klearConfig->getProperty("trim");
            if ($trimMode === "both") {
                $value = trim($value);
            }
            if ($trimMode === "left") {
                $value = ltrim($value);
            }
            if ($trimMode === "right") {
                $value = rtrim($value);
            }
        }

        return $value;
    }

    public function column2Model($model, KlearMatrix_Model_Column $column)
    {
        if ($this->_columnIsNotEditable($column)) {
            return;
        }

        $this->_setForcedValues($model, $column);
        $setter = $column->getSetterName();

        $availableLanguages = Zend_Registry::get('SystemLanguages');
        $value = $this->_retrieveValueForColumn(
            $column,
            array_keys($availableLanguages)
        );

        // Avoid accidental DB data deletion. If we don't get the POST param, we don't touch the field
        if (is_null($value)) {
            return;
        }

        $value = $column->filterValue($value);

        if ($column->isMultilang()) {
            foreach ($value as $lang => $_value) {
                $model->{$setter . $lang}($_value);
            }
            return;
        }

        if ($column->isDependant()) {
            $model->$setter($value, true);
            return;
        }

        if ($column->isFile()) {

            $pathSetter = $setter . 'Path';
            if (is_null($value)) {
                $model->{$pathSetter}(null);
            } else {
                if ($value !== false && file_exists($value['path'])) {
                    $baseNameSetter = $setter . 'BaseName';
                    $model->{$pathSetter}($value['path']);
                    $model->{$baseNameSetter}($value['basename']);
                }
            }
            return;
        }

        $model->$setter($value);
    }

    protected function _setForcedValues($model, KlearMatrix_Model_Column $column)
    {
        $item = $column->getRouteDispatcher()->getCurrentItem();
        if ($item->hasForcedValues()) {
            foreach ($item->getForcedValues() as $field => $value) {
                try {

                    $field = Klear_Model_QueryHelper::replaceSelfReferences(
                        $this->cleanIdentity($field),
                        ''
                    );

                    $setter = 'set' . ucfirst($field);
                    if (!method_exists($model, $setter)) {
                        $setter .= 'Id';
                    }
                    $model->{$setter}($value);

                } catch (\Exception $e) {
                    // Nothing to do... condition not found in model... :S
                    // Debemos morir??
                }
            }
        }
    }

    public function direct($model, KlearMatrix_Model_Column $column)
    {
        return $this->column2Model($model, $column);
    }


    private function cleanIdentity($field)
    {
        preg_match('/identity\(([^ \)]+)\)/i', $field, $matches);

        if (isset($matches[1])) {
            return $matches[1];
        }

        return $field;
    }
}