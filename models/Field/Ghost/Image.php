<?php
class KlearMatrix_Model_Field_Ghost_Image extends KlearMatrix_Model_Field_Ghost_Abstract
{
    protected $_config;
    protected $_parentField;

    protected $_templateFields = array();

    protected $_searchedValues;

    protected $_idField;
    protected $_md5IdField;
    protected $_imageURL;
    protected $_imageId;
    protected $_urlParams = null;

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
        $this->_parentField->getColumn()->markAsDirty();

        return $this;
    }

    public function init()
    {
        if (!$this->_parentField) {
            throw new Klear_Exception_MissingConfiguration('Missing parent host for Ghost_Image');
        }

        $mainModel = $this->_parentField->getColumn()->getModel();

        $this->_imageURL = $this->_config->getRaw()->source->url;
        $this->_idField = $this->_config->getRaw()->source->idField;
        $this->_md5IdField = false;
        if (isset($this->_config->getRaw()->source->md5IdField)) {
            $this->_md5IdField = $this->_config->getRaw()->source->md5IdField;
        }
        if (isset($this->_config->getRaw()->source->params)) {
            $this->_urlParams = $this->_config->getRaw()->source->params;
        }

    }

    public function getValue($model)
    {
        $idFieldGetter = 'get' . $model->columnNameToVar($this->_idField);
        $imageId = $model->$idFieldGetter();

        $paramsString = "";
        if (!is_null($this->_urlParams)) {
            $params = array();
            foreach ($this->_urlParams as $key => $value) {
                $params[] = $key."=".$value;
            }
            $paramsString = "?".implode("&", $params);
        }

        if ($this->_md5IdField) {
            $imageId = md5($imageId);
        }
           return '<img src="'.$this->_imageURL.$imageId.$paramsString.'" />';
    }

}