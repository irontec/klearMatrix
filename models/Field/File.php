<?php

/**
 *
* @author jabi
*
*/
class KlearMatrix_Model_Field_File extends KlearMatrix_Model_Field_Abstract
{
    /**
     * @var KlearMatrix_Model_Field_Password_Abstract
     */
    protected $_adapter;

    protected function _init()
    {
        $sourceConfig = $this->_config->getRaw()->source;
        $adapterClassName = "KlearMatrix_Model_Field_File_" . ucfirst($sourceConfig->data);

        $this->_adapter = new $adapterClassName($sourceConfig);
        $this->_js = $this->_adapter->getExtraJavascript();
        $this->_css = $this->_adapter->getExtraCss();
    }

    /*
     * Prepara el valor de un campo, después del getter
     */
    public function prepareValue($value)
    {
        // Debemos devolver un array con size / mime / name del fichero
        // value nos ha devuelto las specs del campo file

        $ret = array(
            'size' => $this->_column->getModel()->{'get' . ucfirst($value['sizeName'])}(),
            'mime' => $this->_column->getModel()->{'get' . ucfirst($value['mimeName'])}(),
            'name' => $this->_column->getModel()->{'get' . ucfirst($value['baseNameName'])}()
        );

        return $ret;
    }

    public function getCustomOrderField()
    {
        $model = $this->_column->getModel();
        $getter = $this->getCustomGetterName();
        $fields = $model->$getter();

        return $model->varNameToColumn($fields['baseNameName']);
    }

    public function getCustomSearchField()
    {
        $model = $this->_column->getModel();
        $getter = $this->getCustomGetterName();
        $fields = $model->$getter();

        return $model->varNameToColumn($fields['baseNameName']);
    }

    protected function _getConfig()
    {
        return $this->_adapter->getConfig();
    }

    public function getCustomGetterName()
    {
        return 'get' . ucfirst($this->_column->getDbFieldName()) . 'Specs';
    }

    public function getCustomSetterName()
    {
        return 'put' . ucfirst($this->_column->getDbFieldName());
    }

    public function getFetchMethod($dbFieldName)
    {
        return $this->_adapter->getFetchMethod($dbFieldName);
    }

    protected function _filterValue($value)
    {
        if (empty($value)) {

            return false;
        }

        if (strtolower($value) == '__null__') {
            return null;
        }

        $tempFSystemNS = new Zend_Session_Namespace('File_Controller');
        if (isset($tempFSystemNS->{$value})) {

            $tempFile = $tempFSystemNS->{$value};
            // Invocamos put[FILEIDEN] (realpath y basename)
            return $tempFile;
        }

        return false;
    }
}
//EOF
