<?php
class KlearMatrix_Model_Field_Radio extends KlearMatrix_Model_Field_Select
{

    protected $_adapter;

    public function init()
    {
        parent::init();
        $sourceConfig = $this->_config->getRaw()->source;

        $adapterClassName = "KlearMatrix_Model_Field_Select_" . ucfirst($sourceConfig->data);

        $this->_adapter = new $adapterClassName;
        $this->_adapter
                    ->setConfig($sourceConfig)
                    ->setColumn($this->_column)
                    ->init();
    }

    public function filterValue($value, $original)
    {
        if ($value == '__NULL__') {

            return NULL;
        }

        return $value;
    }

    public function getConfig()
    {
        $ret = array_merge(
            $this->_adapter->getExtraConfigArray(),
            array('values' => $this->_adapter->toArray())
        );

        return $ret;
    }

}

//EOF