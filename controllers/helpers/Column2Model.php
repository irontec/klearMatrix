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
            return $request->getPost($column->getDbFieldName());
        }

        $value = array();
        foreach ($langs as $lang) {
            $value[$lang] = $request->getPost($column->getDbFieldName() . $lang);
        }
        return $value;
    }


    public function column2Model($model, KlearMatrix_Model_Column $column)
    {
        if ($this->_columnIsNotEditable($column)) {
            return;
        }

        $setter = $column->getSetterName();

        $value = $this->_retrieveValueForColumn($column, $model->getAvailableLangs());

        // Avoid accidental DB data deletion. If we don't get the POST param, we don't touch the field
        if (is_null($value)) {
            return;
        }

        $value = $column->filterValue($value);

        if ($column->isMultilang()) {
            foreach ($value as $lang => $_value) {
                $model->$setter($_value, $lang);
            }
            return;
        }

        if ($column->isDependant()) {
            $model->$setter($value, true);
            return;
        }

        if ($column->isFile()) {
            if ($value !== false && file_exists($value['path'])) {
                $model->$setter($value['path'], $value['basename']);
            }
            return;
        }

        $model->$setter($value);
    }


    public function direct($model, KlearMatrix_Model_Column $column)
    {
        return $this->column2Model($model, $column);
    }
}